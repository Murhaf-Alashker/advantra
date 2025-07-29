<?php

use App\Models\Category;
use App\Models\City;
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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->longText('description')->nullable();
            $table->decimal('basic_cost',10,2);
            $table->decimal('price',10,2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->bigInteger('stars_count')->default(0);
            $table->bigInteger('reviewer_count')->default(0);
            $table->foreignIdFor(City::class)->constrained();
            $table->foreignIdFor(Category::class)->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
