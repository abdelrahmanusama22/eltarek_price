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
        Schema::table('price_entries', function (Blueprint $table) {
            // 1. Drop the existing foreign key constraint
            $table->dropForeign(['car_id']);

            // 2. Make the column nullable
            $table->unsignedBigInteger('car_id')->nullable()->change();

            // 3. Re-add the foreign key constraint with nullOnDelete()
            $table->foreign('car_id')
                  ->references('id')
                  ->on('cars')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_entries', function (Blueprint $table) {
            // 1. Drop the nullable foreign key
            $table->dropForeign(['car_id']);

            // 2. Revert the column to be non-nullable
            $table->unsignedBigInteger('car_id')->nullable(false)->change();

            // 3. Restore the original cascading delete behavior
            $table->foreign('car_id')
                  ->references('id')
                  ->on('cars')
                  ->cascadeOnDelete();
        });
    }
};
