<?php

use App\Enums\ReviewStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->string('status', 20)->default(ReviewStatus::Pending->value)->after('body');
        });

        DB::table('product_reviews')
            ->where('is_approved', true)
            ->update(['status' => ReviewStatus::Approved->value]);

        DB::table('product_reviews')
            ->where('is_approved', false)
            ->update(['status' => ReviewStatus::Pending->value]);

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('body');
        });

        DB::table('product_reviews')
            ->where('status', ReviewStatus::Approved->value)
            ->update(['is_approved' => true]);

        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
