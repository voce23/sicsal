<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puerperios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parto_id')->constrained('partos');
            $table->date('control_48h')->nullable();
            $table->date('control_7d')->nullable();
            $table->date('control_28d')->nullable();
            $table->date('control_42d')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puerperios');
    }
};
