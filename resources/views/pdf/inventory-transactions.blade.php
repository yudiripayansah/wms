<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h3 { margin-bottom: 4px; }
        .meta { font-size: 10px; color: #555; margin-top: 0; margin-bottom: 6px; }
        .inventory-info { font-size: 10px; background: #f5f5f5; padding: 6px 8px; border-radius: 4px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
        .declined { color: #c00; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; }
        .badge-in         { background: #d1fae5; color: #065f46; }
        .badge-out        { background: #fee2e2; color: #991b1b; }
        .badge-opname     { background: #dbeafe; color: #1e40af; }
        .badge-adjustment { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

<h3>{{ $title }}</h3>
<p class="meta">{{ __('pdf.printed_at') }}: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; {{ __('pdf.total_records') }}: {{ count($transactions) }} record</p>

<div class="inventory-info">
    <strong>{{ __('pdf.inventory_info') }}:</strong> {{ $inventory->barcode }} &nbsp;|&nbsp;
    {{ $inventory->article }} &nbsp;|&nbsp;
    {{ __('pdf.brand') }}: {{ $inventory->brand ?? '-' }} &nbsp;|&nbsp;
    {{ __('pdf.color') }}: {{ $inventory->color ?? '-' }} &nbsp;|&nbsp;
    {{ __('pdf.size') }}: {{ $inventory->size ?? '-' }}
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>{{ __('pdf.type') }}</th>
            <th>{{ __('pdf.session_id') }}</th>
            <th>{{ __('pdf.qty') }}</th>
            <th>{{ __('pdf.location') }}</th>
            <th>{{ __('pdf.bin') }}</th>
            <th>{{ __('pdf.status') }}</th>
            <th>{{ __('pdf.remarks') }}</th>
            <th>{{ __('pdf.date') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $i => $t)
        <tr class="{{ $t->status === 'DECLINED' ? 'declined' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>
                <span class="badge badge-{{ strtolower($t->type) }}">{{ $t->type }}</span>
            </td>
            <td>{{ $t->session_id }}</td>
            <td>{{ $t->qty }}</td>
            <td>{{ $t->location }}</td>
            <td>{{ $t->bin }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ $t->remarks }}</td>
            <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
