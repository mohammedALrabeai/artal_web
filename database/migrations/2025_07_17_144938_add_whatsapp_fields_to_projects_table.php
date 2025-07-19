<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('has_whatsapp_group')->default(false);
            $table->string('whatsapp_group_id')->nullable();
            $table->string('whatsapp_group_name')->nullable();
            $table->timestamp('whatsapp_group_created_at')->nullable();
            $table->foreignId('whatsapp_created_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'has_whatsapp_group',
                'whatsapp_group_id',
                'whatsapp_group_name',
                'whatsapp_group_created_at',
            ]);

            $table->dropConstrainedForeignId('whatsapp_created_by');
        });
    }
};
