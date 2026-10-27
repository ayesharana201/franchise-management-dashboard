<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('franchisee')->after('password'); // franchisor, regional_manager, franchisee
            $t->foreignId('location_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $t->string('region')->nullable()->after('location_id'); // for regional managers
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('location_id');
            $t->dropColumn(['role', 'region']);
        });
    }
};
