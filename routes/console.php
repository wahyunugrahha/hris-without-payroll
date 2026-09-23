<?php

use Illuminate\Support\Facades\Schedule;



// 1. Auto Snapshot Untuk Leaderboard
Schedule::command('leaderboard:snapshot')
    ->dailyAt('23:51')
    ->timezone('Asia/Jakarta');

Schedule::command('leaderboard:snapshot-kpi')
    ->dailyAt('23:52')
    ->timezone('Asia/Jakarta');

// 2. Auto Evaluasi Surat Peringatan (SP)
Schedule::command('sp:evaluate')
    ->dailyAt('23:53')
    ->timezone('Asia/Jakarta');

// 3. Rekap Bulanan & Bonus
Schedule::command('rekap:bulanan')
    ->monthlyOn(25, '23:55')
    ->timezone('Asia/Jakarta');