<?php
namespace App\Filament\Widgets;

use Filament\Widgets\PieChartWidget;

class ZoneReportWidget extends PieChartWidget
{
    protected static ?string $heading = null;

    public function getHeading(): ?string
    {
        return __('Zones Report');
    }

    protected function getData(): array
    {
        $zones = \App\Models\Zone::withCount('employees')->get();

        return [
            'datasets' => [
                [
                    'label' => __('Number of Employees by Zone'),
                    'data' => $zones->pluck('employees_count')->toArray(),
                    'backgroundColor' => $this->getColors($zones->count()), 
                ],
            ],
            'labels' => $zones->pluck('name')->toArray(), // تأكد من تمرير أسماء المناطق
        ];
    }
    private function getColors($count): array
    {
        // قائمة الألوان المخصصة
        $colors = [
            '#FFB6C1', // وردي فاتح
            '#ADD8E6', // أزرق فاتح
            '#90EE90', // أخضر فاتح
            '#FFDAB9', // خوخي
            '#E6E6FA', // بنفسجي فاتح
            '#FFFACD', // أصفر فاتح
            '#F5DEB3', // بني فاتح
            '#D8BFD8', // موف فاتح
        ];

        // إذا كانت المناطق أكثر من عدد الألوان، كرر الألوان
        return array_slice(array_merge($colors, $colors), 0, $count);
    }
    
}
