<?php

namespace App\Forms\Components;

use Filament\Forms\Components\FileUpload;

class ImageField extends FileUpload
{
    protected string $view = 'forms.components.image-field';

    public static function make(string $name): static
    {
        return parent::make($name)
            ->image()
            ->disk('s3')
            ->directory('employees')
            ->visibility('public')
            // ->preserveFilenames()
            ->previewable(true)
            ->downloadable();
    }
}
