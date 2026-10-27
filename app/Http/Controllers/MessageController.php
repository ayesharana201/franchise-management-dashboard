<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    use ScopesToUser;

    public function index(): View
    {
        $ids = $this->visibleLocationIds();

        $threads = Message::with(['sender', 'location'])->withCount('replies')
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereIn('location_id', $ids)->orWhereNull('location_id'))
            ->latest()->paginate(15);

        return view('messages.index', ['threads' => $threads, 'locations' => $this->visibleLocations()]);
    }

    public function show(Message $message): View
    {
        abort_if($message->parent_id !== null, 404);
        $this->authorizeLocation($message->location_id);

        return view('messages.show', ['message' => $message->load(['sender', 'location', 'replies.sender'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        if ($user->isFranchisee()) {
            // Franchisees always write on behalf of their own location, never broadcast.
            $data['location_id'] = $user->location_id;
            $announcement = false;
        } else {
            $this->authorizeLocation($data['location_id'] ?? null);
            $announcement = empty($data['location_id']);
        }

        Message::create($data + ['sender_id' => $user->id, 'is_announcement' => $announcement]);

        return redirect()->route('messages.index')->with('status', 'Message sent.');
    }

    public function reply(Request $request, Message $message): RedirectResponse
    {
        abort_if($message->parent_id !== null, 404);
        $this->authorizeLocation($message->location_id);

        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        Message::create([
            'sender_id' => $request->user()->id,
            'location_id' => $message->location_id,
            'parent_id' => $message->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Reply posted.');
    }
}
