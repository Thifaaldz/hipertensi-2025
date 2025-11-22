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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->string('kecamatan')->index();
            $table->string('wilayah')->nullable();
            $table->integer('tahun')->nullable();
            $table->double('persentase')->nullable();
            $table->string('prioritas')->nullable();
            $table->double('lat')->nullable();
            $table->double('lon')->nullable();
            $table->string('predicted_route')->nullable();
            $table->string('focus_month')->nullable();
            $table->date('focus_date')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->json('meta')->nullable(); // additional info
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
