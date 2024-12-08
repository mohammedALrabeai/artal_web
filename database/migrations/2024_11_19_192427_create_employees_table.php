<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // Personal Information
            $table->string('first_name');
            $table->string('father_name');
            $table->string('grandfather_name');
            $table->string('family_name');
            $table->date('birth_date');
            $table->string('national_id');
            $table->date('national_id_expiry');
            $table->string('nationality');
            $table->string('bank_account');
            $table->string('sponsor_company');
            $table->string('blood_type');
            $table->date('contract_start');
            $table->date('actual_start');
        
            // Job Information
            $table->decimal('basic_salary', 8, 2);
            $table->decimal('living_allowance', 8, 2)->nullable();
            $table->decimal('other_allowances', 8, 2)->nullable();
            $table->string('job_status');
            $table->string('health_insurance_status');
            $table->string('health_insurance_company')->nullable();
            $table->integer('vacation_balance');
            $table->string('social_security')->nullable();
            $table->string('social_security_code')->nullable();
        
            // Education
            $table->string('qualification');
            $table->string('specialization');
        
            // Contact Information
            $table->string('mobile_number');
            $table->string('phone_number')->nullable();
        
            // Address
            $table->string('region');
            $table->string('city');
            $table->string('street');
            $table->string('building_number');
            $table->string('apartment_number')->nullable();
            $table->string('postal_code')->nullable();
        
            // Social Media
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('email')->nullable();
        
            // Security
            $table->string('password');
            $table->foreignId('added_by')->nullable()->constrained('users')->onDelete('set null');
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
