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
            $table->float('ticket_price');
            $table->bigInteger('tickets_count')->nullable()->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->bigInteger('stars_count')->default(0);
            $table->bigInteger('reviewer_count')->default(0);
            $table->bigInteger('tickets_limit')->nullable();
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
