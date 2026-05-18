<?php

namespace Tests\Feature;

use App\Exports\ProductsExport;
use App\Exports\ProductTransactionExport;
use App\Exports\TransactionExport;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_export_headings_are_correct(): void
    {
        $export   = new ProductsExport();
        $expected = ['Kode Barang', 'Brand', 'Barcode', 'SKU', 'Nama Barang', 'Colour', 'Size', 'Total Qty', 'Price'];

        $this->assertEquals($expected, $export->headings());
    }

    public function test_products_export_maps_total_stock_qty(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 15]);
        Stock::factory()->create(['kode_barang' => $product->kode_barang, 'qty' => 10]);

        $export  = new ProductsExport();
        $row     = $export->collection()->where('kode_barang', $product->kode_barang)->first();
        $mapped  = $export->map($row);

        $this->assertEquals(25, $mapped[7]);
    }

    public function test_products_export_returns_zero_qty_when_no_stocks(): void
    {
        $product = Product::factory()->create();

        $export = new ProductsExport();
        $row    = $export->collection()->where('kode_barang', $product->kode_barang)->first();
        $mapped = $export->map($row);

        $this->assertEquals(0, $mapped[7]);
    }

    public function test_transaction_export_filters_by_type(): void
    {
        $product = Product::factory()->create();
        Transaction::factory()->in()->create(['kode_barang' => $product->kode_barang]);
        Transaction::factory()->in()->create(['kode_barang' => $product->kode_barang]);
        Transaction::factory()->out()->create(['kode_barang' => $product->kode_barang]);

        $export = new TransactionExport('IN');
        $this->assertCount(2, $export->collection());

        $exportOut = new TransactionExport('OUT');
        $this->assertCount(1, $exportOut->collection());
    }

    public function test_transaction_export_headings_are_correct(): void
    {
        $export   = new TransactionExport('IN');
        $expected = ['Session ID', 'Kode Barang', 'Nama Barang', 'Colour', 'Size', 'Qty', 'Location', 'Box', 'Status', 'Keterangan', 'Tanggal'];

        $this->assertEquals($expected, $export->headings());
    }

    public function test_transaction_export_maps_product_name(): void
    {
        $product = Product::factory()->create(['nama_barang' => 'Produk Ekspor']);
        $tx      = Transaction::factory()->in()->create(['kode_barang' => $product->kode_barang]);

        $export = new TransactionExport('IN');
        $row    = $export->collection()->first();
        $mapped = $export->map($row);

        $this->assertEquals('Produk Ekspor', $mapped[2]);
    }

    public function test_product_transaction_export_filters_by_kode_barang(): void
    {
        $productA = Product::factory()->create();
        $productB = Product::factory()->create();
        Transaction::factory()->in()->create(['kode_barang' => $productA->kode_barang]);
        Transaction::factory()->out()->create(['kode_barang' => $productA->kode_barang]);
        Transaction::factory()->in()->create(['kode_barang' => $productB->kode_barang]);

        $export = new ProductTransactionExport($productA->kode_barang);
        $this->assertCount(2, $export->collection());
    }
}
