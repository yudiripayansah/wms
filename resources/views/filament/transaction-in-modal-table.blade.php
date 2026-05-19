@php $inventoryMap = $inventoryMap ?? []; @endphp

<script>
    window.__txInventoryMap = @json($inventoryMap);

    window.transactionInTable = function (wire) {
        return {
            rows:         wire.entangle('transactionRows'),
            inventoryMap: window.__txInventoryMap,

            addRow() {
                this.rows = [
                    ...this.rows,
                    { barcode: '', article: '', qty: 1, location: '', bin: '', status: 'OK', remarks: '' }
                ];
            },

            removeRow(index) {
                this.rows = this.rows.filter(function(_, i) { return i !== index; });
            },

            lookupInventory(index) {
                var barcode = (this.rows[index].barcode || '').trim();
                var article = this.inventoryMap[barcode] || '';
                var rows    = this.rows.map(function(r, i) {
                    if (i !== index) return r;
                    return Object.assign({}, r, {
                        article: article,
                        status:  barcode ? (article ? 'OK' : 'DECLINED') : 'OK',
                        remarks: (barcode && !article) ? 'Inventory tidak ditemukan' : '',
                    });
                });
                this.rows = rows;
            }
        };
    };
</script>

<div x-data="transactionInTable($wire)" style="min-width:0; width:100%;">

    <div class="flex items-center justify-between mb-3">
        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="rows.length + ' item'"></span>
        <button
            type="button"
            x-on:click="addRow()"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:outline-none"
        >
            + Tambah Item
        </button>
    </div>

    <div class="border border-gray-200 dark:border-gray-700 rounded-lg" style="overflow-x:auto; overflow-y:auto; max-height:460px; width:100%;">
        <div style="min-width:700px;">
        <table class="w-full text-xs border-collapse">
            <thead class="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                <tr class="border-b border-gray-200 dark:border-gray-600">
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:28px">#</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:130px">Barcode</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold">Article</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:56px">Qty</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:80px">Lokasi</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:64px">Bin</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:90px">Status</th>
                    <th class="px-1 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold" style="width:110px">Keterangan</th>
                    <th class="px-1 py-2" style="width:28px"></th>
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
                        <td class="px-2 py-1 text-gray-400 dark:text-gray-500 text-center" x-text="index + 1"></td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].barcode"
                                x-on:change="lookupInventory(index)"
                                list="trx-barcodes"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1.5 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Barcode..."
                            />
                        </td>

                        <td class="px-2 py-1 max-w-xs">
                            <span x-text="row.article" class="text-gray-600 dark:text-gray-300 block truncate"></span>
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="number"
                                x-model.number="rows[index].qty"
                                min="0"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1.5 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].location"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1.5 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Lokasi"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].bin"
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1.5 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Bin"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <select
                                x-model="rows[index].status"
                                class="w-full border rounded px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
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
                                class="w-full border border-gray-300 dark:border-gray-600 rounded px-1.5 py-0.5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Keterangan"
                            />
                        </td>

                        <td class="px-1 py-1 text-center">
                            <button
                                type="button"
                                x-on:click="removeRow(index)"
                                class="text-red-400 hover:text-red-600 font-bold text-base leading-none px-1"
                                title="Hapus"
                            >&times;</button>
                        </td>
                    </tr>
                </template>

                <tr x-show="rows.length === 0">
                    <td colspan="9" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                        Belum ada item. Upload file Excel atau klik "+ Tambah Item".
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <datalist id="trx-barcodes">
        @foreach($inventoryMap as $barcode => $article)
            <option value="{{ $barcode }}">{{ $article }}</option>
        @endforeach
    </datalist>

</div>
