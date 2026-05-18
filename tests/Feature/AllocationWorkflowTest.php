<?php

namespace Tests\Feature;

use App\Imports\AllocationItemImport;
use App\Models\Allocation;
use App\Models\AllocationItem;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_allocation_defaults_to_draft(): void
    {
        $allocation = Allocation::factory()->create();

        $this->assertEquals('DRAFT', $allocation->fresh()->status);
    }

    public function test_allocation_transitions_draft_to_confirmed(): void
    {
        $allocation = Allocation::factory()->create(['status' => 'DRAFT']);
        $allocation->update(['status' => 'CONFIRMED']);

        $this->assertEquals('CONFIRMED', $allocation->fresh()->status);
    }

    public function test_allocation_transitions_confirmed_to_processed(): void
    {
        $allocation = Allocation::factory()->confirmed()->create();
        $allocation->update(['status' => 'PROCESSED']);

        $this->assertEquals('PROCESSED', $allocation->fresh()->status);
    }

    public function test_import_creates_allocation_items_for_known_products(): void
    {
        $product    = Product::factory()->create();
        $stock      = Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 10]);
        $allocation = Allocation::factory()->create();

        $import = new AllocationItemImport($allocation->id);
        $import->collection(collect([
            collect(['kode_barang' => $product->kode_barang, 'qty' => 3, 'location' => null, 'box' => null]),
        ]));

        $this->assertEquals(1, $import->imported);
        $this->assertEquals(0, $import->skipped);
        $this->assertDatabaseHas('allocation_items', [
            'allocation_id' => $allocation->id,
            'kode_barang'   => $product->kode_barang,
            'qty'           => 3,
        ]);
    }

    public function test_import_skips_unknown_kode_barang(): void
    {
        $allocation = Allocation::factory()->create();

        $import = new AllocationItemImport($allocation->id);
        $import->collection(collect([
            collect(['kode_barang' => 'UNKNOWN-999', 'qty' => 5, 'location' => null, 'box' => null]),
        ]));

        $this->assertEquals(0, $import->imported);
        $this->assertEquals(1, $import->skipped);
        $this->assertDatabaseCount('allocation_items', 0);
    }

    public function test_import_uses_stock_location_when_not_provided(): void
    {
        $product    = Product::factory()->create();
        Stock::factory()->create([
            'kode_barang' => $product->kode_barang,
            'qty'         => 5,
            'location'    => 'LOC-STOK',
            'box'         => 'BOX-STOK',
        ]);
        $allocation = Allocation::factory()->create();

        $import = new AllocationItemImport($allocation->id);
        $import->collection(collect([
            collect(['kode_barang' => $product->kode_barang, 'qty' => 2, 'location' => '', 'box' => '']),
        ]));

        $item = AllocationItem::where('allocation_id', $allocation->id)->first();
        $this->assertEquals('LOC-STOK', $item->location);
        $this->assertEquals('BOX-STOK', $item->box);
    }

    public function test_import_skips_row_with_empty_kode_barang(): void
    {
        $allocation = Allocation::factory()->create();

        $import = new AllocationItemImport($allocation->id);
        $import->collection(collect([
            collect(['kode_barang' => '', 'qty' => 1, 'location' => null, 'box' => null]),
        ]));

        $this->assertEquals(0, $import->imported);
        $this->assertEquals(1, $import->skipped);
    }

    public function test_allocation_items_are_deleted_when_allocation_is_deleted(): void
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
}
