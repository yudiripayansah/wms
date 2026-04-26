@php $productMap = $productMap ?? []; @endphp

<script>
    window.__txProductMap = @json($productMap);

    window.transactionInTable = function (wire) {
        return {
            rows: wire.entangle('transactionRows').defer,
            productMap: window.__txProductMap,

            addRow() {
                this.rows = [
                    ...this.rows,
                    { kode_barang: '', nama_barang: '', qty: 1, location: '', box: '', status: 'OK', remarks: '' }
                ];
            },

            removeRow(index) {
                this.rows = this.rows.filter(function(_, i) { return i !== index; });
            },

            lookupProduct(index) {
                var kode = (this.rows[index].kode_barang || '').trim();
                var nama = this.productMap[kode] || '';
                var rows = this.rows.map(function(r, i) {
                    if (i !== index) return r;
                    return Object.assign({}, r, {
                        nama_barang: nama,
                        status: kode ? (nama ? 'OK' : 'DECLINED') : 'OK',
                        remarks: (kode && !nama) ? 'Produk tidak ditemukan' : '',
                    });
                });
                this.rows = rows;
            }
        };
    };
</script>

<div x-data="transactionInTable($wire)" style="min-width:0; width:100%;">

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
        <div style="min-width:700px;">
        <table class="w-full text-xs border-collapse">
            <thead class="bg-gray-50 sticky top-0 z-10">
                <tr class="border-b border-gray-200">
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:28px">#</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:130px">Kode Barang</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold">Nama Barang</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:56px">Qty</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:80px">Lokasi</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:64px">Box</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:90px">Status</th>
                    <th class="px-1 py-2 text-left text-gray-500 font-semibold" style="width:110px">Keterangan</th>
                    <th class="px-1 py-2" style="width:28px"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, index) in rows" :key="index">
                    <tr
                        class="border-b border-gray-100"
                        :class="row.status === 'DECLINED' ? 'bg-red-50' : 'bg-white hover:bg-gray-50'"
                    >
                        <td class="px-2 py-1 text-gray-400 text-center" x-text="index + 1"></td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].kode_barang"
                                x-on:change="lookupProduct(index)"
                                list="trx-product-codes"
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

                        <td class="px-1 py-1">
                            <select
                                x-model="rows[index].status"
                                class="w-full border rounded px-1 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
                                :class="row.status === 'DECLINED'
                                    ? 'border-red-300 bg-red-50 text-red-700'
                                    : 'border-gray-300 bg-white'"
                            >
                                <option value="OK">OK</option>
                                <option value="DECLINED">DECLINED</option>
                            </select>
                        </td>

                        <td class="px-1 py-1">
                            <input
                                type="text"
                                x-model="rows[index].remarks"
                                class="w-full border border-gray-300 rounded px-1.5 py-0.5 focus:outline-none focus:ring-1 focus:ring-primary-400"
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
                    <td colspan="9" class="px-4 py-10 text-center text-gray-400">
                        Belum ada item. Upload file Excel atau klik "+ Tambah Item".
                    </td>
                </tr>
            </tbody>
        </table>
        </div>{{-- /min-width wrapper --}}
    </div>

    <datalist id="trx-product-codes">
        @foreach($productMap as $kode => $nama)
            <option value="{{ $kode }}">{{ $nama }}</option>
        @endforeach
    </datalist>

</div>
