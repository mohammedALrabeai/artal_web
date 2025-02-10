<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'content',
                'file_url',
                'image_url',
                'video_url',
            ]);
        });
    }

    public function down()
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->text('content')->nullable();
            $table->string('file_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
        });
    }
};
