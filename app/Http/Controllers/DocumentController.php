<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use ScopesToUser;

    public function index(Request $request): View
    {
        $ids = $this->visibleLocationIds();

        $documents = Document::with(['location', 'uploader'])
            ->where(fn ($q) => $q->whereIn('location_id', $ids)->orWhereNull('location_id'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->orderByRaw('expires_on IS NULL, expires_on ASC')
            ->paginate(20)->withQueryString();

        return view('documents.index', ['documents' => $documents, 'locations' => $this->visibleLocations()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Document::TYPES)],
            'location_id' => ['nullable', 'exists:locations,id'],
            'expires_on' => ['nullable', 'date'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,png,jpg,jpeg'],
        ]);

        $user = $request->user();

        // Franchisees can only upload to their own location; network-wide docs are staff only.
        if ($user->isFranchisee()) {
            $data['location_id'] = $user->location_id;
        } else {
            $this->authorizeLocation($data['location_id'] ?? null);
        }

        $file = $request->file('file');

        Document::create([
            'location_id' => $data['location_id'] ?? null,
            'uploaded_by' => $user->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'expires_on' => $data['expires_on'] ?? null,
            'original_name' => $file->getClientOriginalName(),
            'path' => $file->store('documents', 'local'),
        ]);

        return back()->with('status', 'Document uploaded.');
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorizeLocation($document->location_id);

        abort_unless(Storage::disk('local')->exists($document->path), 404, 'File not found.');

        return Storage::disk('local')->download($document->path, $document->original_name ?? basename($document->path));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorizeLocation($document->location_id);

        // Only network staff, or the uploader, may delete.
        abort_unless(auth()->user()->isNetworkStaff() || $document->uploaded_by === auth()->id(), 403);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('status', 'Document deleted.');
    }
}
