<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\SupplyOrder;

class SupplyOrderTest extends FranchiseTestCase
{
    private function item(int $stock = 10, float $price = 5.0, string $sku = 'SKU-1'): InventoryItem
    {
        return InventoryItem::create(['sku' => $sku, 'name' => "Item $sku", 'unit_price' => $price, 'stock' => $stock, 'unit' => 'each']);
    }

    public function test_placing_an_order_totals_lines_and_decrements_stock(): void
    {
        $loc = $this->location();
        $a = $this->item(10, 5.00, 'A');
        $b = $this->item(20, 2.50, 'B');

        $this->actingAs($this->franchisee($loc))->post('/orders', [
            'location_id' => $loc->id,
            'lines' => [
                ['item_id' => $a->id, 'quantity' => 3],
                ['item_id' => $b->id, 'quantity' => 4],
            ],
        ])->assertRedirect();

        $order = SupplyOrder::first();
        $this->assertEquals(25.00, $order->total); // 3*5 + 4*2.5
        $this->assertSame(2, $order->items()->count());
        $this->assertSame(7, $a->fresh()->stock);
        $this->assertSame(16, $b->fresh()->stock);
    }

    public function test_cannot_order_more_than_is_in_stock(): void
    {
        $loc = $this->location();
        $item = $this->item(2);

        $this->actingAs($this->franchisee($loc))->post('/orders', [
            'location_id' => $loc->id, 'lines' => [['item_id' => $item->id, 'quantity' => 5]],
        ])->assertSessionHasErrors('lines');

        $this->assertSame(0, SupplyOrder::count());
        $this->assertSame(2, $item->fresh()->stock);
    }

    public function test_order_needs_at_least_one_nonzero_line(): void
    {
        $loc = $this->location();
        $item = $this->item();

        $this->actingAs($this->franchisee($loc))->post('/orders', [
            'location_id' => $loc->id, 'lines' => [['item_id' => $item->id, 'quantity' => 0]],
        ])->assertSessionHasErrors('lines');
    }

    public function test_franchisee_cannot_order_for_another_location(): void
    {
        $mine = $this->location();
        $other = $this->location();
        $item = $this->item();

        $this->actingAs($this->franchisee($mine))->post('/orders', [
            'location_id' => $other->id, 'lines' => [['item_id' => $item->id, 'quantity' => 1]],
        ])->assertForbidden();
    }

    public function test_cancelling_an_order_returns_stock_exactly_once(): void
    {
        $loc = $this->location();
        $item = $this->item(10);
        $this->actingAs($this->franchisee($loc))->post('/orders', [
            'location_id' => $loc->id, 'lines' => [['item_id' => $item->id, 'quantity' => 4]],
        ]);
        $order = SupplyOrder::first();
        $this->assertSame(6, $item->fresh()->stock);

        $admin = $this->franchisor();
        $this->actingAs($admin)->put("/orders/{$order->id}/status", ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(10, $item->fresh()->stock);

        $this->actingAs($admin)->put("/orders/{$order->id}/status", ['status' => 'cancelled'])->assertStatus(422);
        $this->assertSame(10, $item->fresh()->stock);
    }

    public function test_franchisee_cannot_change_order_status(): void
    {
        $loc = $this->location();
        $user = $this->franchisee($loc);
        $order = SupplyOrder::create(['location_id' => $loc->id, 'user_id' => $user->id, 'status' => 'submitted', 'total' => 0]);

        $this->actingAs($user)->put("/orders/{$order->id}/status", ['status' => 'approved'])->assertForbidden();
    }

    public function test_only_franchisor_can_edit_the_catalogue(): void
    {
        $loc = $this->location();
        $payload = ['sku' => 'NEW', 'name' => 'New', 'unit' => 'each', 'unit_price' => 1, 'stock' => 1, 'reorder_level' => 1];

        $this->actingAs($this->franchisee($loc))->post('/inventory', $payload)->assertForbidden();
        $this->actingAs($this->franchisor())->post('/inventory', $payload)->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['sku' => 'NEW']);
    }

    public function test_franchisee_cannot_view_another_locations_order(): void
    {
        $mine = $this->location();
        $other = $this->location();
        $user = $this->franchisee($mine);

        $order = SupplyOrder::create([
            'location_id' => $other->id,
            'user_id' => $user->id,
            'status' => 'submitted',
            'total' => 0,
        ]);

        $this->actingAs($user)
            ->get("/orders/{$order->id}")
            ->assertForbidden();
    }
}
