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

                // ✅ **القسم الثالث: المرفقات**
                Section::make(__('Attachments'))
                    ->schema([
                        Group::make()
                            ->state(fn ($record) => $record->attachments) // ✅ جلب المرفقات يدويًا
                            ->columns(2)
                            ->schema(fn ($state) => collect($state)->map(function ($attachment) {
                                return Section::make($attachment->title)
                                    ->schema([
                                        TextEntry::make('type')->label(__('Type'))->state($attachment->type),
                                        TextEntry::make('content_url')
                                            ->label(__('Preview'))
                                            ->html()
                                            ->state(match ($attachment->type) {
                                                'text' => "<p>{$attachment->content}</p>",
                                                'link' => "<a href='{$attachment->content}' target='_blank' class='text-blue-600 underline'>{$attachment->content}</a>",
                                                'image' => "<a href='{$attachment->image_url}' target='_blank'><img src='{$attachment->image_url}' width='80' class='rounded shadow' /></a>",
                                                'video' => "<video width='160' controls><source src='{$attachment->video_url}' type='video/mp4'></video>",
                                                'file' => "<a href='{$attachment->file_url}' target='_blank' class='text-blue-600 underline'>".__('Download File').'</a>',
                                                default => __('Unsupported Format'),
                                            }),
                                        TextEntry::make('expiry_date')->label(__('Expiry Date'))->state($attachment->expiry_date),
                                        TextEntry::make('notes')->label(__('Notes'))->state($attachment->notes),
                                    ]);
                            })->toArray()),
                    ]),

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
