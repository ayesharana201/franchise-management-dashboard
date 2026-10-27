<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MessagesAndDocumentsTest extends FranchiseTestCase
{
    public function test_franchisee_messages_are_always_tied_to_their_own_location(): void
    {
        $mine = $this->location();
        $other = $this->location();
        $user = $this->franchisee($mine);

        $this->actingAs($user)->post('/messages', [
            'subject' => 'Help', 'body' => 'Fryer broke', 'location_id' => $other->id,
        ])->assertRedirect();

        $message = Message::first();
        $this->assertSame($mine->id, $message->location_id);
        $this->assertFalse($message->is_announcement);
    }

    public function test_staff_message_with_no_location_is_an_announcement_visible_to_franchisees(): void
    {
        $loc = $this->location();

        $this->actingAs($this->franchisor())->post('/messages', ['subject' => 'Holiday hours', 'body' => 'Closed Friday'])->assertRedirect();
        $this->assertTrue(Message::first()->is_announcement);

        $this->actingAs($this->franchisee($loc))->get('/messages')->assertOk()->assertSee('Holiday hours');
    }

    public function test_franchisee_cannot_read_another_locations_thread(): void
    {
        $mine = $this->location();
        $other = $this->location();
        $admin = $this->franchisor();
        $thread = Message::create(['sender_id' => $admin->id, 'location_id' => $other->id, 'subject' => 'Private', 'body' => 'x']);

        $this->actingAs($this->franchisee($mine))->get("/messages/{$thread->id}")->assertForbidden();
        $this->actingAs($this->franchisee($mine))->get('/messages')->assertDontSee('Private');
    }

    public function test_replies_attach_to_the_thread(): void
    {
        $loc = $this->location();
        $admin = $this->franchisor();
        $thread = Message::create(['sender_id' => $admin->id, 'location_id' => $loc->id, 'subject' => 'Hi', 'body' => 'x']);

        $this->actingAs($this->franchisee($loc))->post("/messages/{$thread->id}/reply", ['body' => 'Thanks'])->assertRedirect();

        $this->assertSame(1, $thread->replies()->count());
    }

    public function test_upload_and_download_a_document(): void
    {
        Storage::fake('local');
        $loc = $this->location();
        $user = $this->franchisee($loc);

        $this->actingAs($user)->post('/documents', [
            'title' => 'Lease', 'type' => 'contract', 'expires_on' => now()->addYear()->toDateString(),
            'file' => UploadedFile::fake()->create('lease.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $doc = Document::first();
        $this->assertSame($loc->id, $doc->location_id);
        Storage::disk('local')->assertExists($doc->path);

        $this->actingAs($user)->get("/documents/{$doc->id}/download")->assertOk();
    }

    public function test_rejects_disallowed_file_types(): void
    {
        Storage::fake('local');
        $loc = $this->location();

        $this->actingAs($this->franchisee($loc))->post('/documents', [
            'title' => 'Bad', 'type' => 'other', 'file' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('file');
    }

    public function test_franchisee_cannot_download_another_locations_document(): void
    {
        Storage::fake('local');
        $mine = $this->location();
        $other = $this->location();
        $admin = $this->franchisor();
        Storage::disk('local')->put('documents/x.pdf', 'x');
        $doc = Document::create(['location_id' => $other->id, 'uploaded_by' => $admin->id, 'title' => 'T', 'type' => 'contract', 'path' => 'documents/x.pdf']);

        $this->actingAs($this->franchisee($mine))->get("/documents/{$doc->id}/download")->assertForbidden();
    }

    public function test_network_wide_documents_are_visible_to_everyone(): void
    {
        $loc = $this->location();
        $admin = $this->franchisor();
        Document::create(['location_id' => null, 'uploaded_by' => $admin->id, 'title' => 'Brand Handbook', 'type' => 'policy', 'path' => 'documents/h.pdf']);

        $this->actingAs($this->franchisee($loc))->get('/documents')->assertOk()->assertSee('Brand Handbook');
    }
}
