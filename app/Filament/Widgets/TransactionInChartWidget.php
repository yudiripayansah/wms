<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TransactionInChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Grafik Barang Masuk';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'daily';

    protected function getFilters(): ?array
    {
        return [
            'daily'   => 'Harian (30 hari)',
            'weekly'  => 'Mingguan (12 minggu)',
            'monthly' => 'Bulanan (12 bulan)',
            'yearly'  => 'Tahunan (5 tahun)',
        ];
    }

    protected function getData(): array
    {
        [$labels, $data] = $this->buildChartData('IN');

        return [
            'datasets' => [
                [
                    'label'           => 'Qty Masuk',
                    'data'            => $data,
                    'borderColor'     => '#22c55e',
                    'backgroundColor' => 'rgba(34,197,94,0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function buildChartData(string $type): array
    {
        $query = Transaction::where('type', $type)->where('status', 'OK');

        switch ($this->filter) {
            case 'weekly':
                $labels = [];
                $data   = [];
                for ($i = 11; $i >= 0; $i--) {
                    $start  = Carbon::now()->subWeeks($i)->startOfWeek();
                    $end    = Carbon::now()->subWeeks($i)->endOfWeek();
                    $labels[] = $start->format('d/m');
                    $data[]   = (clone $query)
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('qty');
                }
                break;

            case 'monthly':
                $labels = [];
                $data   = [];
                for ($i = 11; $i >= 0; $i--) {
                    $month    = Carbon::now()->subMonths($i);
                    $labels[] = $month->format('M Y');
                    $data[]   = (clone $query)
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->sum('qty');
                }
                break;

            case 'yearly':
                $labels = [];
                $data   = [];
                for ($i = 4; $i >= 0; $i--) {
                    $year     = Carbon::now()->subYears($i)->year;
                    $labels[] = (string) $year;
                    $data[]   = (clone $query)
                        ->whereYear('created_at', $year)
                        ->sum('qty');
                }
                break;

            default: // daily
                $labels = [];
                $data   = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date     = Carbon::now()->subDays($i)->toDateString();
                    $labels[] = Carbon::parse($date)->format('d/m');
                    $data[]   = (clone $query)
                        ->whereDate('created_at', $date)
                        ->sum('qty');
                }
                break;
        }

        return [$labels, $data];
    }
}
