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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // User foreign key
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Artist foreign key
            $table->unsignedBigInteger('artist_id');
            $table->foreign('artist_id')
                  ->references('id')
                  ->on('artists')
                  ->onDelete('cascade');

            // Other fields
            $table->decimal('rating', 3, 2); // e.g., 4.50
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->longText('comment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
