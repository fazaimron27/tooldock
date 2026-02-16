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
        Schema::create('vaults', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('category_id')->nullable()->index();
            $table->enum('type', ['login', 'card', 'note', 'server'])->default('login')->index();
            $table->string('name')->index();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('issuer')->nullable()->index();
            $table->text('value')->nullable(); // Encrypted
            $table->text('totp_secret')->nullable(); // Encrypted
            $table->string('totp_algorithm', 10)->nullable(); // sha1, sha256, sha512
            $table->unsignedTinyInteger('totp_digits')->nullable(); // 6 or 8
            $table->unsignedSmallInteger('totp_period')->nullable(); // 30 or 60 seconds
            $table->text('fields')->nullable(); // Encrypted
            $table->string('url')->nullable();
            $table->boolean('is_favorite')->default(false)->index();
            $table->timestamps();
        });

        Schema::table('vaults', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaults');
    }
};
