<x-filament-panels::page>

    @php
        $inventory    = $this->getInventory();
        $transactions = $this->getTransactions();
    @endphp

    {{-- Inventory Info Card --}}
    @if($inventory)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap gap-6 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ __('general.barcode') }}</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $inventory->barcode }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ __('general.article') }}</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $inventory->article }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ __('general.brand') }}</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $inventory->brand ?: '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ __('general.sku') }}</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $inventory->sku ?: '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ __('general.color') }}</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $inventory->color ?: '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400 font-medium">{{ __('general.size') }}</span>
                <p class="font-semibold text-gray-800 dark:text-gray-100 mt-0.5">{{ $inventory->size ?: '-' }}</p>
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
            {{ __('general.export_excel') }}
        </button>

        <a
            href="{{ $this->getPdfUrl() }}"
            target="_blank"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-600 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 dark:hover:bg-gray-600 focus:outline-none"
        >
            {{ __('general.export_pdf') }}
        </a>
    </div>

    {{-- Transaction Table --}}
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ __('transactions.all_transactions', ['count' => $transactions->count()]) }}
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-300 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">{{ __('general.type') }}</th>
                        <th class="px-4 py-3">{{ __('general.session_id') }}</th>
                        <th class="px-4 py-3">{{ __('general.qty') }}</th>
                        <th class="px-4 py-3">{{ __('general.location') }}</th>
                        <th class="px-4 py-3">{{ __('general.bin') }}</th>
                        <th class="px-4 py-3">{{ __('general.status') }}</th>
                        <th class="px-4 py-3">{{ __('general.remarks') }}</th>
                        <th class="px-4 py-3">{{ __('general.date') }}</th>
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
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $t->bin ?: '-' }}</td>
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
                            {{ __('transactions.no_transactions') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>
