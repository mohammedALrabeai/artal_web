<?php

// database/migrations/2025_08_11_100010_update_asset_assignments_add_users_and_constraints.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            // مُسلِّم العهدة عند التعيين
            if (! Schema::hasColumn('asset_assignments', 'assigned_by_user_id')) {
                $table->foreignId('assigned_by_user_id')->nullable()->after('employee_id')
                    ->constrained('users')->nullOnDelete();
            }

            // مستلم الإرجاع عند الإرجاع
            if (! Schema::hasColumn('asset_assignments', 'returned_by_user_id')) {
                $table->foreignId('returned_by_user_id')->nullable()->after('returned_date')
                    ->constrained('users')->nullOnDelete();
            }

            // عمود مُوَلَّد لتحديد السجل المفتوح (returned_date IS NULL)
            // STORED حتى يمكن فهرسته
            if (! Schema::hasColumn('asset_assignments', 'is_open')) {
                $table->boolean('is_open')->storedAs('(case when `returned_date` is null then 1 else 0 end)')
                    ->after('returned_by_user_id');
            }

            // فهرس فريد لضمان سجل مفتوح واحد فقط لكل أصل
            $table->unique(['asset_id', 'is_open'], 'unique_open_assignment_per_asset');

            // فهارس مفيدة
            $table->index(['asset_id']);
            $table->index(['employee_id']);
            $table->index(['assigned_date']);
            $table->index(['expected_return_date']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_assignments', function (Blueprint $table) {
            $table->dropUnique('unique_open_assignment_per_asset');

            $table->dropIndex(['asset_id']);
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['assigned_date']);
            $table->dropIndex(['expected_return_date']);

            if (Schema::hasColumn('asset_assignments', 'is_open')) {
                $table->dropColumn('is_open');
            }
            if (Schema::hasColumn('asset_assignments', 'assigned_by_user_id')) {
                $table->dropConstrainedForeignId('assigned_by_user_id');
            }
            if (Schema::hasColumn('asset_assignments', 'returned_by_user_id')) {
                $table->dropConstrainedForeignId('returned_by_user_id');
            }
        });
    }
};
