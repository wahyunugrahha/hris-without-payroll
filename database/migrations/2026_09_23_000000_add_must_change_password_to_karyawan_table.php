<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(false)->after('password');
        });

        // Akun yang masih memakai password default lama wajib ganti saat login berikutnya.
        // ponytail: 1 bcrypt check per karyawan (~0.1-0.3 dtk/baris); untuk ribuan karyawan jalankan saat sepi.
        DB::table('karyawan')->select('nik', 'password')->orderBy('nik')->chunk(200, function ($rows) {
            $niks = $rows->filter(fn ($row) => $row->password && Hash::check('123456', $row->password))->pluck('nik');

            if ($niks->isNotEmpty()) {
                DB::table('karyawan')->whereIn('nik', $niks)->update(['must_change_password' => true]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};
