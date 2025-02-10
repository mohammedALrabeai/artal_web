<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry; // ✅ استخدام Group بدلًا من RepeatableEntry
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRequest extends ViewRecord
{
    protected static string $resource = RequestResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ✅ **القسم الأول: تفاصيل الطلب الأساسية**
                Section::make(__('Request Details'))
                    ->schema([
                        TextEntry::make('type')->label(__('Type')),
                        TextEntry::make('submittedBy.name')->label(__('Submitted By')),
                        TextEntry::make('employee.first_name')->label(__('Employee')),
                        TextEntry::make('status')->label(__('Status'))
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'secondary',
                            }),
                        TextEntry::make('current_approver_role')->label(__('Current Approver Role')),
                        TextEntry::make('description')->label(__('Description')),
                    ]),

                // ✅ **القسم الثاني: تفاصيل الطلب بناءً على النوع**
                Section::make(__('Additional Request Details'))
                    ->schema([
                        // 🔹 **تفاصيل الإجازة**
                        Section::make(__('Leave Details'))
                            ->visible(fn ($record) => $record->type === 'leave' && $record->leave)
                            ->schema([
                                TextEntry::make('leave.start_date')->label(__('Start Date')),
                                TextEntry::make('leave.end_date')->label(__('End Date')),
                                TextEntry::make('leave.type')->label(__('Leave Type')),
                                TextEntry::make('leave.reason')->label(__('Reason')),
                            ]),

                        // 🔹 **تفاصيل الاستبعاد**
                        Section::make(__('Exclusion Details'))
                            ->visible(fn ($record) => $record->type === 'exclusion' && $record->exclusion)
                            ->schema([
                                TextEntry::make('exclusion.reason')->label(__('Exclusion Reason')),
                                TextEntry::make('exclusion.details')->label(__('Details')),
                            ]),
                    ]),

                // ✅ **القسم الثالث: المرفقات باستخدام `Spatie Media Library`**
                Section::make(fn ($record) => sprintf('%s (%d)', __('Attachments'), $record->attachments->count()))
                    ->schema([
                        Group::make()
                            ->state(fn ($record) => $record->attachments) // ✅ جلب المرفقات المرتبطة بالطلب
                            ->columns(2)
                            ->schema(fn ($state) => collect($state)->map(function ($attachment) {
                                $file = $attachment->getFirstMedia('attachments');

                                return Section::make($attachment->title)
                                    ->schema([
                                        TextEntry::make('type')
                                            ->label(__('Type'))
                                            ->state(class_basename($attachment->model_type)), // ✅ عرض نوع الموديل المرتبط

                                        TextEntry::make('preview')
                                            ->label(__('Preview'))
                                            ->html()
                                            ->state(fn () => $file ? match ($file->mime_type) {
                                                'image/png', 'image/jpeg', 'image/gif' => "<a href='{$file->getTemporaryUrl(now()->addMinutes(30))}' target='_blank'>
                                    <img src='{$file->getTemporaryUrl(now()->addMinutes(30))}' width='80' class='rounded shadow' />
                                </a>",
                                                'video/mp4', 'video/mpeg' => "<video width='160' controls>
                                  <source src='{$file->getTemporaryUrl(now()->addMinutes(30))}' type='video/mp4'>
                               </video>",
                                                'application/pdf' => "<a href='{$file->getTemporaryUrl(now()->addMinutes(30))}' target='_blank' class='font-bold text-primary'>
                                 📄 ".__('View PDF').'
                              </a>',
                                                default => "<a href='{$file->getTemporaryUrl(now()->addMinutes(30))}' target='_blank' class='font-bold text-primary'>
                                📂 ".__('Download File').'
                            </a>',
                                            } : '<span class="text-gray-500">'.__('No File Available').'</span>'),

                                        TextEntry::make('expiry_date')
                                            ->label(__('Expiry Date'))
                                            ->state($attachment->expiry_date ?? '-'),

                                        TextEntry::make('notes')
                                            ->label(__('Notes'))
                                            ->state($attachment->notes ?? '-'),
                                    ]);
                            })->toArray()),
                    ])
                    ->visible(fn ($record) => $record->attachments->isNotEmpty()) // ✅ إخفاء القسم إذا لم يكن هناك مرفقات
                // ->badge(fn ($record) => count($record->attachments))
                , // ✅ عرض عدد المرفقات كـ badge

                // ✅ **القسم الرابع: تاريخ الموافقات**
                Section::make(__('Approval History'))
                    ->schema([
                        Group::make()
                            ->state(fn ($record) => $record->approvals) // ✅ جلب الموافقات يدويًا
                            ->columns(2)
                            ->schema(fn ($state) => collect($state)->map(function ($approval) {
                                return Section::make($approval->approver->name)
                                    ->schema([
                                        TextEntry::make('approver_role')->label(__('Role'))->state($approval->approver_role),
                                        TextEntry::make('status')->label(__('Status'))
                                            ->badge()
                                            ->color(match ($approval->status) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'pending' => 'warning',
                                                default => 'secondary',
                                            }),
                                        TextEntry::make('approved_at')->label(__('Approved At'))->dateTime()->state($approval->approved_at),
                                        TextEntry::make('notes')->label(__('Notes'))->state($approval->notes),
                                    ]);
                            })->toArray()),
                    ]),
            ]);
    }
}
