<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Franchisor-only user and role administration. */
class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::with('location')->orderBy('role')->orderBy('name')->paginate(20),
            'locations' => Location::orderBy('name')->get(),
            'regions' => Location::distinct()->pluck('region'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request) + ['password' => $request->validate(['password' => ['required', 'string', 'min:8']])['password']]);

        return back()->with('status', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->id === $request->user()->id && $data['role'] !== User::ROLE_FRANCHISOR) {
            return back()->withErrors(['role' => 'You cannot remove your own franchisor access.']);
        }

        if ($request->filled('password')) {
            $data['password'] = $request->validate(['password' => ['string', 'min:8']])['password'];
        }

        $user->update($data);

        return back()->with('status', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');
        $user->delete();

        return back()->with('status', 'User deleted.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([User::ROLE_FRANCHISOR, User::ROLE_REGIONAL, User::ROLE_FRANCHISEE])],
            'location_id' => ['nullable', 'required_if:role,' . User::ROLE_FRANCHISEE, 'exists:locations,id'],
            'region' => ['nullable', 'required_if:role,' . User::ROLE_REGIONAL, 'string', 'max:100'],
        ]);

        // Keep scoping columns consistent with the role.
        if ($data['role'] !== User::ROLE_FRANCHISEE) {
            $data['location_id'] = null;
        }
        if ($data['role'] !== User::ROLE_REGIONAL) {
            $data['region'] = null;
        }

        return $data;
    }
}
