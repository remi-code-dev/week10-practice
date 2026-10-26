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
        // $table はクロージャ（function (Blueprint $table) { ... }）の引数なので、この中でしか使えない
        Schema::table('posts', function (Blueprint $table) {
            // S3上の保存先パス（例: images/xxxx.jpg）を入れる。画像なしの投稿もあるので nullable
            $table->string('image_path')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
