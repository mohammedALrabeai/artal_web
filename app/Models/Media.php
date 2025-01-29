<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Illuminate\Support\Str;

class Media extends BaseMedia
{
    //boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
          dump($media);
         
        });
        
    }
}
