<?php

use App\Models\GroupTrip;
use App\Models\Guide;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


//use Ramsey\Uuid\Guid\Guid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reports_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->foreignIdFor(Guide::class)->constrained();
            $table->foreignIdFor(GroupTrip::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports_logs');
    }
};
