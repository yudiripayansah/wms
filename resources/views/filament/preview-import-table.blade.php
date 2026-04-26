@php
    $rows = $getState() ?? [];
@endphp
<div class="p-2">
    Preview {{count($rows)}} Data
</div>
<div class="overflow-auto border rounded" style="max-height: 500px;overflow-y: auto;">

    <table class="w-full text-sm">
        <thead class="bg-gray-100 sticky top-0">
            <tr>
                <th class="p-2">No</th>
                <th class="p-2">Kode</th>
                <th class="p-2">Nama</th>
                <th class="p-2">Qty</th>
                <th class="p-2">Location</th>
                <th class="p-2">Box</th>
                <th class="p-2">Status</th>
                <th class="p-2">Remarks</th>
            </tr>
        </thead>

        <tbody>

            @foreach($rows as $row)
            <tr class="{{ $row['status'] === 'DECLINED' ? 'bg-red-100' : '' }}">

                <td class="p-2">
                    {{ $loop->iteration }}
                </td>
                <td class="p-2">
                    {{ $row['kode_barang'] }}
                </td>

                <td class="p-2">
                    {{ $row['nama_barang'] }}
                </td>

                <td class="p-2">
                    <input type="number"
                        value="{{ $row['qty'] }}"
                        class="border rounded p-1 w-20">
                </td>

                <td class="p-2">
                    <input type="text"
                        value="{{ $row['location'] }}"
                        class="border rounded p-1 w-32">
                </td>

                <td class="p-2">
                    <input type="text"
                        value="{{ $row['box'] }}"
                        class="border rounded p-1 w-20">
                </td>

                <td class="p-2">
                    <select
                        class="border rounded p-1">
                        <option value="OK">OK</option>
                        <option value="DECLINED">DECLINED</option>
                    </select>
                </td>

                <td class="p-2">
                    <input type="text"
                        value="{{ $row['remarks'] }}"
                        class="border rounded p-1 w-40">
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>

</div>