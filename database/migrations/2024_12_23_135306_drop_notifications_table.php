<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notifications');
    }

    public function down(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->index();
            $table->morphs('notifiable');
            $table->longText('data');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });
    }
};
