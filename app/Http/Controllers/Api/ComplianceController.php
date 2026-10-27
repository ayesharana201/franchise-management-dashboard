<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Http\Controllers\Controller;
use App\Models\ComplianceCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplianceController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            ComplianceCheck::with(['location:id,name,code', 'item:id,title,category'])
                ->whereIn('location_id', $this->visibleLocationIds())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->orderBy('due_date')->paginate(25)
        );
    }

    public function update(Request $request, ComplianceCheck $check): JsonResponse
    {
        $this->authorizeLocation($check->location_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'passed', 'failed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $check->update($data + [
            'checked_on' => $data['status'] === 'pending' ? null : now()->toDateString(),
            'checked_by' => $data['status'] === 'pending' ? null : $request->user()->id,
        ]);

        return response()->json($check->fresh());
    }
}
