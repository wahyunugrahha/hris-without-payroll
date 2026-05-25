<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cabang_lokasis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_cabang');
            $table->string('nama_lokasi')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('radius')->default(0); // meters
            $table->boolean('aktif')->default(true);
            // no timestamps for simplicity

            $table->index(['kode_cabang', 'aktif']);
        });

        // Backfill from existing cabang.lokasi_kantor and cabang.radius
        // Assumes lokasi_kantor formatted as "lat,long"
        $cabangs = DB::table('cabang')->select('kode_cabang', 'lokasi_kantor', 'radius')->get();
        foreach ($cabangs as $cabang) {
            if (!empty($cabang->lokasi_kantor) && strpos($cabang->lokasi_kantor, ',') !== false) {
                [$lat, $lon] = explode(',', $cabang->lokasi_kantor);
                DB::table('cabang_lokasis')->insert([
                    'kode_cabang' => $cabang->kode_cabang,
                    'nama_lokasi' => 'Lokasi Utama',
                    'latitude' => (float)$lat,
                    'longitude' => (float)$lon,
                    'radius' => (int)($cabang->radius ?? 0),
                    'aktif' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cabang_lokasis');
    }
};
