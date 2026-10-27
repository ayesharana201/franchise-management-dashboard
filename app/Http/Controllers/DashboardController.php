<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ScopesToUser;
use App\Models\ComplianceCheck;
use App\Models\Document;
use App\Models\SupplyOrder;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ScopesToUser;

    public function index(Request $request, ReportService $reports): View
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $ids = $this->visibleLocationIds();

        $recentOrders = SupplyOrder::with('location')
            ->whereIn('location_id', $ids)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'type' => 'order',
                'title' => 'Supply order #' . $order->id,
                'description' => $order->location->name . ' · ' . ucfirst(str_replace('_', ' ', $order->status)),
                'created_at' => $order->created_at,
                'url' => route('orders.show', $order),
            ]);

        $recentDocuments = Document::with('location')
            ->where(fn ($query) => $query
                ->whereIn('location_id', $ids)
                ->orWhereNull('location_id'))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($document) => [
                'type' => 'document',
                'title' => $document->title,
                'description' => $document->location?->name ?? 'Network-wide document',
                'created_at' => $document->created_at,
                'url' => route('documents.index'),
            ]);

        $recentActivity = $recentOrders
            ->concat($recentDocuments)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        return view('dashboard.index', [
            'stats' => $reports->dashboard(
                $request->user(),
                $data['from'] ?? null,
                $data['to'] ?? null
            ),
            'overdueChecks' => ComplianceCheck::with(['location', 'item'])
                ->whereIn('location_id', $ids)
                ->where('status', 'pending')
                ->whereDate('due_date', '<', now())
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
            'pendingOrders' => SupplyOrder::with('location')
                ->whereIn('location_id', $ids)
                ->where('status', 'submitted')
                ->latest()
                ->limit(5)
                ->get(),
            'expiringDocs' => Document::with('location')
                ->where(fn ($query) => $query
                    ->whereIn('location_id', $ids)
                    ->orWhereNull('location_id'))
                ->whereBetween('expires_on', [now(), now()->addDays(60)])
                ->orderBy('expires_on')
                ->limit(5)
                ->get(),
            'recentActivity' => $recentActivity,
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
        ]);
    }
}
