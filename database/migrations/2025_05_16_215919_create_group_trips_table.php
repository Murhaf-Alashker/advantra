<?php

use App\Enums\Status;
use App\Models\Guide;
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
        Schema::create('group_trips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('description')->nullable();
            $table->dateTime('starting_date');
            $table->dateTime('ending_date');
            $table->enum('status',Status::values())->default(Status::PENDING);
            $table->decimal('price',10,2);
            $table->bigInteger('tickets_count');
            $table->bigInteger('stars_count')->default(0);
            $table->bigInteger('reviews_count')->default(0);
            $table->foreignIdFor(Guide::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_trips');
    }
};
