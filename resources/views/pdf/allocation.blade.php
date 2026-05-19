<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Allocation #{{ $allocation->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8pt;
            color: #1a1a1a;
            line-height: 1.3;
        }

        @page {
            size: A4 portrait;
            margin: 14mm 12mm 18mm 12mm;
        }

        .doc-header {
            width: 100%;
            border-bottom: 2px solid #374151;
            padding-bottom: 6pt;
            margin-bottom: 8pt;
        }

        .header-table { width: 100%; border-collapse: collapse; }

        .info-grid { font-size: 7.5pt; border-collapse: collapse; }

        .info-label {
            color: #374151;
            font-weight: bold;
            padding: 1.5pt 8pt 1.5pt 0;
            white-space: nowrap;
            vertical-align: top;
            width: 90pt;
        }

        .info-colon { padding: 1.5pt 4pt 1.5pt 0; vertical-align: top; }

        .info-value { color: #111827; padding: 1.5pt 0; vertical-align: top; }

        .alloc-number-cell { text-align: right; vertical-align: top; }

        .alloc-number-value {
            font-size: 48pt;
            font-weight: bold;
            color: #111827;
            line-height: 1;
        }

        .alloc-total-strip { font-size: 7.5pt; color: #6b7280; margin-top: 4pt; }
        .alloc-total-strip strong { font-size: 9pt; color: #111827; }

        .summary-strip {
            background: #f3f4f6;
            border: 1pt solid #e5e7eb;
            padding: 3pt 6pt;
            font-size: 7pt;
            color: #4b5563;
            margin-bottom: 8pt;
        }

        .group-block { margin-bottom: 10pt; page-break-inside: avoid; }

        .group-header {
            background: #374151;
            color: #ffffff;
            padding: 4pt 6pt;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .group-header-key { color: #fde68a; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            border: 0.5pt solid #d1d5db;
        }

        .data-table thead tr { background: #f9fafb; }

        .data-table th {
            padding: 3pt 4pt;
            text-align: left;
            font-size: 6.5pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border: 0.5pt solid #d1d5db;
            white-space: nowrap;
            vertical-align: middle;
        }

        .data-table th.right  { text-align: right; }
        .data-table th.center { text-align: center; }

        .data-table td {
            padding: 2.5pt 4pt;
            border: 0.5pt solid #e5e7eb;
            vertical-align: middle;
        }

        .data-table td.right  { text-align: right; }
        .data-table td.center { text-align: center; }
        .data-table td.mono   { font-family: "DejaVu Sans Mono", monospace; }
        .data-table td.bold   { font-weight: bold; }
        .data-table td.muted  { color: #6b7280; }

        .row-alt { background: #f9fafb; }

        .th-color-size { padding: 0; border: 0.5pt solid #d1d5db; }
        .th-color-size-inner { width: 100%; border-collapse: collapse; }
        .th-color-size-inner td {
            padding: 1.5pt 4pt;
            border: none;
            border-bottom: 0.5pt solid #d1d5db;
            font-size: 6.5pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .th-color-size-inner tr:last-child td { border-bottom: none; }

        .td-color-size { padding: 0; }
        .td-color-size-inner { width: 100%; border-collapse: collapse; }
        .td-color-size-inner td {
            padding: 1.5pt 4pt;
            border: none;
            border-bottom: 0.5pt solid #e5e7eb;
            font-size: 7pt;
        }
        .td-color-size-inner tr:last-child td { border-bottom: none; }

        .subtotal-row { background: #f3f4f6; border-top: 1.5pt solid #9ca3af; }
        .subtotal-row td {
            padding: 3pt 4pt;
            font-size: 7.5pt;
            font-weight: bold;
            color: #374151;
            border: 0.5pt solid #d1d5db;
        }
        .subtotal-label { color: #6b7280; font-size: 6.5pt; text-transform: uppercase; }

        .grand-total {
            background: #1f2937;
            color: #ffffff;
            margin-top: 12pt;
            padding: 6pt 8pt;
        }
        .grand-total-table { width: 100%; border-collapse: collapse; }
        .grand-total-label {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #d1d5db;
        }
        .grand-total-value { text-align: right; font-size: 16pt; font-weight: bold; color: #ffffff; }

        .page-footer {
            position: fixed;
            bottom: 0;
            left: 12mm;
            right: 12mm;
            border-top: 0.5pt solid #d1d5db;
            padding-top: 3pt;
            font-size: 6.5pt;
            color: #9ca3af;
        }
        .page-footer-table { width: 100%; border-collapse: collapse; }
    </style>
</head>
<body>

<div class="page-footer">
    <table class="page-footer-table">
        <tr>
            <td>{{ config('app.name') }} &mdash; {{ __('pdf.allocation_sheet') }}</td>
            <td style="text-align:center;">
                {{ __('pdf.group') }}: <strong>{{ strtoupper($form === 'barcode' ? __('allocation.by_barcode') : __('allocation.by_location')) }}</strong>
            </td>
            <td style="text-align:right;">{{ __('pdf.generated') }}: {{ now()->format('d M Y H:i') }}</td>
        </tr>
    </table>
</div>

<script type="text/php">
    if (isset($pdf)) {
        $font  = $fontMetrics->get_font("DejaVu Sans", "normal");
        $size  = 6.5;
        $color = [0.6, 0.6, 0.6];
        $text  = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $x     = $pdf->get_width()  - 55;
        $y     = $pdf->get_height() - 9;
        $pdf->page_text($x, $y, $text, $font, $size, $color);
    }
</script>

{{-- ── Document Header ──────────────────────────────────────────────────── --}}
<div class="doc-header">
    <table class="header-table">
        <tr>
            <td style="width:65%; vertical-align:top;">
                <table class="info-grid">
                    @foreach([
                        __('allocation.header_date')      => now()->format('l, d-M-Y'),
                        __('allocation.header_dest')      => $allocation->customer,
                        __('allocation.header_tx')        => $allocation->session_id,
                        __('allocation.header_brand')     => $allocation->brand,
                        __('allocation.header_allocator') => $allocation->sales_associate ?? ($allocation->user?->name ?? ''),
                        __('allocation.header_period')    => $allocation->release_date,
                    ] as $label => $value)
                    <tr>
                        <td class="info-label">{{ $label }}</td>
                        <td class="info-colon">:</td>
                        <td class="info-value">{{ $value ?: '' }}</td>
                    </tr>
                    @endforeach
                </table>
            </td>
            <td style="width:35%; vertical-align:top;">
                <div class="alloc-number-cell">
                    <div class="alloc-number-value">{{ $allocation->id }}</div>
                    <div class="alloc-total-strip">
                        {{ __('general.total') }} &nbsp;<strong>{{ $grouped->flatten()->sum('qty') }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Summary strip ──────────────────────────────────────────────────── --}}
<div class="summary-strip">
    {{ $grouped->count() }} {{ $form === 'barcode' ? __('allocation.barcodes_count') : __('allocation.locations_count') }}
    &nbsp;&bull;&nbsp;
    {{ $grouped->flatten()->count() }} {{ __('allocation.items_count') }}
    &nbsp;&bull;&nbsp;
    <strong>{{ $grouped->flatten()->sum('qty') }} {{ __('allocation.total_qty') }}</strong>
    &nbsp;&bull;&nbsp;
    {{ strtoupper($form === 'barcode' ? __('allocation.group_barcode') : __('allocation.group_location')) }}
    @if($allocation->remarks)
        &nbsp;&bull;&nbsp; {{ $allocation->remarks }}
    @endif
</div>

{{-- ── Grouped Tables ─────────────────────────────────────────────────── --}}
@foreach($grouped as $groupKey => $items)
<div class="group-block">

    <div class="group-header">
        {{ $form === 'barcode' ? __('allocation.barcode_label') : __('allocation.location_label') }}:
        <span class="group-header-key">{{ $groupKey }}</span>
        &nbsp;&mdash;&nbsp;{{ $items->count() }} {{ __('allocation.items_count') }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width:18pt;" class="right">#</th>
                <th style="width:70pt;">{{ __('pdf.barcode') }}</th>
                <th>{{ __('pdf.article') }}</th>
                <th style="width:55pt;" class="th-color-size">
                    <table class="th-color-size-inner">
                        <tr><td>{{ __('pdf.color') }}</td></tr>
                        <tr><td>{{ __('pdf.size') }}</td></tr>
                    </table>
                </th>
                <th style="width:28pt;" class="center">{{ __('pdf.bin') }}</th>
                <th style="width:44pt;" class="center">{{ __('pdf.location') }}</th>
                <th style="width:24pt;" class="right">{{ __('pdf.qty') }}</th>
            </tr>
        </thead>
        <tbody>
            @php $groupNo = 1; $groupQty = 0; @endphp
            @foreach($items as $idx => $item)
            @php
                $inv       = $item->inventory;
                $groupQty += (int) $item->qty;
            @endphp
            <tr class="{{ $idx % 2 === 1 ? 'row-alt' : '' }}">
                <td class="right muted">{{ $groupNo++ }}</td>
                <td class="mono">{{ $item->barcode }}</td>
                <td class="bold">{{ $inv?->article ?: '—' }}</td>
                <td class="td-color-size">
                    <table class="td-color-size-inner">
                        <tr><td>{{ $inv?->color ?: '—' }}</td></tr>
                        <tr><td>{{ $inv?->size  ?: '—' }}</td></tr>
                    </table>
                </td>
                <td class="center">{{ $item->bin ?: '—' }}</td>
                <td class="center">{{ $item->location ?: '—' }}</td>
                <td class="right bold">{{ $item->qty }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="5">
                    <span class="subtotal-label">
                        {{ $form === 'barcode' ? __('allocation.total_barcode') : __('allocation.total_location') }}
                    </span>
                    &nbsp;{{ $groupKey }}
                </td>
                <td class="right" style="font-size:9pt; color:#1f2937; border:0.5pt solid #d1d5db;">{{ $groupQty }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

</div>
@endforeach

{{-- ── Grand Total ──────────────────────────────────────────────────────── --}}
<div class="grand-total">
    <table class="grand-total-table">
        <tr>
            <td class="grand-total-label">{{ __('general.grand_total') }}</td>
            <td class="grand-total-value">{{ $grouped->flatten()->sum('qty') }}</td>
        </tr>
    </table>
</div>

</body>
</html>
