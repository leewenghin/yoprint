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
        Schema::table('imports', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('uploaded_at');
            $table->integer('total_rows')->nullable()->after('error_message');
            $table->integer('processed_rows')->default(0)->after('total_rows');
            $table->integer('failed_rows')->default(0)->after('processed_rows');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn(['processed_at', 'total_rows', 'processed_rows', 'failed_rows']);
        });
    }
};
