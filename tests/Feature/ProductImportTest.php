<?php

namespace Tests\Feature;

use App\Imports\ProductsImport;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    private function row(array $data): \Illuminate\Support\Collection
    {
        return collect($data);
    }

    public function test_import_creates_new_product(): void
    {
        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => 'TEST-001', 'nama_barang' => 'Sepatu Lari', 'brand' => 'Nike', 'price' => '250000']),
        ]));

        $this->assertEquals(1, $import->success);
        $this->assertEquals(0, $import->updated);
        $this->assertDatabaseHas('products', ['kode_barang' => 'TEST-001', 'nama_barang' => 'Sepatu Lari']);
    }

    public function test_import_updates_existing_product(): void
    {
        Product::factory()->create(['kode_barang' => 'UPD-001', 'nama_barang' => 'Nama Lama']);

        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => 'UPD-001', 'nama_barang' => 'Nama Baru', 'brand' => 'Adidas']),
        ]));

        $this->assertEquals(1, $import->updated);
        $this->assertEquals(0, $import->success);
        $this->assertDatabaseHas('products', ['kode_barang' => 'UPD-001', 'nama_barang' => 'Nama Baru']);
    }

    public function test_import_skips_row_without_kode_barang(): void
    {
        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => '', 'nama_barang' => 'Test Product']),
        ]));

        $this->assertEquals(1, $import->failed);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_skips_row_without_nama_barang(): void
    {
        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => 'NO-NAME', 'nama_barang' => '']),
        ]));

        $this->assertEquals(1, $import->failed);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_import_normalises_price_with_dot_thousand_separator(): void
    {
        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => 'PRC-001', 'nama_barang' => 'Mahal', 'price' => '1.500.000']),
        ]));

        $product = Product::where('kode_barang', 'PRC-001')->first();
        $this->assertEquals(1500000, $product->price);
    }

    public function test_import_handles_multiple_rows(): void
    {
        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => 'MUL-001', 'nama_barang' => 'Produk A']),
            $this->row(['kode_barang' => 'MUL-002', 'nama_barang' => 'Produk B']),
            $this->row(['kode_barang' => '',        'nama_barang' => 'Invalid']),
        ]));

        $this->assertEquals(2, $import->success);
        $this->assertEquals(1, $import->failed);
        $this->assertDatabaseCount('products', 2);
    }

    public function test_import_trims_whitespace(): void
    {
        $import = new ProductsImport();
        $import->collection(collect([
            $this->row(['kode_barang' => '  TRIM-001  ', 'nama_barang' => '  Produk Spasi  ']),
        ]));

        $this->assertDatabaseHas('products', [
            'kode_barang'  => 'TRIM-001',
            'nama_barang' => 'Produk Spasi',
        ]);
    }
}
