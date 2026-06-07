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
        Schema::create('points_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('points')->comment('positive = earn, negative = deduct');
            $table->string('action_type', 50)->comment('post_upvoted, answer_accepted, comment_upvoted, post_created, daily_login, etc');
            $table->uuid('reference_id')->nullable()->comment('related post_id or comment_id');
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('points_log');
    }
};
