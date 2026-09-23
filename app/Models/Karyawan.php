<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Karyawan extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public const STATUS_AKTIF = 'Aktif';

    public const STATUS_NONAKTIF = 'Nonaktif';

    public const STATUS_DIBERHENTIKAN = 'Diberhentikan';

    public const STATUS_MENUNGGU_APPROVAL = 'Menunggu Approval';

    public const FILTER_HABIS_KONTRAK = 'Habis Kontrak';

    public const TURNOVER_STATUSES = [
        self::STATUS_NONAKTIF,
        self::STATUS_DIBERHENTIKAN,
    ];

    public const FILTERABLE_STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_NONAKTIF,
        self::STATUS_DIBERHENTIKAN,
        self::STATUS_MENUNGGU_APPROVAL,
        self::FILTER_HABIS_KONTRAK,
    ];

    protected $table = 'karyawan';

    protected $guard_name = 'karyawan';

    protected $primaryKey = 'nik';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        // --- IDENTITAS UTAMA ---
        'nik',
        'nama_lengkap',
        'nama_panggilan',
        'password',
        'must_change_password',
        'remember_token',
        'jabatan_id',
        'kode_dept',
        'kode_cabang',
        'foto',
        'no_hp',
        'email',

        // --- DATA PRIBADI ---
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'status_pernikahan',
        'alamat',
        'pendidikan_terakhir',
        'status_ptkp',

        // --- KEPEGAWAIAN ---
        'tmt',                  // TMT (Join Date)
        'tanggal_awal_kontrak',
        'status_karyawan',      // PKWT/Tetap
        'is_whitelist',
        'status_aktif',         // Aktif/Nonaktif
        'tanggal_habis_kontrak',
        'tanggal_keluar',
        'history_karyawan',
        'statemen',             // Statement Surat

        // --- KELUARGA & DARURAT ---
        'nama_ibu_kandung',
        'nama_darurat',         // Nama Kontak Darurat
        'hubungan_darurat',     // Hubungan (Istri/Ayah)
        'no_darurat',           // No HP Darurat

        // --- LEGAL & FINANCE ---
        'no_bpjs_kesehatan',
        'foto_bpjs_kesehatan',
        'no_bpjs_ketenagakerjaan',
        'foto_bpjs_ketenagakerjaan',
        'no_rekening',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'must_change_password' => 'boolean',
        'tmt' => 'date',
        'tanggal_awal_kontrak' => 'date',
        'tanggal_lahir' => 'date',
        'tanggal_habis_kontrak' => 'date',
        'tanggal_keluar' => 'date',
    ];

    public function getSisaKontrakAttribute()
    {
        if (empty($this->tanggal_habis_kontrak)) {
            if ($this->status_aktif == self::STATUS_MENUNGGU_APPROVAL) {
                return '-';
            }

            return 'Permanen';
        }

        $end = Carbon::parse($this->tanggal_habis_kontrak)->startOfDay();
        $today = Carbon::today();
        $isExpired = $end->lt($today);

        $from = $isExpired ? $end : $today;
        $to = $isExpired ? $today : $end;
        $interval = $from->diff($to);

        $parts = [];
        $months = ($interval->y * 12) + $interval->m;
        $days = $interval->d;

        if ($months > 0) {
            $parts[] = $months.' bulan';
        }

        if ($days > 0) {
            $parts[] = $days.' hari';
        }

        if (empty($parts)) {
            return 'Hari ini';
        }

        return $isExpired
            ? 'Expired '.implode(' ', $parts).' lalu'
            : implode(' ', $parts).' lagi';
    }

    public function getFotoUrlAttribute()
    {
        if ($this->foto && Storage::disk('public')->exists('uploads/karyawan/'.$this->foto)) {
            return asset('storage/uploads/karyawan/'.$this->foto);
        }

        return asset('assets/img/nophoto.png');
    }

    public function getJabatanNamaAttribute(): ?string
    {
        if (array_key_exists('jabatan_nama', $this->attributes)) {
            return $this->attributes['jabatan_nama'];
        }

        return optional($this->jabatanRel)->nama_jabatan;
    }

    public function scopeWajibPresensi($query)
    {
        return $query->where('is_whitelist', 0);
    }

    public function scopeDataAktif($query)
    {
        return $query
            ->where('status_aktif', self::STATUS_AKTIF)
            ->whereNull('tanggal_keluar')
            ->where(function ($sub) {
                $sub->whereNull('tanggal_habis_kontrak')
                    ->orWhereDate('tanggal_habis_kontrak', '>', Carbon::today()->toDateString());
            });
    }

    public function scopeTurnover($query)
    {
        return $query->where(function ($sub) {
            $sub->whereIn('status_aktif', self::TURNOVER_STATUSES)
                ->orWhereNotNull('tanggal_keluar')
                ->orWhereDate('tanggal_habis_kontrak', '<=', Carbon::today()->toDateString());
        });
    }

    public function scopeFilterStatus($query, ?string $status)
    {
        return match ($status) {
            self::STATUS_AKTIF => $query->dataAktif(),
            self::STATUS_NONAKTIF => $query->where('status_aktif', self::STATUS_NONAKTIF),
            self::STATUS_DIBERHENTIKAN => $query->where('status_aktif', self::STATUS_DIBERHENTIKAN),
            self::FILTER_HABIS_KONTRAK => $query->where('status_aktif', self::STATUS_AKTIF)
                ->whereNotNull('tanggal_habis_kontrak')
                ->whereDate('tanggal_habis_kontrak', '<=', Carbon::today()->toDateString()),
            self::STATUS_MENUNGGU_APPROVAL => $query->where('status_aktif', self::STATUS_MENUNGGU_APPROVAL),
            default => $query,
        };
    }

    // --- RELASI ---
    /**
     * Admin cabang hanya melihat karyawan di cabangnya.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        $cabang = $user?->scopedCabang();

        return $query->when($cabang !== null, fn (Builder $q) => $q->where('kode_cabang', $cabang));
    }

    public function jabatanRel()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id', 'id');
    }

    public function departemen()
    {
        return $this->belongsTo(Departemen::class, 'kode_dept', 'kode_dept');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'kode_cabang', 'kode_cabang');
    }

    public function presensis()
    {
        return $this->hasMany(Presensi::class, 'nik', 'nik');
    }

    public function konfigurasiJamKerja()
    {
        return $this->hasMany(Setjamkerja::class, 'nik', 'nik');
    }

    public function lembur()
    {
        return $this->hasMany(Lembur::class, 'nik', 'nik');
    }

    public function izin()
    {
        return $this->hasMany(Izin::class, 'nik', 'nik');
    }

    public function dinasLuars()
    {
        return $this->hasMany(DinasLuar::class, 'nik', 'nik');
    }

    public function kpiDaily()
    {
        return $this->hasMany(KPIDaily::class, 'nik', 'nik');
    }

    public function kpiReport()
    {
        return $this->hasMany(KPIReport::class, 'nik', 'nik');
    }

    public function suratPeringatan()
    {
        return $this->hasMany(SuratPeringatan::class, 'nik', 'nik');
    }
}
