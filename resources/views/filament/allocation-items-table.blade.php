@php
    $inventoryMap   = $inventoryMap   ?? [];
    $stockTotals    = $stockTotals    ?? [];
    $reservedMap    = $reservedMap    ?? [];
    $canManageItems = $canManageItems ?? true;
@endphp

<script>
    window.__allocInventoryMap   = @json($inventoryMap);
    window.__allocStockTotals    = @json($stockTotals);
    window.__allocReservedMap    = @json($reservedMap);
    window.__allocStockBreakdown = @json($stockBreakdown ?? []);
</script>

<div
    wire:ignore
    x-data="{
        rows:               $wire.entangle('allocationRows'),
        inventoryMap:       window.__allocInventoryMap   || {},
        stockTotals:        window.__allocStockTotals    || {},
        reservedMap:        window.__allocReservedMap    || {},
        stockBreakdown:     window.__allocStockBreakdown || {},
        canManageItems:     $wire.canManageItems,
        canEditQty:         $wire.canEditQty,
        isSelfReserved:     $wire.isSelfReserved,
        selfReservedAtLoad: {},

        init() {
            // Only add back self-reserved quantities when this allocation is already in
            // reservedMap (status PROCESSING or FINISHED). For PENDING/new, the allocation
            // is NOT in reservedMap, so adding it back would double-count.
            if (this.isSelfReserved) {
                var self = this;
                (this.rows || []).forEach(function(r) {
                    if (r.barcode) {
                        self.selfReservedAtLoad[r.barcode] = (self.selfReservedAtLoad[r.barcode] || 0) + (r.qty || 0);
                    }
                });
            }
        },

        getAvailable(barcode) {
            if (!barcode) return 0;
            var total      = this.stockTotals[barcode]       || 0;
            var reservedAll= this.reservedMap[barcode]       || 0;
            var selfLoad   = this.selfReservedAtLoad[barcode]|| 0;
            return Math.max(0, total - reservedAll + selfLoad);
        },

        validateQty(i) {
            var b         = (this.rows[i].barcode || '').trim();
            var available = this.getAvailable(b);
            var qty       = this.rows[i].qty || 0;
            var exceed    = b && qty > 0 && qty > available;
            this.rows[i]  = Object.assign({}, this.rows[i], { exceed: exceed, available: available });
        },

        addRow() {
            this.$wire.addAllocationRow();
        },

        removeRow(i) {
            this.$wire.removeAllocationRow(i);
        },

        lookupInventory(i) {
            var b = (this.rows[i].barcode || '').trim();

            // Duplicate barcode check
            if (b && this.rows.some(function(r, idx) { return idx !== i && (r.barcode || '').trim() === b; })) {
                this.rows[i] = Object.assign({}, this.rows[i], { barcode: '', article: '', sku: '', color: '', size: '', exceed: false, available: 0 });
                alert('Barcode ' + b + ' sudah ada di daftar item.');
                return;
            }

            var inv = this.inventoryMap[b] || null;
            var article = inv ? (inv.article || '') : '';
            var sku     = inv ? (inv.sku     || '') : '';
            var color   = inv ? (inv.color   || '') : '';
            var size    = inv ? (inv.size    || '') : '';
            this.rows[i] = Object.assign({}, this.rows[i], {
                article: article, sku: sku, color: color, size: size
            });
            this.validateQty(i);
        }
    }"
    style="min-width:0; width:100%;"
