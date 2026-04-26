<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px; font-size: 12px; }
    </style>
</head>
<body>

<h3>Master Produk</h3>

<table>
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama</th>
            <th>Brand</th>
            <th>Total Qty</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach($products as $p)
        <tr>
            <td>{{ $p->kode_barang }}</td>
            <td>{{ $p->nama_barang }}</td>
            <td>{{ $p->brand }}</td>
            <td>{{ $p->stocks_sum_qty ?? 0 }}</td>
            <td>{{ $p->price }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
