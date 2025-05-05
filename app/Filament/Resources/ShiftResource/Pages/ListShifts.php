<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use App\Models\Shift;
use App\Services\NotificationService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ExportAction::make()
                ->visible(fn () => auth()->user()?->hasRole('super_admin')),
            Action::make('adjustLastEntryTimeGlobally')
                ->label('تعديل وقت الدخول لكل الورديات')
                ->icon('heroicon-o-clock')
                ->form([
                    TextInput::make('minutes')
                        ->label('عدد الدقائق (+ أو -)')
                        ->numeric()
                        ->required(),
                ])
                ->requiresConfirmation()
                ->action(function (array $data) {
                    $delta = (int) $data['minutes'];
                    $editedBy = auth()->user()?->name ?? 'غير معروف';
                    $count = 0;

                    DB::beginTransaction();

                    try {
                        \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($delta, &$count) {
                            Shift::query()->chunkById(200, function ($shifts) use ($delta, &$count) {
                                foreach ($shifts as $shift) {
                                    $old = $shift->last_entry_time;
                                    $new = max(0, $old + $delta);

                                    Log::info("✅ Shift Updated [ID: {$shift->id}] - {$shift->name} | {$old} → {$new} mins");

                                    $shift->last_entry_time = $new;
                                    $shift->save(); // ✅ يتم الحفظ بدون إطلاق أي event أو notification
                                    $count++;
                                }
                            });
                        });

                        DB::commit();

                        // ✅ إشعار عام فقط
                        $notificationService = new NotificationService;
                        $notificationService->sendNotification(
                            ['manager', 'general_manager', 'hr'],
                            '🛠️ تعديل وقت الدخول للورديات',
                            "تم تعديل وقت الدخول لـ {$count} وردية بنجاح\n"
                            ."القيمة المدخلة: {$delta} دقيقة\n"
                            ."تم التعديل بواسطة: {$editedBy}",
                            [
                                $notificationService->createAction('عرض الورديات', '/admin/shifts', 'heroicon-s-clock'),
                            ]
                        );

                        // notify()->success("تم تعديل وقت الدخول لـ {$count} وردية بنجاح.");
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Log::error('❌ فشل تعديل أوقات الورديات: '.$e->getMessage());
                        // notify()->danger('حدث خطأ أثناء التعديل: '.$e->getMessage());
                    }
                })

                ->color('warning'),

        ];
    }
}
