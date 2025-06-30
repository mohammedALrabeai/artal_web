<?php

namespace App\Filament\Resources\ShiftResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ShiftRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'activities'; // اسم افتراضي

    protected static ?string $title = 'سجل التعديلات';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return Activity::query()
                    ->where('subject_type', \App\Models\Shift::class)
                    ->where('subject_id', $this->ownerRecord->id)
                    ->latest();
            })
            ->columns([
                Tables\Columns\TextColumn::make('description')->label('الوصف'),

                Tables\Columns\TextColumn::make('causer.name')->label('بواسطة'),

                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime(),



                Tables\Columns\TextColumn::make('new_values')
                    ->label('القيم الجديدة')
                    ->html()
                    ->getStateUsing(function ($record) {
                        try {
                            $properties = is_string($record->properties)
                                ? json_decode($record->properties, true)
                                : (is_array($record->properties) ? $record->properties : $record->properties?->toArray());

                            $attributes = $properties['attributes'] ?? [];

                            if (empty($attributes)) {
                                return '-';
                            }

                            $text = collect($attributes)
                                ->map(fn($value, $key) => "$key : $value")
                                ->implode("\n");

                            return nl2br(e($text));
                        } catch (\Throwable $e) {
                            return '-';
                        }
                    }),




                Tables\Columns\TextColumn::make('old_values')
                    ->label('القيم القديمة')
                    ->html()
                    ->getStateUsing(function ($record) {
                        try {
                            $properties = is_string($record->properties)
                                ? json_decode($record->properties, true)
                                : (is_array($record->properties) ? $record->properties : $record->properties?->toArray());

                            $old = $properties['old'] ?? [];

                            if (empty($old)) {
                                return '-';
                            }

                            $text = collect($old)
                                ->map(fn($value, $key) => "$key : $value")
                                ->implode("\n");

                            return nl2br(e($text));
                        } catch (\Throwable $e) {
                            return '-';
                        }
                    }),







            ]);
    }
}
