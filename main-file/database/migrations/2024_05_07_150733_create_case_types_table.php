<?php

use App\Models\User;
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
        Schema::create('case_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('created_by');
            $table->timestamps();
        });
        
        if(Request::route()->getName() == 'LaravelUpdater::database')
        {
            User::assginecasetypepermission();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_types');
    }
};
