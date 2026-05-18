@php
    $rows = $getState() ?? [];
@endphp
<div class="p-2 text-sm text-gray-600 dark:text-gray-300">
    Preview {{ count($rows) }} Data
</div>
<div class="overflow-auto border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800" style="max-height: 500px; overflow-y: auto;">
    <table class="w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0">
            <tr>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">No</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Kode</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Nama</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Qty</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Location</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Box</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Status</th>
                <th class="p-2 text-gray-600 dark:text-gray-300 text-left">Remarks</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($rows as $row)
            <tr class="{{ $row['status'] === 'DECLINED' ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                <td class="p-2 text-gray-700 dark:text-gray-300">{{ $loop->iteration }}</td>
                <td class="p-2 text-gray-700 dark:text-gray-300">{{ $row['kode_barang'] }}</td>
                <td class="p-2 text-gray-700 dark:text-gray-300">{{ $row['nama_barang'] }}</td>
                <td class="p-2">
                    <input type="number"
                        value="{{ $row['qty'] }}"
                        class="border border-gray-300 dark:border-gray-600 rounded p-1 w-20 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </td>
                <td class="p-2">
                    <input type="text"
                        value="{{ $row['location'] }}"
                        class="border border-gray-300 dark:border-gray-600 rounded p-1 w-32 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </td>
                <td class="p-2">
                    <input type="text"
                        value="{{ $row['box'] }}"
                        class="border border-gray-300 dark:border-gray-600 rounded p-1 w-20 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </td>
                <td class="p-2">
                    <select class="border border-gray-300 dark:border-gray-600 rounded p-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="OK">OK</option>
                        <option value="DECLINED">DECLINED</option>
                    </select>
                </td>
                <td class="p-2">
                    <input type="text"
                        value="{{ $row['remarks'] }}"
                        class="border border-gray-300 dark:border-gray-600 rounded p-1 w-40 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
