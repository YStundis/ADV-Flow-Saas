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
          // Generate referral code for existing companys
        if (Schema::hasColumn('users', 'create_refercode')) {
            $users = User::where('type', 'company')->where('create_refercode', null)->get();
            // dd($users);
            foreach ($users as $user) {
                do {
                    $refferal_code = rand(100000, 999999);
                    $checkCode = User::where('type', 'company')->where('create_refercode', $refferal_code)->get();
                } while ($checkCode->count());
                $user->create_refercode = $refferal_code;
                $user->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            //
        });
    }
};
