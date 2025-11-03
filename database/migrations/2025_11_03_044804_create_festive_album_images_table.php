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
        Schema::create('festive_album_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('festive_album_id')->constrained('festive_albums')->onDelete('cascade');
            $table->string('image_or_video_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('festive_album_images');
    }
};
