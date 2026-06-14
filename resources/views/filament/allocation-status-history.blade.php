@if($histories->isEmpty())
    <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('allocation.history_empty') }}</p>
@else
<div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full text-xs border-collapse">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr class="border-b border-gray-200 dark:border-gray-600">
                <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold">Waktu</th>
                <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold">Oleh</th>
                <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold">Dari</th>
                <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold">Ke</th>
                <th class="px-3 py-2 text-left text-gray-500 dark:text-gray-300 font-semibold">Catatan</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($histories as $h)
            <tr class="bg-white dark:bg-gray-800">
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    {{ $h->created_at->format('d/m/Y H:i') }}
                </td>
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium">
                    {{ $h->user?->name ?? '—' }}
                </td>
                <td class="px-3 py-2">
                    @if($h->from_status)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ match($h->from_status) {
                            'PENDING'    => 'bg-gray-200 text-gray-800 dark:bg-gray-500 dark:text-white',
                            'PROCESSING' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                            'FINISHED'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'COMPLETED'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'CANCELED'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            default      => 'bg-gray-200 text-gray-800 dark:bg-gray-500 dark:text-white',
                        } }}">
                        {{ __('allocation.status.' . strtolower($h->from_status)) }}
                    </span>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">—</span>
                    @endif
                </td>
                <td class="px-3 py-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ match($h->to_status) {
                            'PENDING'    => 'bg-gray-200 text-gray-800 dark:bg-gray-500 dark:text-white',
                            'PROCESSING' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                            'FINISHED'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'COMPLETED'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'CANCELED'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            default      => 'bg-gray-200 text-gray-800 dark:bg-gray-500 dark:text-white',
                        } }}">
                        {{ __('allocation.status.' . strtolower($h->to_status)) }}
                    </span>
                </td>
                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                    {{ $h->notes ?? '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
