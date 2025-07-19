<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Forms\Components\EmployeeSelectV2;
use App\Models\Project;
use App\Services\WhatsApp\WhatsAppGroupService;
use App\Services\WhatsApp\WhatsAppMessageService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?int $navigationSort = -10;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationBadge(): ?string
    {
        // ✅ إخفاء العدد عن المستخدمين غير الإداريين
        if (! auth()->user()?->hasRole('admin')) {
            return null;
        }

        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Projects');
    }

    public static function getPluralLabel(): string
    {
        return __('Projects');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Zone & Project Management');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label(__('Name')), // إضافة تسمية مترجمة
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label(__('Description')), // إضافة تسمية مترجمة
                // Forms\Components\TextInput::make('area_id')
                //     ->required()
                //     ->numeric(),
                Forms\Components\Select::make('area_id')
                    ->required()
                    ->options(
                        collect(\App\Models\Area::all())->pluck('name', 'id')
                    )
                    ->placeholder(__('Select Area')) // إضافة تسمية مترجمة
                    ->searchable()
                    ->label(__('Area')), // إضافة تسمية مترجمة
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->label(__('Start Date')), // إضافة تسمية مترجمة
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('End Date')), // إضافة تسمية مترجمة
                Forms\Components\TextInput::make('emp_no')
                    ->label(__('Number of Employees (All shifts included)')) // التسمية موجودة بالفعل
                    ->numeric()
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label(__('Status'))
                    ->default(false)
                    ->afterStateUpdated(function ($state, callable $set, $record) {
                        if ($record && $state === false) {
                            // ✅ تعطيل المواقع المرتبطة بالمشروع
                            foreach ($record->zones as $zone) {
                                $zone->update(['status' => false]);

                                // ✅ تعطيل الورديات داخل كل موقع
                                foreach ($zone->shifts as $shift) {
                                    $shift->update(['status' => false]);
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('تم تعطيل المشروع وجميع المواقع والورديات التابعة له')
                                ->success()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label(__('Name'))
                    ->copyable()
                    ->copyMessageDuration(1500), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('area.name')
                    ->numeric()
                    ->sortable()
                    ->label(__('Area')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label(__('Start Date')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label(__('End Date')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('emp_no')
                    ->label(__('Number of Employees'))
                    ->sortable()
                    // ->state(function ($record) {
                    //     return $record->employeeProjectRecords()->count().' موظف';
                    // })
                    ->state(function ($record) {
                        return $record->emp_no.' موظف';
                    })
                    ->extraAttributes(['class' => 'cursor-pointer text-primary underline'])
                    ->action(
                        Tables\Actions\Action::make('show_employees')
                            ->label('عرض الموظفين')
                            ->modalHeading('الموظفون المسندون للمشروع')
                            ->modalSubmitAction(false)
                            ->modalWidth('4xl')
                            ->action(fn () => null)
                            ->mountUsing(function (Tables\Actions\Action $action, $record) {
                                $employees = \App\Models\EmployeeProjectRecord::with(['employee', 'zone', 'shift'])
                                    ->where('project_id', $record->id)
                                    ->where('status', 1) // ✅ فقط الإسنادات النشطة
                                    ->get()
                                    ->sortBy(fn ($record) => $record->zone->name ?? '');

                                $action->modalContent(view('filament.modals.project-employees', compact('employees')));
                            })
                    ),

                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label(__('Status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('Created At')), // إضافة تسمية مترجمة
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label(__('Updated At')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')
                    ->label(__('Filter by Area'))
                    ->options(
                        \App\Models\Area::pluck('name', 'id')->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->placeholder(__('All Areas')),
            ])
            ->actions([

             

Tables\Actions\Action::make('add_members_to_group')
    ->label('➕ إضافة أعضاء للجروب')
    // ->icon('heroicon-o-user-plus')
    ->color('primary')
    ->visible(fn($record) => $record->has_whatsapp_group && $record->whatsapp_group_id)
    ->form([
        \App\Forms\Components\EmployeeSelectV2::make('employee_ids')
            ->label('اختر الموظفين لإرسال رابط دعوة')
            ->multiple()
            ->required()
    ])
    ->action(function (Project $record, array $data) {
        $groupJid = $record->whatsapp_group_id;

        // جلب أرقام الجوال للموظفين المحددين
        $mobileNumbers = \App\Models\Employee::whereIn('id', $data['employee_ids'])
            ->pluck('mobile_number')
            ->map(fn($num) => preg_replace('/[^0-9]/', '', $num))
            ->filter(fn($num) => strlen($num) >= 10)
            ->values()
            ->toArray();

        if (empty($mobileNumbers)) {
            \Filament\Notifications\Notification::make()
                ->title('لم يتم العثور على أرقام جوال صالحة')
                ->danger()
                ->send();
            return;
        }

        $groupService = new WhatsAppGroupService();
        $messageService = new WhatsAppMessageService();

        // 1. محاولة الإضافة (ولو لن نستفيد من النتيجة مباشرة)
        $groupService->addParticipants($groupJid, $mobileNumbers);

        // 2. جلب رابط الدعوة
        $inviteLink = $groupService->getInviteLink($groupJid);

        if (!$inviteLink) {
            \Filament\Notifications\Notification::make()
                ->title('فشل في جلب رابط الدعوة')
                ->danger()
                ->send();
            return;
        }

        // 3. إرسال رسالة لكل رقم برابط الدعوة
        foreach ($mobileNumbers as $number) {
            $messageService->sendMessage($number, "تمت إضافتك إلى مجموعة المشروع: {$record->name}\nانضم عبر الرابط:\n{$inviteLink}");
        }

        \Filament\Notifications\Notification::make()
            ->title('📤 تم إرسال رابط الدعوة')
            ->body("عدد الرسائل المرسلة: " . count($mobileNumbers))
            ->success()
            ->send();
    }),


                Tables\Actions\Action::make('create_whatsapp_group')
                    ->label('تفعيل جروب واتساب')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->visible(fn ($record) => ! $record->has_whatsapp_group)
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد إنشاء جروب واتساب')
                    ->modalDescription('سيتم إنشاء جروب وإرسال رابط دعوة لمن لم يُضاف تلقائيًا.')
                    ->action(function (Project $record) {
                        $numbers = \App\Models\EmployeeProjectRecord::with('employee')
                            ->where('project_id', $record->id)
                            ->where('status', 1)
                            ->where(function ($q) {
                                $q->whereNull('end_date')
                                    ->orWhere('end_date', '>=', now());
                            })
                            ->get()
                            ->pluck('employee.mobile_number')
                            ->filter()
                            ->unique()
                            ->values()
                            ->toArray();
                        if (count($numbers) === 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('لا يوجد موظفون فعّالون')
                                ->body('لا يمكن إنشاء جروب واتساب بدون موظفين فعّالين.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $groupService = new WhatsAppGroupService;
                        $messageService = new WhatsAppMessageService;
                        $groupName = mb_substr($record->name, 0, 99); // دعم UTF-8

                        $result = $groupService->createGroup($groupName, $numbers);

                        if (! $result) {
                            \Filament\Notifications\Notification::make()
                                ->title('فشل إنشاء الجروب')
                                ->danger()
                                ->send();

                            return;
                        }

                        $groupJid = $result['group_jid'];
                        $participants = $result['participants'];

                        $inviteLink = $groupService->getInviteLink($groupJid);

                        foreach ($participants as $participant) {
                            if (! $participant->added && $inviteLink) {
                                $messageService->sendMessage($participant->phoneNumber, "انضم إلى جروب المشروع عبر الرابط:\n{$inviteLink}");
                            }
                        }

                        $record->update([
                            'has_whatsapp_group' => true,
                            'whatsapp_group_id' => $groupJid,
                            'whatsapp_group_name' => $record->name,
                            'whatsapp_group_created_at' => now(),
                            'whatsapp_created_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('تم إنشاء الجروب بنجاح')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_employees')
                        ->label('تصدير موظفي المشاريع المحددة')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('اختر نوع السجلات')
                                ->options([
                                    'active' => 'الموظفين النشطين فقط',
                                    'all' => 'جميع الموظفين',
                                ])
                                ->default('active')
                                ->required(),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('تاريخ البداية')
                                ->required()
                                ->default(now('Asia/Riyadh')->toDateString()),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $projectIds = $records->pluck('id')->toArray();
                            $onlyActive = $data['status'] === 'active';
                            $startDate = $data['start_date'];

                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\SelectedProjectsEmployeeExport($projectIds, $onlyActive, $startDate),
                                'selected_projects_employees.xlsx'
                            );
                        })
                        ->requiresConfirmation()
                        ->modalHeading('تأكيد التصدير')
                        ->modalDescription('اختر نوع السجلات وتاريخ البداية لتصدير تقرير الموظفين')
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export_pdf')
                        ->label('📄 تصدير PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('primary')
                        ->form([
                            Forms\Components\DatePicker::make('start_date')
                                ->label('تاريخ البداية')
                                ->default(now('Asia/Riyadh')->startOfDay())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            // نحفظ التاريخ والـ ids في session مؤقتًا
                            session()->put('export_pdf_ids', $records->pluck('id')->toArray());
                            session()->put('export_pdf_start_date', $data['start_date']);

                            // لا نُرجع أي شيء هنا، نكتفي بالإشعار
                            \Filament\Notifications\Notification::make()
                                ->title('📄 يمكنك الآن الضغط على زر التصدير')
                                ->success()
                                ->send();
                        })
                        ->after(function () {
                            // نعطي تعليمات للمستخدم ليفتح التبويب بنفسه (لأن Livewire لا يدعم window.open)
                            \Filament\Notifications\Notification::make()
                                ->title('🔗 اضغط هنا لفتح التقرير')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('pdf')
                                        ->label('فتح التقرير')
                                        ->url(route('projects.export.pdf'), shouldOpenInNewTab: true),
                                ])
                                ->send()
                                ->sendToDatabase(Auth::user());
                        }),

                ])
                    ->label('تصدير موظفي المشاريع المحددة'),

                ExportBulkAction::make()
                    ->label(__('Export')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
