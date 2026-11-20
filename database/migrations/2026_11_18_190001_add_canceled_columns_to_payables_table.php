<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payables', function (Blueprint $table): void {
            $table->timestamp('canceled_at')->nullable()->after('notes');
            $table->string('cancel_reason', 255)->nullable()->after('canceled_at');
            $table->index('canceled_at');
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table): void {
            $table->dropIndex(['canceled_at']);
            $table->dropColumn(['canceled_at', 'cancel_reason']);
        });
    }
};
