<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('karyawan', function (Blueprint $table) {
            // Identitas Tambahan
            $table->string('nama_panggilan')->nullable();
            $table->string('jenis_kelamin', 10)->nullable(); // L/P
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama')->nullable();

            $table->string('status_pernikahan')->nullable(); // Menikah/Belum/Cerai

            // --- KOLOM BARU YANG DIMINTA ---
            // Menyimpan kode PTKP: TK, K/0, K/1, K/2, K/3
            $table->string('status')->nullable();
            // -------------------------------

            $table->text('alamat')->nullable();
            $table->string('email')->nullable();
            $table->string('pendidikan_terakhir')->nullable();

            // Kepegawaian
            $table->date('tanggal_masuk')->nullable();
            $table->string('status_karyawan')->nullable(); // PKWT/Tetap
            $table->string('status_aktif')->default('Aktif'); // Aktif/Resign
            $table->date('tanggal_habis_kontrak')->nullable();
            $table->text('history_karyawan')->nullable();
            $table->text('statemen')->nullable(); // Statement Surat

            // Keluarga & Darurat
            $table->string('nama_ibu_kandung')->nullable();
            $table->string('nama_darurat')->nullable(); // Nama orangnya
            $table->string('no_darurat')->nullable();   // Nomor HP
            $table->string('hubungan_darurat')->nullable(); // Hub (Istri/Ayah)

            // Legal & Finance
            $table->string('no_bpjs_kesehatan')->nullable();
            $table->string('no_bpjs_ketenagakerjaan')->nullable();
            $table->string('no_rekening')->nullable(); // BRI
        });
    }

    public function down()
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn([
                'nama_panggilan',
                'jenis_kelamin',
                'tempat_lahir',
                'tanggal_lahir',
                'agama',
                'status_pernikahan',
                'status', // Hapus kolom status jika rollback
                'alamat',
                'email',
                'pendidikan_terakhir',
                'tanggal_masuk',
                'status_karyawan',
                'status_aktif',
                'tanggal_habis_kontrak',
                'history_karyawan',
                'statemen',
                'nama_ibu_kandung',
                'nama_darurat',
                'no_darurat',
                'hubungan_darurat',
                'no_bpjs_kesehatan',
                'no_bpjs_ketenagakerjaan',
                'no_rekening',
            ]);
        });
    }
};
