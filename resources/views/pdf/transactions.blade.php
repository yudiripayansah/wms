<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h3 { margin-bottom: 4px; }
        p.meta { font-size: 10px; color: #555; margin-top: 0; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
        .declined { color: #c00; }
    </style>
</head>
<body>

<h3>{{ $title }}</h3>
<p class="meta">Dicetak: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; Total: {{ count($transactions) }} record</p>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Session ID</th>
            <th>Kode Barang</th>
            <th>Nama Barang</th>
            <th>Qty</th>
            <th>Location</th>
            <th>Box</th>
            <th>Status</th>
            <th>Keterangan</th>
            <th>Tanggal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($transactions as $i => $t)
        <tr class="{{ $t->status === 'DECLINED' ? 'declined' : '' }}">
            <td>{{ $i + 1 }}</td>
            <td>{{ $t->session_id }}</td>
            <td>{{ $t->kode_barang }}</td>
            <td>{{ $t->product?->nama_barang }}</td>
            <td>{{ $t->qty }}</td>
            <td>{{ $t->location }}</td>
            <td>{{ $t->box }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ $t->remarks }}</td>
            <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
