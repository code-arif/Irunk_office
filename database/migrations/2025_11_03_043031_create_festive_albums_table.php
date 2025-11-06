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
        Schema::create('festive_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('favourite_set');
            $table->string('favourite_day');
            $table->timestamp('festive_date')->nullable();
            $table->string('camp_experience');
            $table->text('unique_moments');
            $table->text('dairy_entry');
            $table->enum('status',['public','private'])->default('private');
            $table->enum('fest_type',['upcoming','past'])->default('upcoming');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('festive_albums');
    }
};
