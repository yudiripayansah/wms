<x-filament-panels::page>

    @php
        $product      = $this->getProduct();
        $transactions = $this->getTransactions();
    @endphp

    {{-- Product Info Card --}}
    @if($product)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap gap-6 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">Kode Barang</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $product->kode_barang }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">Nama Barang</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $product->nama_barang }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">Brand</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $product->brand ?: '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">Colour</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $product->colour ?: '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">Size</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $product->size ?: '-' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Export Buttons --}}
    <div class="flex gap-3 mb-4">
        <button
            wire:click="exportExcel"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus:outline-none"
        >
            Export Excel
        </button>

        <a
            href="{{ $this->getPdfUrl() }}"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-600 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 dark:hover:bg-gray-600 focus:outline-none"
        >
            Export PDF
        </a>
    </div>

    {{-- Transaction Table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Semua Transaksi &mdash; {{ $transactions->count() }} record
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-300 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Session ID</th>
                        <th class="px-4 py-3">Qty</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">Box</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($transactions as $i => $t)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match($t->type) {
                                    'IN'         => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400',
                                    'OUT'        => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400',
                                    'OPNAME'     => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400',
                                    'ADJUSTMENT' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400',
                                    default      => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badge }}">
                                {{ $t->type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $t->session_id ?: '-' }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-100">{{ $t->qty }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $t->location ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $t->box ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusClass = $t->status === 'OK'
                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400'
                                    : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">
                                {{ $t->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $t->remarks ?: '-' }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $t->created_at?->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                            Belum ada transaksi untuk produk ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>
