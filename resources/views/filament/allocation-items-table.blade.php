@php
    $productMap = $productMap ?? [];
    $stockMap   = $stockMap   ?? [];
@endphp

<script>
    window.__allocProductMap = @json($productMap);
    window.__allocStockMap   = @json($stockMap);

    window.allocationItemsTable = function (wire) {
        return {
            rows:       wire.entangle('allocationRows').defer,
            productMap: window.__allocProductMap,
            stockMap:   window.__allocStockMap,

            addRow() {
                this.rows = [
                    ...this.rows,
                    { kode_barang: '', nama_barang: '', qty: 0, location: '', box: '' }
                ];
            },

            removeRow(index) {
                this.rows = this.rows.filter(function(_, i) { return i !== index; });
            },

            lookupProduct(index) {
                var kode  = (this.rows[index].kode_barang || '').trim();
                var nama  = this.productMap[kode] || '';
                var stock = this.stockMap[kode]   || {};
                var rows  = this.rows.map(function(r, i) {
                    if (i !== index) return r;
                    return Object.assign({}, r, {
                        nama_barang: nama,
                        location:    stock.location || '',
                        box:         stock.box      || '',
                    });
                });
                this.rows = rows;
            }
        };
    };
</script>

<div x-data="allocationItemsTable($wire)" style="min-width:0; width:100%;">

    <div class="flex items-center justify-between mb-3">
        <span class="text-sm text-gray-500" x-text="rows.length + ' item'"></span>
        <button
            type="button"
            x-on:click="addRow()"
            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold bg-primary-600 text-white rounded-lg hover:bg-primary-700 focus:outline-none"
        >
            + Tambah Item
        </button>
    </div>

    <div class="border border-gray-200 rounded-lg" style="overflow-x:auto; overflow-y:auto; max-height:460px; width:100%;">
        <div style="min-width:600px;">
        <table class="w-full text-xs border-collapse">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr class="border-b border-gray-200">
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:28px">#</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:140px">Kode Barang</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold">Nama Barang</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:60px">Qty</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:90px">Lokasi</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:70px">Box</th>
                    <th class="px-1 py-2" style="width:28px"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, index) in rows" :key="index">
                    <tr class="border-b border-gray-100 bg-white hover:bg-gray-50">
                        <td class="px-2 py-1 text-gray-400 text-center" x-text="index + 1"></td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].kode_barang"
                                x-on:change="lookupProduct(index)"
                                list="alloc-product-codes"
                                class="w-full border border-gray-300 rounded px-1.5 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Kode..."
                            />
                        </td>

                        <td class="px-2 py-1 max-w-xs">
                            <span x-text="row.nama_barang" class="text-gray-600 block truncate"></span>
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="number"
                                x-model.number="rows[index].qty"
                                min="0"
                                class="w-full border border-gray-300 rounded px-1.5 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].location"
                                class="w-full border border-gray-300 rounded px-1.5 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Lokasi"
                            />
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].box"
                                class="w-full border border-gray-300 rounded px-1.5 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                placeholder="Box"
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
                    <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                        Belum ada item. Upload file Excel atau klik "+ Tambah Item".
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>

    <datalist id="alloc-product-codes">
        @foreach($productMap as $kode => $nama)
            <option value="{{ $kode }}">{{ $nama }}</option>
        @endforeach
    </datalist>

</div>
