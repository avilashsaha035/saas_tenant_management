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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->cascadeOnDelete();
            $table->string('role')->default('member')->after('password'); // 'owner', 'admin', 'manager', 'member'
            $table->string('status')->default('active')->after('role'); // 'active', 'inactive'

            $table->index(['tenant_id', 'role']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropIndex(['tenant_id', 'status']);
            $table->dropColumn(['tenant_id', 'role', 'status']);
        });
    }
};
