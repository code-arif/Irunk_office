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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained('subcategories')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('boost_plan_id')->nullable()->constrained('boost_plans')->onDelete('set null');
            // $table->boolean('is_boosted')->default(0);
            $table->boolean('is_boosted')->default(false);
            $table->string('boosted_until')->nullable();
            $table->timestamp('boosted_at')->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('condition')->default('new');
            $table->string('brand')->nullable();
            $table->boolean('for_sell')->default(true);
            $table->integer('sell_count')->default(0);
            $table->string('material')->nullable();
            $table->float('price');
            $table->integer('quantity')->default(1);
            $table->float('vat')->default(0);
            $table->string('thumbnail')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