>

    {{-- Accumulation info banner --}}
    <template x-if="$wire.totalAllocationRows > 0">
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20 px-4 py-3 text-sm text-blue-800 dark:text-blue-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>
                File mengandung <strong x-text="$wire.totalAllocationRows"></strong> baris &rarr;
                <strong x-text="rows.length"></strong> item unik setelah akumulasi.
            </span>
        </div>
    </template>

    {{-- Exceed warning --}}
    <template x-if="rows.some(r => r.exceed)">
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20 px-4 py-3 text-sm text-red-800 dark:text-red-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <span>
                Beberapa item <strong>melebihi stok yang tersedia</strong> (ditandai merah). Kurangi qty atau hapus item tersebut sebelum menyimpan.
            </span>
        </div>
    </template>

    <div class="flex items-center justify-between mt-4 mb-5">
        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="rows.length + ' item'"></span>
        <div class="flex items-center gap-2" x-show="canManageItems">
            <a
                href="{{ route('template.transaction-import') }}"
                target="_blank"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Template
            </a>
            <button
                type="button"
                x-on:click="addRow()"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:outline-none"
            >
                + Tambah Item
            </button>
        </div>
    </div>

    <div class="mt-4 border border-gray-200 dark:border-gray-700 rounded-lg" style="overflow-x:auto; overflow-y:auto; max-height:420px; width:100%;">
        <table class="w-full text-xs border-collapse" style="min-width:680px;">
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                <tr class="border-b border-gray-200 dark:border-gray-600">
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:24px">#</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:115px">Barcode</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:120px">Article</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:85px">SKU</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:80px">Color</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:42px">Size</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:70px">Qty</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:80px">Tersedia</th>
                    <th class="px-1 py-2" style="width:20px"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, index) in rows" :key="index">
                    <tr
                        class="border-b border-gray-100 dark:border-gray-700"
                        :class="row.exceed ? '' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                        x-bind:style="row.exceed ? 'background-color: rgba(254, 202, 202, 0.35); border-left: 3px solid #dc2626;' : ''"
                    >
                        <td class="px-1 py-1 text-gray-100 dark:text-white text-center text-xs" x-text="index + 1"></td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].barcode"
                                x-on:change="canManageItems && lookupInventory(index)"
                                list="alloc-barcodes"
                                :disabled="!canManageItems"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 disabled:opacity-60 disabled:cursor-not-allowed"
                                placeholder="Barcode..."
                            />
                        </td>

                        <td class="px-1 py-1" style="max-width:120px">
                            <span x-text="row.article" class="text-gray-100 dark:text-white block truncate text-xs" x-bind:title="row.article"></span>
                        </td>

                        <td class="px-1 py-1" style="max-width:85px">
                            <span x-text="row.sku" class="text-gray-100 dark:text-white block truncate text-xs" x-bind:title="row.sku"></span>
                        </td>

                        <td class="px-1 py-1" style="max-width:80px">
                            <span x-text="row.color" class="text-gray-100 dark:text-white block truncate text-xs" x-bind:title="row.color"></span>
                        </td>

                        <td class="px-1 py-1 text-center">
                            <span x-text="row.size" class="text-gray-100 dark:text-white text-xs"></span>
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="number"
                                x-model.number="rows[index].qty"
                                x-on:input="canEditQty && validateQty(index)"
                                min="0"
                                :disabled="!canEditQty"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 text-xs bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 disabled:opacity-60 disabled:cursor-not-allowed"
                            />
                        </td>

                        <td class="px-1 py-1 text-right">
                            <span
                                x-text="row.barcode ? getAvailable(row.barcode) : '—'"
                                class="text-xs font-semibold block"
                                :class="row.exceed ? 'text-red-400' : 'text-gray-100 dark:text-white'"
                            ></span>
                            <template x-if="row.barcode && (stockBreakdown[row.barcode] || []).length > 0">
                                <div class="mt-0.5 space-y-0.5">
                                    <template x-for="s in (stockBreakdown[row.barcode] || [])">
                                        <div
                                            class="text-gray-400 dark:text-gray-500 leading-none"
                                            style="font-size:9px"
                                            x-text="(s.b || '—') + ' - ' + (s.l || '—') + ' = ' + s.q + ' Item'"
                                        ></div>
                                    </template>
                                </div>
                            </template>
                        </td>

                        <td class="px-1 py-1 text-center">
                            <button
                                type="button"
                                x-show="canManageItems"
                                x-on:click="removeRow(index)"
                                class="text-red-400 hover:text-red-600 font-bold text-sm leading-none"
                                title="Hapus"
                            >&times;</button>
                        </td>
                    </tr>
                </template>

                <tr x-show="rows.length === 0">
                    <td colspan="9" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-xs">
                        Belum ada item. Upload file Excel atau klik "+ Tambah Item".
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <datalist id="alloc-barcodes">
        @foreach($inventoryMap as $barcode => $inv)
            <option value="{{ $barcode }}">{{ is_array($inv) ? ($inv['article'] ?? '') : $inv }}</option>
        @endforeach
    </datalist>

</div>
