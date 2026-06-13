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
        Schema::table('post_edit_history', function (Blueprint $table) {
            $table->string('title_before', 300)->nullable()->after('edited_by');
            $table->string('title_after', 300)->nullable()->after('title_before');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_edit_history', function (Blueprint $table) {
            $table->dropColumn(['title_before', 'title_after']);
        });
    }
};
