<?php

namespace App\Services\Dashboard;

use App\Models\DinasLuar;
use App\Models\HariLibur;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Pengumuman;
use App\Services\Dashboard\Concerns\FiltersByUserContext;
use Carbon\Carbon;

/**
 * Widget budaya kerja & harian dashboard admin (ulang tahun, pengumuman, dsb).
 */
class CultureWidgetService
{
    use FiltersByUserContext;

    /**
     * Logic: Culture & Daily Widgets (Pengumuman, Mini Calendar, Momen Spesial)
     */
    public function getCultureAndDailyWidgets($ctx, $userCtx, $selectedMonth = null)
    {
        $today = Carbon::parse($ctx['today']);
        $calendarRef = $today->copy();
        if ($selectedMonth && preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $calendarRef = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        }

        $monthStart = $calendarRef->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $monthEnd = $calendarRef->copy()->endOfMonth();

        // 1) Pengumuman real by month window
        $pengumumanAktif = Pengumuman::query()
            ->where(function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('tanggal_mulai', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->orWhereBetween('tanggal_selesai', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
                    ->orWhere(function ($sub) use ($monthStart, $monthEnd) {
                        $sub->whereDate('tanggal_mulai', '<=', $monthStart->format('Y-m-d'))
                            ->whereDate('tanggal_selesai', '>=', $monthEnd->format('Y-m-d'));
                    });
            })
            ->orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->limit(10)
            ->get();

        // 2) Event mini calendar: holiday, leave, KPI cutoff
        $holidayRows = HariLibur::query()
            ->whereBetween('tanggal_libur', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->get(['tanggal_libur', 'keterangan']);

        $leaveQuery = Izin::query()
            ->join('karyawan', 'izin.nik', '=', 'karyawan.nik')
            ->where('izin.status_approved', 1)
            ->whereDate('izin.tgl_izin_dari', '<=', $monthEnd->format('Y-m-d'))
            ->whereDate('izin.tgl_izin_sampai', '>=', $monthStart->format('Y-m-d'));
        $this->applyCabangFilter($leaveQuery, $userCtx, 'karyawan');
        $leaveRows = $leaveQuery->get([
            'izin.tgl_izin_dari',
            'izin.tgl_izin_sampai',
            'izin.status',
            'karyawan.nik',
            'karyawan.nama_lengkap',
            'karyawan.foto',
        ]);

        $dinasQuery = DinasLuar::query()
            ->join('karyawan', 'dinas_luar.nik', '=', 'karyawan.nik')
            ->where('dinas_luar.status_acc', 'acc')
            ->whereDate('dinas_luar.tgl_mulai', '<=', $monthEnd->format('Y-m-d'))
            ->whereDate('dinas_luar.tgl_selesai', '>=', $monthStart->format('Y-m-d'));
        $this->applyCabangFilter($dinasQuery, $userCtx, 'karyawan');
        $dinasRows = $dinasQuery->get([
            'dinas_luar.tgl_mulai',
            'dinas_luar.tgl_selesai',
            'dinas_luar.lokasi_tujuan',
            'karyawan.nik',
            'karyawan.nama_lengkap',
            'karyawan.foto',
        ]);

        $calendarEvents = [];
        $calendarDailyDetails = [];

        $ensureCalendarDay = function (&$arr, $key) {
            if (! isset($arr[$key])) {
                $arr[$key] = [
                    'holiday' => false,
                    'leave' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'cuti' => 0,
                    'dinas' => 0,
                    'kpi_cutoff' => false,
                ];
            }
        };

        $ensureDetailsDay = function (&$arr, $key) {
            if (! isset($arr[$key])) {
                $arr[$key] = [
                    'izin' => [],
                    'sakit' => [],
                    'cuti' => [],
                    'dinas' => [],
                ];
            }
        };

        foreach ($holidayRows as $h) {
            $key = Carbon::parse($h->tanggal_libur)->format('Y-m-d');
            $ensureCalendarDay($calendarEvents, $key);
            $calendarEvents[$key]['holiday'] = true;
        }

        foreach ($leaveRows as $row) {
            $from = Carbon::parse($row->tgl_izin_dari)->max($monthStart);
            $to = Carbon::parse($row->tgl_izin_sampai)->min($monthEnd);

            $status = strtolower((string) ($row->status ?? 'i'));
            $type = $status === 's' ? 'sakit' : ($status === 'c' ? 'cuti' : 'izin');
            $person = [
                'nik' => $row->nik,
                'nama_lengkap' => $row->nama_lengkap,
                'foto' => $row->foto,
                'keterangan' => strtoupper($type),
            ];

            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $key = $d->format('Y-m-d');
                $ensureCalendarDay($calendarEvents, $key);
                $ensureDetailsDay($calendarDailyDetails, $key);
                $calendarEvents[$key]['leave']++;
                $calendarEvents[$key][$type]++;
                $calendarDailyDetails[$key][$type][] = $person;
            }
        }

        foreach ($dinasRows as $row) {
            $from = Carbon::parse($row->tgl_mulai)->max($monthStart);
            $to = Carbon::parse($row->tgl_selesai)->min($monthEnd);
            $person = [
                'nik' => $row->nik,
                'nama_lengkap' => $row->nama_lengkap,
                'foto' => $row->foto,
                'keterangan' => $row->lokasi_tujuan ?: '-',
            ];

            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $key = $d->format('Y-m-d');
                $ensureCalendarDay($calendarEvents, $key);
                $ensureDetailsDay($calendarDailyDetails, $key);
                $calendarEvents[$key]['dinas']++;
                $calendarDailyDetails[$key]['dinas'][] = $person;
            }
        }

        $kpiCutoffDate = Carbon::create($today->year, $today->month, min(25, $monthEnd->day));
        $cutoffKey = $kpiCutoffDate->format('Y-m-d');
        $ensureCalendarDay($calendarEvents, $cutoffKey);
        $calendarEvents[$cutoffKey]['kpi_cutoff'] = true;

        // 3) Culture widgets: birthday + work anniversary (month-aware)
        $birthdayQuery = Karyawan::query()
            ->with(['cabang:kode_cabang,nama_cabang', 'departemen:kode_dept,nama_dept'])
            ->where('status_aktif', 'Aktif')
            ->whereRaw('EXTRACT(MONTH FROM tanggal_lahir) = ?', [$calendarRef->month])
            ->orderByRaw('EXTRACT(DAY FROM tanggal_lahir) asc');

        if ($calendarRef->isSameMonth($today) && $calendarRef->year === $today->year) {
            $birthdayQuery->whereRaw('EXTRACT(DAY FROM tanggal_lahir) >= ?', [$today->day]);
        }

        $this->applyCabangFilter($birthdayQuery, $userCtx);
        $ulangTahunBulanIni = $birthdayQuery->get(['nik', 'nama_lengkap', 'tanggal_lahir', 'foto', 'kode_cabang', 'kode_dept']);

        $annivQuery = Karyawan::query()
            ->where('status_aktif', 'Aktif')
            ->whereNotNull('tanggal_awal_kontrak')
            ->whereRaw('EXTRACT(MONTH FROM tanggal_awal_kontrak) = ?', [$calendarRef->month])
            ->orderByRaw('EXTRACT(DAY FROM tanggal_awal_kontrak) asc')
            ->limit(8);
        $this->applyCabangFilter($annivQuery, $userCtx);
        $anniversaryBulanIni = $annivQuery->get(['nik', 'nama_lengkap', 'tanggal_awal_kontrak', 'foto']);

        $calendarLabel = $monthStart->translatedFormat('F Y');
        $calendarMonth = $monthStart->format('Y-m');
        $prevMonth = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthStart->copy()->addMonth()->format('Y-m');

        return compact(
            'pengumumanAktif',
            'calendarEvents',
            'calendarDailyDetails',
            'calendarLabel',
            'calendarMonth',
            'prevMonth',
            'nextMonth',
            'kpiCutoffDate',
            'ulangTahunBulanIni',
            'anniversaryBulanIni'
        );
    }
}
