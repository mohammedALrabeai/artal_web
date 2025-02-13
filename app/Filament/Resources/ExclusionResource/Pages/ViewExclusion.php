<?php

namespace App\Filament\Resources\ExclusionResource\Pages;

use App\Filament\Resources\ExclusionResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;

// ✅ الصحيح لاستخدامه داخل Infolist

class ViewExclusion extends ViewRecord
{
    protected static string $resource = ExclusionResource::class;

    public function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('Exclusion Details'))
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label(__('Employee'))
                            ->state(fn ($record) => $record->employee
                                ? "{$record->employee->first_name} {$record->employee->father_name} {$record->employee->family_name} (ID: {$record->employee->id}, National ID: {$record->employee->national_id})"
                                : __('No Employee')),

                        TextEntry::make('type')->label(__('Exclusion Type')),
                        TextEntry::make('exclusion_date')->label(__('Exclusion Date'))->date(),
                        TextEntry::make('reason')->label(__('Reason'))->default('-'),
                        TextEntry::make('notes')->label(__('Notes'))->default('-'),
                        TextEntry::make('status')->label(__('Status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary',
                            }),
                        TextEntry::make('created_at')->label(__('Created At'))->dateTime(),
                        TextEntry::make('updated_at')->label(__('Updated At'))->dateTime(),
                    ]),

                // ✅ **قسم يعرض تفاصيل الطلب المرتبط بالاستبعاد**
                Section::make(__('Related Request'))
                    ->schema([
                        TextEntry::make('request.type')->label(__('Request Type'))->default('-'),
                        TextEntry::make('request.status')->label(__('Request Status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary',
                            })
                            ->default('-'),
                        TextEntry::make('request.submittedBy.name')->label(__('Submitted By'))->default('-'),
                        TextEntry::make('request.description')->label(__('Description'))->default('-'),
                        TextEntry::make('request.current_approver_role')->label(__('Current Approver Role'))->default('-'),
                        TextEntry::make('request.created_at')->label(__('Request Created At'))->dateTime()->default('-'),
                    ])
                    ->hidden(fn ($record) => ! $record->request), // ✅ إخفاء القسم إذا لم يكن هناك طلب مرتبط

                // ✅ قسم سجل الموافقات
                Section::make(__('Approval History'))
                    ->schema([
                        RepeatableEntry::make('request.approvals')
                            ->label(__('Approval History'))
                            ->schema([
                                TextEntry::make('approver.name')->label(__('Approved By'))->default('-'),
                                TextEntry::make('approver_role')->label(__('Role'))->default('-'),
                                TextEntry::make('status')->label(__('Status'))
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'pending' => 'warning',
                                        default => 'secondary',
                                    }),
                                TextEntry::make('notes')->label(__('Comments'))->default('-'),
                                TextEntry::make('approved_at')->label(__('Approval Date'))->dateTime()->default('-'),
                            ]),
                    ])
                    ->hidden(fn ($record) => ! $record->request || $record->request->approvals->isEmpty()), // ✅ إخفاء القسم إذا لم يكن هناك أي موافقات
            ]);
    }
}
