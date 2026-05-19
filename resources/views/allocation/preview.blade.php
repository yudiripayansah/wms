<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocation {{ $allocation->session_id }} — {{ $form === 'barcode' ? __('allocation.by_barcode') : __('allocation.by_location') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap');
        body { font-family: 'JetBrains Mono', monospace, sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .page-wrapper { padding: 0 !important; }
            .card { box-shadow: none !important; border: none !important; }
            .group-block { page-break-inside: avoid; }
            @page { margin: 15mm 12mm; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900">

{{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
<div class="no-print sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm px-6 py-3 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <span class="text-sm font-semibold text-gray-700">Allocation #{{ $allocation->id }}</span>
        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
            {{ $allocation->status === 'COMPLETED' ? 'bg-green-100 text-green-800' :
               ($allocation->status === 'FINISHED'   ? 'bg-blue-100 text-blue-800' :
               ($allocation->status === 'PROCESSING' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700')) }}">
            {{ $allocation->status }}
        </span>
    </div>

    <div class="flex items-center gap-2">
        <a href="{{ route('allocation.preview', [$allocation, 'location']) }}"
           class="px-3 py-1.5 rounded text-xs font-semibold border
                  {{ $form === 'location' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            {{ __('allocation.by_location') }}
        </a>
        <a href="{{ route('allocation.preview', [$allocation, 'barcode']) }}"
           class="px-3 py-1.5 rounded text-xs font-semibold border
                  {{ $form === 'barcode' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
            {{ __('allocation.by_barcode') }}
        </a>
    </div>

    <div class="flex items-center gap-2">
        <button onclick="window.print()"
                class="px-3 py-1.5 rounded text-xs font-semibold bg-gray-700 text-white hover:bg-gray-800">
            {{ __('general.print') }}
        </button>
        <a href="{{ route('allocation.pdf', [$allocation, $form]) }}" target="_blank"
           class="px-3 py-1.5 rounded text-xs font-semibold bg-red-600 text-white hover:bg-red-700">
            {{ __('general.download_pdf') }}
        </a>
    </div>
</div>

{{-- ── Document ─────────────────────────────────────────────────────────── --}}
<div class="page-wrapper max-w-5xl mx-auto px-4 py-6">
<div class="card bg-white shadow rounded-lg overflow-hidden">

    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="px-6 py-5 border-b border-gray-200">
        <div class="flex items-start justify-between gap-8">

            {{-- Left: shipment info --}}
            <div class="text-xs space-y-0.5">
                @foreach([
                    __('allocation.header_date')      => now()->format('l, d-M-Y'),
                    __('allocation.header_dest')      => $allocation->customer,
                    __('allocation.header_tx')        => $allocation->session_id,
                    __('allocation.header_brand')     => $allocation->brand,
                    __('allocation.header_allocator') => $allocation->sales_associate ?? ($allocation->user?->name ?? ''),
                    __('allocation.header_period')    => $allocation->release_date,
                ] as $label => $value)
                <div class="flex gap-2">
                    <span class="text-gray-500 font-medium w-32 shrink-0">{{ $label }}</span>
                    <span class="text-gray-400">:</span>
                    <span class="text-gray-900 font-semibold">{{ $value ?: '' }}</span>
                </div>
                @endforeach
            </div>

            {{-- Right: large allocation number --}}
            <div class="text-right shrink-0">
                <div class="text-6xl font-bold text-gray-900 tabular-nums leading-none">
                    {{ $allocation->id }}
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    {{ __('general.total') }} &nbsp;
                    <span class="font-bold text-gray-900 text-sm">{{ $grouped->flatten()->sum('qty') }}</span>
                </div>
            </div>
        </div>

        @if($allocation->remarks)
            <p class="mt-3 text-xs text-gray-500 italic">{{ __('allocation.remarks') }}: {{ $allocation->remarks }}</p>
        @endif
    </div>

    {{-- ── Summary strip ─────────────────────────────────────────────────── --}}
    <div class="px-6 py-2 bg-gray-50 border-b border-gray-200 flex gap-6 text-xs text-gray-600">
        <span>
            <strong class="text-gray-800">{{ $grouped->count() }}</strong>
            {{ $form === 'barcode' ? __('allocation.barcodes_count') : __('allocation.locations_count') }}
        </span>
        <span><strong class="text-gray-800">{{ $grouped->flatten()->count() }}</strong> {{ __('allocation.items_count') }}</span>
        <span><strong class="text-gray-800">{{ $grouped->flatten()->sum('qty') }}</strong> {{ __('allocation.total_qty') }}</span>
        <span class="ml-auto font-semibold">{{ $form === 'barcode' ? __('allocation.group_barcode') : __('allocation.group_location') }}</span>
    </div>

    {{-- ── Grouped Tables ─────────────────────────────────────────────────── --}}
    <div class="divide-y divide-gray-200">

        @foreach($grouped as $groupKey => $items)
        <div class="group-block">
            <div class="px-6 py-2 bg-gray-700 text-white flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-widest">
                    {{ $form === 'barcode' ? __('allocation.barcode_label') : __('allocation.location_label') }}:
                    <span class="text-yellow-300 ml-1">{{ $groupKey }}</span>
                </span>
                <span class="text-xs text-gray-300">{{ $items->count() }} {{ __('allocation.items_count') }}</span>
            </div>

            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase tracking-wide text-[10px]">
                        <th class="px-3 py-2 text-right border-b border-gray-200 w-8">#</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200">{{ __('pdf.barcode') }}</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200">{{ __('pdf.article') }}</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200">{{ __('pdf.color') }}</th>
                        <th class="px-3 py-2 text-center border-b border-gray-200 w-14">{{ __('pdf.size') }}</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200 w-14">{{ __('pdf.bin') }}</th>
                        <th class="px-3 py-2 text-left border-b border-gray-200">{{ __('pdf.location') }}</th>
                        <th class="px-3 py-2 text-right border-b border-gray-200 w-14">{{ __('pdf.qty') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $groupNo = 1; $groupQty = 0; @endphp

                    @foreach($items as $item)
                    @php
                        $inv = $item->inventory;
                        $groupQty += (int) $item->qty;
                    @endphp
                    <tr class="hover:bg-blue-50">
                        <td class="px-3 py-1.5 text-right text-gray-400">{{ $groupNo++ }}</td>
                        <td class="px-3 py-1.5 text-gray-700 font-mono">{{ $item->barcode }}</td>
                        <td class="px-3 py-1.5 text-gray-800 font-semibold">{{ $inv?->article ?: '—' }}</td>
                        <td class="px-3 py-1.5 text-gray-700">{{ $inv?->color ?: '—' }}</td>
                        <td class="px-3 py-1.5 text-center text-gray-700">{{ $inv?->size ?: '—' }}</td>
                        <td class="px-3 py-1.5 text-gray-700">{{ $item->bin ?: '—' }}</td>
                        <td class="px-3 py-1.5 text-gray-700">{{ $item->location ?: '—' }}</td>
                        <td class="px-3 py-1.5 text-right font-bold text-gray-900">{{ $item->qty }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-300">
                        <td colspan="6" class="px-3 py-2 text-xs font-bold text-gray-600 uppercase">
                            {{ $form === 'barcode' ? __('allocation.total_barcode') : __('allocation.total_location') }}
                            <span class="text-yellow-700 ml-1">{{ $groupKey }}</span>
                        </td>
                        <td class="px-3 py-2 text-right font-bold text-gray-900 text-sm">
                            {{ $groupQty }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endforeach
    </div>

    {{-- ── Grand total ────────────────────────────────────────────────────── --}}
    <div class="px-6 py-4 bg-gray-800 text-white flex items-center justify-between">
        <span class="text-sm font-bold uppercase tracking-widest">{{ __('general.grand_total') }}</span>
        <span class="text-2xl font-bold tabular-nums">{{ $grouped->flatten()->sum('qty') }}</span>
    </div>

</div>
</div>

</body>
</html>
