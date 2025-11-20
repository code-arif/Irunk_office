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
        Schema::create('festive_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained('artists')->onDelete('cascade');
            $table->string('favourite_set');
            $table->string('favourite_day');
            $table->string('camp_experience');
            $table->string('festive_story');
            $table->string('locations')->nullable();
            $table->date('festive_date');
            $table->enum('day_type', ['single-day', 'none'])->default('none');
            $table->enum('status', ['public', 'private'])->default('public');
            $table->string('fest_type')->default('previous');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('festive_experiences');
    }
};
