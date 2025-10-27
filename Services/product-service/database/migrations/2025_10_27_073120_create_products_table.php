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
            $table->uuid('restaurant_id');
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->bigInteger('price');
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('quantity');
            $table->integer('estimated_preparation_time')->nullable();

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
