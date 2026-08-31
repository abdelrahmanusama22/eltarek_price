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
            $table->string('warranty_info')->nullable();
            $table->string('brochure_pdf')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_entries', function (Blueprint $table) {
            $table->dropColumn(['warranty_info', 'brochure_pdf']);
        });
    }
};
