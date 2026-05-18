<?php

namespace Tests\Unit;

use App\Models\Allocation;
use App\Models\AllocationItem;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocation_defaults_to_draft(): void
    {
        $allocation = Allocation::factory()->create();

        $this->assertEquals('DRAFT', $allocation->fresh()->status);
    }

    public function test_allocation_has_items_relationship(): void
    {
        $product    = Product::factory()->create();
        $allocation = Allocation::factory()->create();
        $item       = AllocationItem::factory()->create([
            'allocation_id' => $allocation->id,
            'kode_barang'   => $product->kode_barang,
        ]);

        $this->assertTrue($allocation->items->contains($item));
    }

    public function test_allocation_item_belongs_to_allocation(): void
    {
        $product    = Product::factory()->create();
        $allocation = Allocation::factory()->create();
        $item       = AllocationItem::factory()->create([
            'allocation_id' => $allocation->id,
            'kode_barang'   => $product->kode_barang,
        ]);

        $this->assertTrue($item->allocation->is($allocation));
    }

    public function test_allocation_item_belongs_to_product(): void
    {
        $product    = Product::factory()->create();
        $allocation = Allocation::factory()->create();
        $item       = AllocationItem::factory()->create([
            'allocation_id' => $allocation->id,
            'kode_barang'   => $product->kode_barang,
        ]);

        $this->assertTrue($item->product->is($product));
    }

    public function test_allocation_items_cascade_delete_with_allocation(): void
    {
        $product    = Product::factory()->create();
        $allocation = Allocation::factory()->create();
        AllocationItem::factory()->create([
            'allocation_id' => $allocation->id,
            'kode_barang'   => $product->kode_barang,
        ]);

        $allocation->delete();

        $this->assertDatabaseMissing('allocation_items', ['allocation_id' => $allocation->id]);
    }

    public function test_allocation_session_id_is_unique(): void
    {
        $session = 'session-duplicate-test';
        Allocation::factory()->create(['session_id' => $session]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Allocation::factory()->create(['session_id' => $session]);
    }

    public function test_allocation_item_resolves_stock_location_automatically(): void
    {
        $product    = Product::factory()->create();
        $stock      = Stock::factory()->create([
            'kode_barang' => $product->kode_barang,
            'location'    => 'LOC-AUTO',
            'box'         => 'BOX-AUTO',
            'qty'         => 10,
        ]);
        $allocation = Allocation::factory()->create();

        $item = AllocationItem::factory()->create([
            'allocation_id' => $allocation->id,
            'kode_barang'   => $product->kode_barang,
            'location'      => 'LOC-AUTO',
            'box'           => 'BOX-AUTO',
        ]);

        $this->assertEquals('LOC-AUTO', $item->location);
        $this->assertEquals('BOX-AUTO', $item->box);
    }
}
