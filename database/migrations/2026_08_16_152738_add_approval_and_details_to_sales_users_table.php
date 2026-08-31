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
        Schema::table('sales_users', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('password');
            $table->string('phone')->nullable()->after('email');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['is_approved', 'phone', 'branch_id']);
        });
    }
};
