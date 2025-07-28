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
        Schema::create('limited_events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('tickets_count')->default(0);
            $table->bigInteger('remaining_tickets')->default(0);
            $table->bigInteger('tickets_limit')->default(0);
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limited_events');
    }
};
