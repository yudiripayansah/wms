<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_has_stocks_relationship(): void
    {
        $product = Product::factory()->create();
        $stock   = Stock::factory()->create(['kode_barang' => $product->kode_barang]);

        $this->assertTrue($product->stocks->contains($stock));
    }

    public function test_product_has_transactions_relationship(): void
    {
        $product     = Product::factory()->create();
        $transaction = Transaction::factory()->create(['kode_barang' => $product->kode_barang]);

        $this->assertTrue($product->transactions->contains($transaction));
    }

    public function test_total_qty_is_sum_of_all_stocks(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 10]);
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 25]);

        $this->assertEquals(35, $product->stocks->sum('qty'));
    }

    public function test_total_qty_via_with_sum(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 15]);
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 5]);

        $loaded = Product::withSum('stocks', 'qty')->find($product->id);

        $this->assertEquals(20, $loaded->stocks_sum_qty);
    }

    public function test_product_stock_uses_custom_foreign_key(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 7]);

        $stock = Stock::where('kode_barang', $product->kode_barang)->first();

        $this->assertEquals($product->kode_barang, $stock->kode_barang);
        $this->assertTrue($stock->product->is($product));
    }

    public function test_deleting_product_cascades_to_stocks(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create(['kode_barang' => $product->kode_barang]);

        $kode = $product->kode_barang;
        $product->delete();

        $this->assertDatabaseMissing('stocks', ['kode_barang' => $kode]);
    }

    public function test_deleting_product_cascades_to_transactions(): void
    {
        $product = Product::factory()->create();
        Transaction::factory()->create(['kode_barang' => $product->kode_barang]);

        $kode = $product->kode_barang;
        $product->delete();

        $this->assertDatabaseMissing('transactions', ['kode_barang' => $kode]);
    }
}
