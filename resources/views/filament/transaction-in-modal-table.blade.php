@php $inventoryMap = $inventoryMap ?? []; @endphp

<script>
    window.__txInventoryMap = @json($inventoryMap);
</script>

<div
    wire:ignore
    x-data="{
        rows: $wire.entangle('transactionRows'),
        inventoryMap: window.__txInventoryMap || {},
        addRow() {
            this.$wire.addTransactionRow();
        },
        removeRow(i) {
            this.$wire.removeTransactionRow(i);
        },
        lookupInventory(i) {
            var b   = (this.rows[i].barcode || '').trim();
            var inv = this.inventoryMap[b] || null;
            var article = inv ? (inv.article || '') : '';
            var sku     = inv ? (inv.sku     || '') : '';
            var color   = inv ? (inv.color   || '') : '';
            var size    = inv ? (inv.size    || '') : '';
            var s = b ? (inv ? 'OK' : 'DECLINED') : 'OK';
            var r = b && !inv ? 'Inventory tidak ditemukan' : '';
            this.rows[i] = Object.assign({}, this.rows[i], {
                article: article, sku: sku, color: color, size: size, status: s, remarks: r
            });
            this.$wire.updateTransactionRowStatus(i, s, r);
        }
    }"
    style="min-width:0; width:100%;"
>

    {{-- Accumulation info banner --}}
    <template x-if="$wire.totalImportRows > 0">
        <div class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20 px-4 py-3 text-sm text-blue-800 dark:text-blue-300">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>
                File mengandung <strong x-text="$wire.totalImportRows"></strong> baris data &rarr;
                <strong x-text="rows.length"></strong> item unik setelah akumulasi qty per barcode.
            </span>
        </div>
    </template>

    <div class="flex items-center justify-between mt-1 mb-4">
        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="rows.length + ' item'"></span>
        <div class="flex items-center gap-2">
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

    <div class="mt-3 border border-gray-200 dark:border-gray-700 rounded-lg" style="overflow-x:auto; overflow-y:auto; max-height:420px; width:100%;">
        <table class="w-full text-xs border-collapse" style="min-width:820px;">
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                <tr class="border-b border-gray-200 dark:border-gray-600">
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:24px">#</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:115px">Barcode</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:110px">Article</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:80px">SKU</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:75px">Color</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:42px">Size</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:50px">Qty</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:72px">Lokasi</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:55px">Bin</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:82px">Status</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:95px">Keterangan</th>
                    <th class="px-1 py-2" style="width:20px"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, index) in rows" :key="index">
                    <tr
                        class="border-b border-gray-100 dark:border-gray-700"
                        :class="row.status === 'DECLINED'
                            ? 'bg-red-50 dark:bg-red-900/20'
                            : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    >
                        <td class="px-1 py-1 text-gray-400 dark:text-gray-500 text-center text-xs" x-text="index + 1"></td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].barcode"
                                x-on:change="lookupInventory(index)"
                                list="trx-barcodes"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 text-xs"
                                placeholder="Barcode..."
                            />
                        </td>

                        <td class="px-1 py-1" style="max-width:110px">
                            <span x-text="row.article" class="text-gray-600 dark:text-gray-300 block truncate text-xs" title="" x-bind:title="row.article"></span>
                        </td>

                        <td class="px-1 py-1" style="max-width:80px">
                            <span x-text="row.sku" class="text-gray-600 dark:text-gray-300 block truncate text-xs" x-bind:title="row.sku"></span>
                        </td>

                        <td class="px-1 py-1" style="max-width:75px">
                            <span x-text="row.color" class="text-gray-600 dark:text-gray-300 block truncate text-xs" x-bind:title="row.color"></span>
                        </td>

                        <td class="px-1 py-1 text-center">
                            <span x-text="row.size" class="text-gray-600 dark:text-gray-300 text-xs"></span>
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="number"
                                x-model.number="rows[index].qty"
                                min="0"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 text-xs"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].location"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 text-xs"
                                placeholder="Lokasi"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].bin"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 text-xs"
                                placeholder="Bin"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <select
                                x-model="rows[index].status"
                                class="w-full border rounded px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400 text-xs"
                                :class="row.status === 'DECLINED'
                                    ? 'border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400'
                                    : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100'"
                            >
                                <option value="OK">OK</option>
                                <option value="DECLINED">DECLINED</option>
                            </select>
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].remarks"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400 text-xs"
                                placeholder="Keterangan"
                            />
                        </td>

                        <td class="px-1 py-1 text-center">
                            <button
                                type="button"
                                x-on:click="removeRow(index)"
                                class="text-red-400 hover:text-red-600 font-bold text-sm leading-none"
                                title="Hapus"
                            >&times;</button>
                        </td>
                    </tr>
                </template>

                <tr x-show="rows.length === 0">
                    <td colspan="12" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500 text-xs">
                        Belum ada item. Upload file Excel atau klik "+ Tambah Item".
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <datalist id="trx-barcodes">
        @foreach($inventoryMap as $barcode => $inv)
            <option value="{{ $barcode }}">{{ is_array($inv) ? ($inv['article'] ?? '') : $inv }}</option>
        @endforeach
    </datalist>

</div>
