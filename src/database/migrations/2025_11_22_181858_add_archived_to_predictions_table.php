<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
    {
        Schema::table('predictions', function (Blueprint $table) {
            if (!Schema::hasColumn('predictions', 'archived')) {
                $table->boolean('archived')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('predictions', function (Blueprint $table) {
            if (Schema::hasColumn('predictions', 'archived')) {
                $table->dropColumn('archived');
            }
        });
    }
};
