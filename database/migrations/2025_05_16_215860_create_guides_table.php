<?php

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
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->text('description')->nullable();
            $table->string('card_number')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('price', 8, 2);
            $table->decimal('const_salary', 8, 2)->default(100);
            $table->decimal('extra_salary', 8, 2)->default(0);
            $table->bigInteger('stars_count')->default(0);
            $table->bigInteger('reviews_count')->default(0);
            $table->string('fcm_token')->nullable();
            $table->foreignIdFor(City::class)->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
