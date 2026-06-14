<?php

namespace App\Concerns;

use Livewire\Attributes\Renderless;

trait HasTransactionRows
{
    #[Renderless]
    public function addTransactionRow(): void
    {
        $this->transactionRows[] = [
            'barcode'  => '',
            'article'  => '',
            'sku'      => '',
            'color'    => '',
            'size'     => '',
            'qty'      => 1,
            'location' => '',
            'bin'      => '',
            'status'   => 'OK',
            'remarks'  => '',
        ];
    }

    #[Renderless]
    public function removeTransactionRow(int $index): void
    {
        array_splice($this->transactionRows, $index, 1);
        $this->transactionRows = array_values($this->transactionRows);
    }

    #[Renderless]
    public function updateTransactionRowStatus(int $index, string $status, string $remarks): void
    {
        if (isset($this->transactionRows[$index])) {
            $this->transactionRows[$index]['status']  = $status;
            $this->transactionRows[$index]['remarks'] = $remarks;
        }
    }
}
