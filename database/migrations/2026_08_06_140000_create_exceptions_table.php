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
        Schema::create('exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->text('message');
            $table->longText('trace');
            $table->string('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->text('request_url')->nullable();
            $table->string('request_ip', 45)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('environment', 20)->nullable();
            $table->timestamps();

            $table->index('class');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
