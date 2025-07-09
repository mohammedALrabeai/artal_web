<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfTextField extends Model
{
    use HasFactory;

    protected $fillable = [
        'pdf_document_id',
        'field_name',
        'field_label',
        'x_position',
        'y_position',
        'width',
        'height',
        'page_number',
        'font_size',
        'font_family',
        'text_color',
        'is_required',
        'field_type',
        'placeholder',
    ];

    protected $casts = [
        'x_position' => 'float',
        'y_position' => 'float',
        'width' => 'float',
        'height' => 'float',
        'page_number' => 'integer',
        'font_size' => 'integer',
        'is_required' => 'boolean',
    ];

    public function pdfDocument(): BelongsTo
    {
        return $this->belongsTo(PdfDocument::class);
    }
}

