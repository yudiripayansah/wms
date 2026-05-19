<!DOCTYPE html>
<html>
<head>
    <title>{{ __('pdf.inventory_list') }}</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
        th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>

<h3>{{ __('pdf.inventory_list') }}</h3>

<table>
    <thead>
        <tr>
            <th>{{ __('pdf.barcode') }}</th>
            <th>{{ __('pdf.brand') }}</th>
            <th>{{ __('pdf.sku') }}</th>
            <th>{{ __('pdf.article') }}</th>
            <th>{{ __('pdf.color') }}</th>
            <th>{{ __('pdf.size') }}</th>
            <th>{{ __('pdf.total_qty') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inventories as $inv)
        <tr>
            <td>{{ $inv->barcode }}</td>
            <td>{{ $inv->brand }}</td>
            <td>{{ $inv->sku }}</td>
            <td>{{ $inv->article }}</td>
            <td>{{ $inv->color }}</td>
            <td>{{ $inv->size }}</td>
            <td>{{ $inv->stocks_sum_qty ?? 0 }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
