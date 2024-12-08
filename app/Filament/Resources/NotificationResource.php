<?php
namespace App\Filament\Resources;

use App\Models\Notification;
use App\Models\User; // لاستعراض المستخدمين لإرسال الإشعارات
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\MultiSelect;

use Filament\Tables\Filters\SelectFilter;

use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\NotificationResource\Pages;



use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationGroup = 'الإدارة';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Textarea::make('data.message')
                    ->label('الرسالة')
                    ->required(),
                TextInput::make('data.type')
                    ->label('النوع')
                    ->required(),
                MultiSelect::make('user_ids')
                    ->label('المستخدمون')
                    ->relationship('notifiable', 'name') // قم بتغيير الحقل حسب اسم العلاقة
                    ->placeholder('اختر المستخدمين'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data->message')
                    ->label('الرسالة')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data->type')
                    ->label('النوع'),
                Tables\Columns\BooleanColumn::make('read_at')
                    ->label('مقروء؟')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime(),
            ])
            ->filters([
                // SelectFilter::make('read')
                // ->label('حالة القراءة')
                // ->options([
                //     'unread' => 'غير مقروء',
                //     'read' => 'مقروء',
                // ])
                // ->query(function (Builder $query, ?string $value): Builder {
                //     if ($value === 'unread') {
                //         return $query->whereNull('read_at');
                //     }

                //     if ($value === 'read') {
                //         return $query->whereNotNull('read_at');
                //     }

                //     return $query;
                // }),
            ])
            ->actions([
                Action::make('sendNotification')
                    ->label('إرسال إشعار')
                    ->form([
                        Textarea::make('message')
                            ->label('نص الإشعار')
                            ->required(),
                        TextInput::make('type')
                            ->label('النوع')
                            ->required(),
                        MultiSelect::make('user_ids')
                            ->label('اختر المستخدمين')
                            ->options(User::all()->pluck('name', 'id')->toArray()), // استرجاع المستخدمين
                    ])
                    ->action(function (array $data) {
                        $users = User::whereIn('id', $data['user_ids'])->get();
            
                        foreach ($users as $user) {
                            $user->notify(new \App\Notifications\UserSpecificNotification(
                                $data['message'], // الرسالة
                                $data['type']     // النوع
                            ));
                        }
                    })
                    ->icon('heroicon-o-paper-airplane'),
                    Action::make('markAsRead')
                    ->label('تعيين كمقروء')
                    ->action(function (Notification $record) {
                        $record->update(['read_at' => now()]);
                    })
                    ->icon('heroicon-o-check-circle'),
            ])
            
          
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
