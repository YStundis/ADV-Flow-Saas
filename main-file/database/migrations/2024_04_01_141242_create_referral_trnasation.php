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
        Schema::create('referral_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('plan');
            $table->integer('commisson');
            $table->float('commisson_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_trnasation');
    }
};
