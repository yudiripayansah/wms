<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_belongs_to_product(): void
    {
        $product     = Product::factory()->create();
        $transaction = Transaction::factory()->create(['kode_barang' => $product->kode_barang]);

        $this->assertTrue($transaction->product->is($product));
    }

    public function test_transaction_does_not_have_self_referential_method(): void
    {
        $this->assertFalse(method_exists(Transaction::class, 'transactions'));
    }

    public function test_transaction_status_defaults_to_ok(): void
    {
        $product     = Product::factory()->create();
        $transaction = Transaction::factory()->create(['kode_barang' => $product->kode_barang]);

        $this->assertEquals('OK', $transaction->fresh()->status);
    }

    public function test_all_valid_transaction_types_can_be_saved(): void
    {
        $product = Product::factory()->create();

        foreach (['IN', 'OUT', 'OPNAME', 'ADJUSTMENT'] as $type) {
            $transaction = Transaction::factory()->create([
                'kode_barang' => $product->kode_barang,
                'type'        => $type,
            ]);

            $this->assertEquals($type, $transaction->fresh()->type, "Type $type failed");
        }
    }

    public function test_transaction_declined_status_can_be_saved(): void
    {
        $product     = Product::factory()->create();
        $transaction = Transaction::factory()->declined()->create([
            'kode_barang' => $product->kode_barang,
        ]);

        $this->assertEquals('DECLINED', $transaction->fresh()->status);
    }

    public function test_transaction_session_id_is_nullable(): void
    {
        $product     = Product::factory()->create();
        $transaction = Transaction::factory()->create([
            'kode_barang' => $product->kode_barang,
            'session_id'  => null,
        ]);

        $this->assertNull($transaction->fresh()->session_id);
    }

    public function test_transactions_can_be_filtered_by_type(): void
    {
        $product = Product::factory()->create();
        Transaction::factory()->in()->create(['kode_barang' => $product->kode_barang]);
        Transaction::factory()->in()->create(['kode_barang' => $product->kode_barang]);
        Transaction::factory()->out()->create(['kode_barang' => $product->kode_barang]);

        $this->assertCount(2, Transaction::where('type', 'IN')->get());
        $this->assertCount(1, Transaction::where('type', 'OUT')->get());
    }
}
