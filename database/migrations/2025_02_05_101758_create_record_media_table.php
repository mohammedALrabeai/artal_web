<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('record_media', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');

            // العلاقة البوليمورفية
            $table->morphs('recordable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_media');
    }
};
