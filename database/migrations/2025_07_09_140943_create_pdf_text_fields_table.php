<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_text_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pdf_document_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->string('field_label');
            $table->decimal('x_position', 8, 2);
            $table->decimal('y_position', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->integer('page_number')->default(1);
            $table->integer('font_size')->default(12);
            $table->string('font_family')->default('Arial');
            $table->string('text_color')->default('#000000');
            $table->boolean('is_required')->default(false);
            $table->enum('field_type', ['text', 'textarea', 'number', 'date'])->default('text');
            $table->string('placeholder')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_text_fields');
    }
};

