<?php

namespace App\Exports;

use App\Models\Coverage;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CoveragesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected ?string $from;
    protected ?string $to;

    public function __construct(?string $from, ?string $to)
    {
        $this->from = $from;
        $this->to   = $to;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Date',
            'Status',
            'Zone',
            'Covering Employee (Full Name)',
            'Covering Employee National ID',
            'Absent Employee (Full Name)',
            'Absent Employee National ID',
            'Added By',
            'Created At',
            'Updated At',
        ];
    }

    public function collection(): Collection
    {
        return Coverage::query()
            ->with(['employee', 'absentEmployee', 'zone', 'addedBy'])
            ->when($this->from, fn ($q) => $q->whereDate('date', '>=', $this->from))
            ->when($this->to,   fn ($q) => $q->whereDate('date', '<=', $this->to))
            ->orderBy('date')
            ->get();
    }

    public function map($record): array
    {
        $fullName = function ($emp) {
            return collect([
                $emp?->first_name,
                $emp?->father_name,
                $emp?->grandfather_name,
                $emp?->family_name,
            ])->filter()->join(' ') ?: 'Not Assigned';
        };

        $tz = 'Asia/Riyadh';

        return [
            $record->id,
            optional($record->date)->format('Y-m-d'),
            $record->status,
            $record->zone?->name ?? 'Not Assigned',

            $fullName($record->employee),
            $record->employee?->national_id ?? 'Not Assigned',

            $fullName($record->absentEmployee),
            $record->absentEmployee?->national_id ?? 'Not Assigned',

            $record->addedBy?->name ?? '—',
            optional($record->created_at)?->timezone($tz)?->format('Y-m-d H:i'),
            optional($record->updated_at)?->timezone($tz)?->format('Y-m-d H:i'),
        ];
    }
}
