@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Beranda</div>
                    <h2 class="page-title">Dashboard HRIS</h2>
                    <p class="page-subtitle">Ringkasan aktivitas HR dan karyawan</p>
                </div>
                <div class="col-auto ms-auto">
                    <a href="{{ route('overview') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24"
                            stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                            <path d="M4 4h16v12h-16z" />
                            <path d="M8 20h8" />
                            <path d="M12 16v4" />
                        </svg>
                        Overview
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <style>
        /* Dashboard HRIS — mengikuti design.md (token dari admin-tokens.css). */
        .dashboard-shell {
            padding-inline: var(--space-lg);
        }

        /* ── Grid: kolom utama + kolom samping; mobile satu kolom berurutan prioritas ── */
        .dash-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .dash-main,
        .dash-side {
            display: contents;
        }

        .dash-attendance { order: 1; }
        .dash-attention { order: 2; }
        .dash-side > .dash-card:nth-child(2) { order: 3; }
        .dash-main > .dash-card:nth-child(2) { order: 4; }
        .dash-main > .dash-card:nth-child(3) { order: 5; }
        .dash-side > .dash-card:nth-child(3) { order: 6; }
        .dash-main > .dash-card:nth-child(4) { order: 7; }
        .dash-side > .dash-card:nth-child(4) { order: 8; }
        .dash-side > .dash-card:nth-child(5) { order: 9; }
        .dash-demografi { order: 10; }

        @media (min-width: 1200px) {
            .dash-grid {
                grid-template-columns: minmax(0, 1fr) minmax(300px, 30%);
                align-items: start;
            }

            .dash-main,
            .dash-side {
                display: flex;
                flex-direction: column;
                gap: var(--space-md);
                min-width: 0;
            }

            .dash-demografi {
                grid-column: 1 / -1;
            }
        }

        /* ── Kartu dasar ── */
        .dash-card {
            min-width: 0;
            padding: var(--space-md) var(--space-lg);
            background: var(--color-surface);
            border: 1px solid var(--color-rule);
            border-radius: 12px;
            box-shadow: var(--shadow-lift);
        }

        .dash-card-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--space-xs) var(--space-md);
            margin-bottom: var(--space-md);
        }

        .dash-card-title {
            margin: 0;
            color: var(--color-ink);
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .dash-card-sub {
            margin: 2px 0 0;
            color: var(--color-muted);
            font-size: 0.8125rem;
        }

        .dash-subtitle {
            margin: 0;
            color: var(--color-ink-2);
            font-size: 0.8125rem;
            font-weight: 600;
        }

        .dash-card-foot {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--space-xs);
            margin-top: var(--space-sm);
            padding-top: var(--space-sm);
            border-top: 1px solid var(--color-rule);
        }

        .dash-link,
        .dash-inline-link {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 0;
            border: 0;
            background: none;
            color: var(--color-accent);
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            cursor: pointer;
        }

        .dash-inline-link {
            color: inherit;
            text-decoration: underline;
            text-decoration-color: var(--color-rule-2);
            text-underline-offset: 3px;
        }

        .dash-link:hover,
        .dash-inline-link:hover {
            color: var(--color-accent-hover);
            text-decoration: underline;
        }

        [data-bs-theme=dark] .dash-link {
            color: var(--tblr-link-color);
        }

        /* Tautan di kepala kartu tampil sebagai tombol kecil di pojok kanan atas. */
        .dash-card-head > .dash-link {
            margin-left: auto;
            padding: 6px 10px;
            border: 1px solid var(--color-rule);
            border-radius: 8px;
            background: var(--color-surface-2);
            color: var(--color-ink-2);
            text-decoration: none;
            transition: background-color var(--dur-short) var(--ease-out), border-color var(--dur-short) var(--ease-out);
        }

        .dash-card-head > .dash-link:hover {
            border-color: var(--color-rule-2);
            background: var(--color-rule);
            color: var(--color-ink);
            text-decoration: none;
        }

        .dash-card-head > .dash-link:active {
            background: var(--color-rule-2);
        }

        .dash-empty {
            margin: 0;
            padding: var(--space-sm) 0;
            color: var(--color-muted);
            font-size: 0.8125rem;
        }

        /* ── Tab ringkas (segmented) ── */
        .dash-tabs {
            display: flex;
            gap: 2px;
            margin: 0;
            padding: 2px;
            list-style: none;
            background: var(--color-surface-2);
            border: 1px solid var(--color-rule);
            border-radius: 8px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .dash-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            color: var(--color-muted);
            font-size: 0.8125rem;
            font-weight: 500;
            white-space: nowrap;
            text-decoration: none;
            transition: background-color var(--dur-short) var(--ease-out), color var(--dur-short) var(--ease-out);
        }

        .dash-tab:hover {
            color: var(--color-ink);
            text-decoration: none;
        }

        .dash-tab.active {
            background: var(--color-surface);
            color: var(--color-ink);
            box-shadow: 0 0 0 1px var(--color-rule), var(--shadow-lift);
        }

        .dash-tab-count {
            min-width: 20px;
            padding: 0 6px;
            border-radius: 10px;
            background: var(--color-rule);
            color: var(--color-ink-2);
            font-size: 0.6875rem;
            font-weight: 600;
            line-height: 18px;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }

        :is(.dash-tab, .dash-link, .dash-inline-link, .row-action, .approval-count, .quick-action, .cal-nav-btn, .compact-item):focus-visible,
        .calendar-day-cell:focus-visible {
            outline: 2px solid var(--color-focus);
            outline-offset: 2px;
        }

        /* ── Kehadiran ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: var(--space-sm);
        }

        .stat {
            --tone: var(--color-muted);
            min-width: 0;
            padding: var(--space-sm);
            border: 1px solid var(--color-rule);
            border-radius: 10px;
        }

        .stat--primary { --tone: var(--color-accent); }
        .stat--warning { --tone: var(--color-warning); }
        .stat--danger { --tone: var(--color-danger); }

        [data-bs-theme=dark] .stat--primary { --tone: var(--tblr-link-color); }

        .stat-top {
            display: flex;
            align-items: center;
            gap: var(--space-xs);
            min-width: 0;
        }

        .stat-icon {
            display: inline-flex;
            flex: 0 0 28px;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: color-mix(in oklab, var(--tone) 12%, transparent);
            color: var(--tone);
        }

        .stat-label {
            color: var(--color-ink-2);
            font-size: 0.8125rem;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-value {
            margin-top: var(--space-xs);
            color: var(--color-ink);
            font-size: 1.75rem;
            font-weight: 600;
            line-height: 1.1;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
        }

        .stat-of {
            color: var(--color-muted);
            font-size: 0.9375rem;
            font-weight: 500;
            letter-spacing: 0;
        }

        .stat-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 4px 8px;
            margin-top: 4px;
            min-height: 18px;
            font-size: 0.75rem;
        }

        .stat-pct {
            color: var(--tone);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .stat-delta {
            color: var(--color-muted);
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .stat-delta.is-good { color: var(--color-success); }
        .stat-delta.is-bad { color: var(--color-danger); }

        .stat-secondary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--space-xs) var(--space-lg);
            margin-top: var(--space-sm);
            padding: var(--space-xs) var(--space-sm);
            border-radius: 8px;
            background: var(--color-surface-2);
            font-size: 0.8125rem;
        }

        .stat-secondary-item {
            display: inline-flex;
            align-items: baseline;
            gap: 6px;
        }

        .stat-secondary-label { color: var(--color-muted); }

        .stat-secondary-value {
            color: var(--color-ink);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .stat-secondary-delta {
            color: var(--color-muted);
            font-size: 0.75rem;
        }

        .stat-secondary-note {
            margin-left: auto;
            color: var(--color-muted);
            font-size: 0.75rem;
        }

        .dash-trend {
            margin-top: var(--space-md);
            padding-top: var(--space-sm);
            border-top: 1px solid var(--color-rule);
        }

        .dash-trend-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-xs);
        }

        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-md);
            margin: 0;
            padding: 0;
            list-style: none;
            color: var(--color-muted);
            font-size: 0.75rem;
        }

        .chart-legend li {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .legend-swatch {
            flex: 0 0 10px;
            width: 10px;
            height: 10px;
            border-radius: 3px;
            background: var(--swatch, var(--color-muted));
        }

        .chart-box {
            min-width: 0;
        }

        /* ── Daftar karyawan (hybrid list/tabel) ── */
        .people-list {
            max-height: 368px;
            overflow-y: auto;
            margin-inline: calc(-1 * var(--space-lg));
            scrollbar-width: thin;
        }

        .people-row {
            display: grid;
            grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.3fr) minmax(0, 1.3fr) minmax(0, 1.2fr) 32px;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-xs) var(--space-lg);
            border-top: 1px solid var(--color-rule);
        }

        .people-row:not(.people-row--head):hover {
            background: var(--color-surface-2);
        }

        .people-row--head {
            position: sticky;
            top: 0;
            z-index: 1;
            padding-block: 6px;
            border-top: 0;
            background: var(--color-surface);
            color: var(--color-muted);
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .people-person {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            min-width: 0;
        }

        .people-person .avatar,
        .approval-item .avatar,
        .rank-item .avatar,
        .compact-item .avatar {
            flex: 0 0 auto;
            box-shadow: 0 0 0 1px var(--color-rule);
        }

        .people-name {
            color: var(--color-ink);
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .people-sub {
            color: var(--color-muted);
            font-size: 0.75rem;
            line-height: 1.3;
        }

        .people-sub-mobile {
            display: none;
        }

        .people-cell {
            min-width: 0;
            color: var(--color-ink-2);
            font-size: 0.8125rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .people-date {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
            font-variant-numeric: tabular-nums;
        }

        .row-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: var(--color-muted);
            transition: background-color var(--dur-short) var(--ease-out), color var(--dur-short) var(--ease-out);
        }

        .row-action:hover {
            background: var(--color-rule);
            color: var(--color-ink);
        }

        .pill {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 10px;
            font-size: 0.6875rem;
            font-weight: 500;
            line-height: 18px;
            white-space: nowrap;
            --pill: var(--color-muted);
            background: color-mix(in oklab, var(--pill) 12%, transparent);
            color: color-mix(in oklab, var(--pill) 85%, var(--color-ink));
        }

        .pill--success { --pill: var(--color-success); }
        .pill--warning { --pill: var(--color-warning); }
        .pill--danger { --pill: var(--color-danger); }

        /* ── Pusat persetujuan ── */
        .approval-summary {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: var(--space-xs);
            margin: 0 0 var(--space-sm);
            padding: 0;
            list-style: none;
        }

        .approval-count {
            display: flex;
            flex-direction: column;
            gap: 2px;
            padding: var(--space-xs) var(--space-sm);
            border: 1px solid var(--color-rule);
            border-radius: 10px;
            color: var(--color-muted);
            text-decoration: none;
            transition: border-color var(--dur-short) var(--ease-out), background-color var(--dur-short) var(--ease-out);
        }

        .approval-count:hover {
            border-color: var(--color-rule-2);
            background: var(--color-surface-2);
            text-decoration: none;
        }

        .approval-count.active {
            border-color: var(--color-accent);
            background: var(--color-accent-tint);
            box-shadow: inset 0 0 0 1px var(--color-accent);
        }

        .approval-count-value {
            color: var(--color-ink);
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.2;
            font-variant-numeric: tabular-nums;
        }

        .approval-count-label {
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .approval-list,
        .compact-list {
            display: flex;
            flex-direction: column;
        }

        .approval-item,
        .compact-item,
        .rank-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            min-width: 0;
            padding: var(--space-xs) 0;
            border-top: 1px solid var(--color-rule);
            color: inherit;
            text-decoration: none;
        }

        .approval-item:first-child,
        .compact-item:first-child,
        .rank-item:first-child {
            border-top: 0;
        }

        a.compact-item:hover {
            text-decoration: none;
        }

        a.compact-item:hover .people-name {
            color: var(--color-accent);
        }

        .approval-review {
            flex: 0 0 auto;
            --tblr-btn-bg: var(--color-surface);
            --tblr-btn-border-color: var(--color-rule-2);
            --tblr-btn-color: var(--color-ink-2);
            --tblr-btn-hover-bg: var(--color-surface-2);
            --tblr-btn-hover-color: var(--color-ink);
        }

        .compact-list--scroll {
            max-height: 232px;
            overflow-y: auto;
        }

        .people-date-chip {
            flex: 0 0 auto;
            color: var(--color-muted);
            font-size: 0.75rem;
            font-variant-numeric: tabular-nums;
        }

        /* ── Analitik ── */
        .figure-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: var(--space-sm);
            margin: 0 0 var(--space-sm);
        }

        .figure-row > div {
            padding-left: var(--space-sm);
            border-left: 2px solid var(--color-rule);
        }

        .figure-row dt {
            color: var(--color-muted);
            font-size: 0.75rem;
            font-weight: 400;
        }

        .figure-row dd {
            margin: 0;
            color: var(--color-ink);
            font-size: 1.25rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .rank-list {
            margin: 0;
            padding: 0;
            list-style: none;
            max-height: 260px;
            overflow-y: auto;
        }

        .rank-no {
            flex: 0 0 24px;
            color: var(--color-muted);
            font-size: 0.8125rem;
            font-weight: 600;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }

        .rank-item:nth-child(-n + 3) .rank-no {
            color: var(--color-accent);
        }

        .rank-score {
            flex: 0 0 auto;
            color: var(--color-ink);
            font-size: 0.8125rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        /* ── Perlu perhatian ── */
        .dash-attention .dash-card-head {
            margin-bottom: var(--space-sm);
        }

        .attention-icon {
            display: inline-flex;
            color: var(--color-warning);
        }

        .dash-attention.is-clear .attention-icon {
            color: var(--color-success);
        }

        .attention-list {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .attention-item {
            --level: var(--color-warning);
            padding: var(--space-sm);
            border: 1px solid color-mix(in oklab, var(--level) 30%, var(--color-rule));
            border-radius: 10px;
            background: color-mix(in oklab, var(--level) 7%, var(--color-surface));
            color: var(--color-ink-2);
            font-size: 0.8125rem;
        }

        .attention-item--danger {
            --level: var(--color-danger);
        }

        .attention-level {
            display: block;
            margin-bottom: 2px;
            color: color-mix(in oklab, var(--level) 80%, var(--color-ink));
            font-size: 0.6875rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        /* ── Aksi cepat ── */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: var(--space-xs);
        }

        /* Di kolom samping desktop cukup sempit: tampil sebagai daftar agar teks tidak terpotong. */
        @media (min-width: 1200px) {
            .quick-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .quick-action {
            display: grid;
            grid-template-columns: 32px minmax(0, 1fr);
            grid-template-rows: auto auto;
            column-gap: var(--space-xs);
            align-items: center;
            padding: var(--space-sm);
            border: 1px solid var(--color-rule);
            border-radius: 10px;
            color: var(--color-ink);
            text-decoration: none;
            transition: border-color var(--dur-short) var(--ease-out), background-color var(--dur-short) var(--ease-out);
        }

        .quick-action:hover {
            border-color: var(--color-rule-2);
            background: var(--color-surface-2);
            text-decoration: none;
        }

        .quick-action:active {
            background: var(--color-rule);
        }

        .quick-icon {
            grid-row: 1 / span 2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--color-accent-tint);
            color: var(--color-accent);
        }

        [data-bs-theme=dark] .quick-icon {
            color: var(--tblr-link-color);
        }

        .quick-title {
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quick-desc {
            color: var(--color-muted);
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Kalender ── */
        .cal-nav {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            padding: 2px;
            border: 1px solid var(--color-rule);
            border-radius: 8px;
        }

        .cal-nav-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 26px;
            padding: 0 6px;
            border-radius: 6px;
            color: var(--color-ink-2);
            font-size: 0.8125rem;
            text-decoration: none;
        }

        .cal-nav-btn:hover {
            background: var(--color-surface-2);
            color: var(--color-ink);
            text-decoration: none;
        }

        .cal-nav-today {
            font-weight: 500;
        }

        .cal-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 2px;
            margin: 0 -2px;
        }

        .cal-table th {
            padding: 2px 0 6px;
            color: var(--color-muted);
            font-size: 0.6875rem;
            font-weight: 600;
            text-align: center;
        }

        .cal-table th.is-weekend {
            color: color-mix(in oklab, var(--color-danger) 60%, var(--color-muted));
        }

        .calendar-day-cell {
            height: 44px;
            padding: 3px 2px;
            border-radius: 8px;
            vertical-align: top;
            text-align: center;
            cursor: pointer;
            transition: background-color var(--dur-short) var(--ease-out);
        }

        .calendar-day-cell:hover {
            background: var(--color-surface-2);
        }

        .cal-date {
            display: block;
            color: var(--color-ink);
            font-size: 0.8125rem;
            font-weight: 500;
            line-height: 1.4;
            font-variant-numeric: tabular-nums;
        }

        .calendar-day-cell.is-weekend .cal-date {
            color: var(--color-muted);
        }

        .calendar-day-cell.is-outside .cal-date {
            color: var(--color-rule-2);
        }

        .calendar-day-cell.is-holiday .cal-date,
        .cal-date-holiday {
            color: var(--color-danger);
            font-weight: 600;
        }

        .calendar-day-cell.is-today {
            background: var(--color-accent-tint);
            box-shadow: inset 0 0 0 1px var(--color-accent);
        }

        .calendar-day-cell.is-today .cal-date {
            color: var(--color-accent);
            font-weight: 700;
        }

        [data-bs-theme=dark] .calendar-day-cell.is-today .cal-date {
            color: var(--color-ink);
        }

        .cal-marks {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 1px;
            min-height: 14px;
        }

        .cal-mark {
            display: inline-block;
            min-width: 13px;
            padding: 0 2px;
            border-radius: 3px;
            background: var(--color-rule);
            color: var(--color-ink-2);
            font-size: 0.5625rem;
            font-weight: 600;
            line-height: 13px;
            text-align: center;
        }

        .cal-dot {
            display: inline-block;
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: var(--color-accent);
        }

        .cal-legend {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-2xs) var(--space-sm);
            margin: var(--space-xs) 0 0;
            padding: 0;
            list-style: none;
            color: var(--color-muted);
            font-size: 0.75rem;
        }

        .cal-legend li {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* ── Aktivitas ── */
        .timeline {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .timeline-item {
            --dot: var(--color-muted);
            position: relative;
            padding: 0 0 var(--space-sm) var(--space-lg);
            color: var(--color-ink-2);
            font-size: 0.8125rem;
        }

        .timeline-item--warning { --dot: var(--color-warning); }
        .timeline-item--primary { --dot: var(--color-accent); }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: 4px;
            top: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--dot);
        }

        .timeline-item:not(:last-child)::after {
            content: "";
            position: absolute;
            left: 7px;
            top: 18px;
            bottom: 0;
            width: 2px;
            background: var(--color-rule);
        }

        .timeline-item strong {
            color: var(--color-ink);
            font-weight: 600;
        }

        /* ── Demografi ── */
        .demo-layout {
            display: grid;
            grid-template-columns: minmax(0, 320px) minmax(0, 1fr);
            align-items: center;
            gap: var(--space-lg);
        }

        .demo-legend {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
            max-width: 420px;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 0.8125rem;
        }

        .demo-legend li {
            display: grid;
            grid-template-columns: 10px minmax(0, 1fr) auto 56px;
            align-items: center;
            gap: var(--space-xs);
            padding-bottom: var(--space-xs);
            border-bottom: 1px solid var(--color-rule);
        }

        .demo-legend-label { color: var(--color-ink-2); }

        .demo-legend-value {
            color: var(--color-ink);
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .demo-legend-pct {
            color: var(--color-muted);
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* ── Responsif ── */
        @media (max-width: 991.98px) {
            .stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .approval-summary {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .figure-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .people-row {
                grid-template-columns: minmax(0, 1fr) minmax(0, 0.9fr) 32px;
            }

            .people-row--head span:nth-child(2),
            .people-row--head span:nth-child(3),
            .people-jabatan,
            .people-cabang {
                display: none;
            }

            .people-sub-mobile {
                display: inline;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-shell {
                padding-inline: var(--space-md);
            }

            .dash-card {
                padding: var(--space-md);
            }

            .people-list {
                margin-inline: calc(-1 * var(--space-md));
            }

            .people-row {
                padding-inline: var(--space-md);
            }

            .stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            /* 5 metrik di 2 kolom: yang terakhir (Alpha) mengisi satu baris penuh. */
            .stat:last-child {
                grid-column: 1 / -1;
            }

            .stat-secondary-note {
                margin-left: 0;
                flex-basis: 100%;
            }

            .demo-layout {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>


    @php
        $avatarKaryawan = fn ($foto) => $foto ? asset('storage/uploads/karyawan/' . $foto) : asset('assets/img/nophoto.png');
        $totalAktif = (int) ($jmlkaryawan ?? 0);
        // Rasio kehadiran dibanding karyawan yang terjadwal kerja hari ini (bukan yang libur).
        $terjadwal = (int) ($jmlDijadwalkan ?? 0);
        $belumAbsen = (int) ($jmlBelumAbsen ?? 0);
        $persen = fn ($n) => $terjadwal > 0 ? number_format($n / $terjadwal * 100, 1, ',', '.') . '%' : null;

        // Ikon (Tabler Icons, path statis).
        $ikon = [
            'hadir' => '<path d="M5 12l5 5l10 -10" />',
            'terlambat' => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7v5l3 3" />',
            'izin' => '<path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 13h6" /><path d="M9 17h3" />',
            'sakit' => '<path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" /><path d="M12 9v4" /><path d="M10 11h4" />',
            'alpha' => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M10 10l4 4m0 -4l-4 4" />',
            'karyawan' => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M16 19h6" /><path d="M19 16v6" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4" />',
            'jam' => '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7v5l3 3" />',
            'lembur' => '<path d="M3 3v18h18" /><path d="M20 18v3" /><path d="M16 16v5" /><path d="M12 13v8" /><path d="M8 16v5" /><path d="M3 11c6 0 5 -5 9 -5s3 5 9 5" />',
            'pengumuman' => '<path d="M18 8a3 3 0 0 1 0 6" /><path d="M10 8v11a1 1 0 0 1 -1 1h-1a1 1 0 0 1 -1 -1v-5" /><path d="M12 8h0l4.524 -3.77a.9 .9 0 0 1 1.476 .692v12.156a.9 .9 0 0 1 -1.476 .692l-4.524 -3.77h-8a1 1 0 0 1 -1 -1v-4a1 1 0 0 1 1 -1h8" />',
            'peringatan' => '<path d="M12 9v4" /><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z" /><path d="M12 16h.01" />',
            'aman' => '<path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3" /><path d="M9 12l2 2l4 -4" />',
            'panah' => '<path d="M9 6l6 6l-6 6" />',
        ];
        $svg = fn ($nama, $ukuran = 18) => '<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="' . $ukuran . '" height="' . $ukuran . '" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $ikon[$nama] . '</svg>';

        // ── Kehadiran ──
        $metrikUtama = [
            ['key' => 'hadir', 'label' => 'Hadir', 'value' => (int) ($jmlhadir ?? 0), 'tone' => 'primary', 'ikon' => 'hadir', 'goodWhenUp' => true, 'rasio' => true],
            ['key' => 'terlambat', 'label' => 'Terlambat', 'value' => (int) ($jmlterlambat ?? 0), 'tone' => 'warning', 'ikon' => 'terlambat', 'goodWhenUp' => false, 'rasio' => false],
            ['key' => 'izin', 'label' => 'Izin', 'value' => (int) ($jmlizin ?? 0), 'tone' => 'neutral', 'ikon' => 'izin', 'goodWhenUp' => false, 'rasio' => false],
            ['key' => 'sakit', 'label' => 'Sakit', 'value' => (int) ($jmlsakit ?? 0), 'tone' => 'neutral', 'ikon' => 'sakit', 'goodWhenUp' => false, 'rasio' => false],
            ['key' => 'tanpa_keterangan', 'label' => 'Alpha', 'value' => (int) ($jmlTanpaKeterangan ?? $jmltidakabsen ?? 0), 'tone' => 'danger', 'ikon' => 'alpha', 'goodWhenUp' => false, 'rasio' => true],
        ];
        $metrikSekunder = [
            ['key' => 'cuti', 'label' => 'Cuti', 'value' => (int) ($jmlcuti ?? 0)],
            ['key' => 'roster', 'label' => 'Roster', 'value' => (int) ($jmlroster ?? 0)],
            ['key' => 'dinas_luar', 'label' => 'Dinas Luar', 'value' => (int) ($jmlDinasLuar ?? 0)],
        ];
        $pembanding = fn ($key) => $realtimeComparison[$key] ?? ['prev_week' => 0, 'delta' => 0, 'prev_week_date' => null];

        // ── Perlu perhatian (diurutkan dari yang paling kritis) ──
        $kontrakMepet = collect($karyawanSisaKontrak ?? [])->filter()->filter(
            fn ($k) => data_get($k, 'tanggal_habis_kontrak')
                && \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse(data_get($k, 'tanggal_habis_kontrak'))->startOfDay(), false) <= 7,
        )->count();
        $perhatian = collect();
        if ($isLateMassive ?? false) {
            $perhatian->push(['level' => 'danger', 'teks' => ($lateMassiveCount ?? 0) . ' karyawan terlambat hari ini (keterlambatan masif).', 'url' => auth('user')->user()->can('presensi-monitoring-view-admin') ? route('presensi.monitoring') : null, 'cta' => 'Lihat presensi']);
        }
        if (($spWarningCount ?? 0) > 0) {
            $perhatian->push(['level' => 'warning', 'teks' => $spWarningCount . ' karyawan memiliki SP yang akan berakhir dalam 7 hari.', 'url' => auth('user')->user()->can('surat-peringatan-view-admin') ? route('suratperingatan.index') : null, 'cta' => 'Lihat detail']);
        }
        if ($kontrakMepet > 0) {
            $perhatian->push(['level' => 'warning', 'teks' => $kontrakMepet . ' kontrak karyawan berakhir dalam 7 hari atau sudah lewat.', 'url' => '#tab-kary-kontrak', 'cta' => 'Lihat daftar', 'tab' => true]);
        }

        // ── Persetujuan ──
        $antrean = [
            ['id' => 'izin', 'label' => 'Izin', 'rows' => collect($izinPending ?? []), 'url' => route('presensi.izinsakit')],
            ['id' => 'lembur', 'label' => 'Lembur', 'rows' => collect($lemburPending ?? []), 'url' => route('admin.lembur.approval')],
            ['id' => 'dinas', 'label' => 'Dinas', 'rows' => collect($dinasLuarPending ?? []), 'url' => route('dinasluars.approval')],
            ['id' => 'kpi', 'label' => 'KPI', 'rows' => collect($kpiPending ?? []), 'url' => route('kpi.indikator.index')],
            ['id' => 'bpjs', 'label' => 'BPJS', 'rows' => collect($bpjsPendingList ?? []), 'total' => (int) ($bpjsPendingCount ?? 0), 'url' => route('admin.bpjs.index')],
            ['id' => 'gaji', 'label' => 'Kenaikan Gaji', 'rows' => collect($salaryIncreasePendingList ?? []), 'total' => (int) ($salaryIncreasePendingCount ?? 0), 'url' => route('admin.kenaikan_gaji.index', ['status' => 'pending'])],
        ];
        $jenisIzin = function ($item) {
            $kode = strtolower((string) ($item->status ?? ''));
            return match (true) {
                $kode === 's' => 'Sakit',
                $kode === 't' => 'Izin terlambat',
                $kode === 'p' => 'Izin pulang cepat',
                $kode === 'c' || !empty($item->kode_cuti) => $item->jenis_cuti_formal ?? ($item->nama_cuti ?? 'Cuti'),
                default => 'Izin absen',
            };
        };

        // ── Turnover 12 bulan ──
        $turnoverRows = collect($turnoverData ?? []);
        $turnoverMasuk = (int) $turnoverRows->sum('masuk');
        $turnoverKeluar = (int) $turnoverRows->sum('keluar');
        $turnoverKontrak = (int) $turnoverRows->sum('kontrak_habis');
        $turnoverRataRata = $turnoverRows->count() > 0 ? round((float) $turnoverRows->avg('turnover_rate'), 1) : 0;
        $turnoverAdaData = ($turnoverMasuk + $turnoverKeluar + $turnoverKontrak) > 0;
        $kpiCabangAdaData = collect($kpiCabangSeries ?? [])->flatten()->filter(fn ($v) => (float) $v > 0)->isNotEmpty();

        // ── Demografi ──
        $umur = $dataSebaranUmur ?? null;
        $gender = $dataSebaranGender ?? null;
        $pend = $dataPendidikan ?? null;
        $demografi = [
            'umur' => ['label' => 'Umur', 'data' => ['< 20' => optional($umur)->umur_under_20 ?? 0, '20–29' => optional($umur)->umur_20_29 ?? 0, '30–39' => optional($umur)->umur_30_39 ?? 0, '40–49' => optional($umur)->umur_40_49 ?? 0, '≥ 50' => optional($umur)->umur_50_plus ?? 0]],
            'gender' => ['label' => 'Gender', 'data' => ['Laki-laki' => optional($gender)->laki_laki ?? 0, 'Perempuan' => optional($gender)->perempuan ?? 0]],
            'pendidikan' => ['label' => 'Pendidikan', 'data' => ['SMA' => optional($pend)->sma ?? 0, 'D3' => optional($pend)->d3 ?? 0, 'S1' => optional($pend)->s1 ?? 0, 'S2' => optional($pend)->s2 ?? 0]],
        ];

        // ── Aktivitas terbaru (dari antrean yang sudah ada) ──
        $aktivitas = collect()
            ->merge(collect($izinPending ?? [])->map(fn ($i) => ['time' => $i->created_at ?? now(), 'jenis' => $jenisIzin($i), 'nama' => $i->nama_lengkap ?? '-', 'status' => 'menunggu review', 'tone' => 'warning']))
            ->merge(collect($lemburPending ?? [])->map(fn ($l) => ['time' => $l->created_at ?? now(), 'jenis' => 'Lembur', 'nama' => $l->nama_lengkap ?? '-', 'status' => 'masuk antrean', 'tone' => 'primary']))
            ->merge(collect($dinasLuarPending ?? [])->map(fn ($d) => ['time' => $d->created_at ?? now(), 'jenis' => 'Dinas luar', 'nama' => $d->nama_lengkap ?? '-', 'status' => 'menunggu persetujuan', 'tone' => 'primary']))
            ->sortByDesc(fn ($a) => \Carbon\Carbon::parse($a['time'])->timestamp)
            ->take(6);
    @endphp

    <div class="page-body">
        <div class="container-fluid dashboard-shell">
            <div class="dash-grid">

                {{-- ═══ KOLOM UTAMA ═══ --}}
                <div class="dash-main">

                    {{-- 1. KEHADIRAN: ringkasan + tren --}}
                    <section class="dash-card dash-attendance" aria-labelledby="judul-kehadiran">
                        <header class="dash-card-head">
                            <div class="min-w-0">
                                <h2 class="dash-card-title" id="judul-kehadiran">Kehadiran hari ini</h2>
                                <p class="dash-card-sub">
                                    {{ now()->locale('id')->translatedFormat('l, d F Y') }} · {{ $terjadwal }} terjadwal kerja ·
                                    <button type="button" class="dash-inline-link" data-bs-toggle="modal"
                                        data-bs-target="#modal-karyawan-aktif">{{ $totalAktif }} karyawan aktif</button>
                                </p>
                            </div>
                            @can('presensi-monitoring-view-admin')
                                <a href="{{ route('presensi.monitoring') }}" class="dash-link">Monitoring presensi {!! $svg('panah', 14) !!}</a>
                            @endcan
                        </header>

                        <div class="stat-grid">
                            @foreach ($metrikUtama as $m)
                                @php
                                    $cmp = $pembanding($m['key']);
                                    $delta = (int) round((float) ($cmp['delta'] ?? 0));
                                    $baik = $m['goodWhenUp'] ? $delta > 0 : $delta < 0;
                                @endphp
                                <div class="stat stat--{{ $m['tone'] }}">
                                    <div class="stat-top">
                                        <span class="stat-icon">{!! $svg($m['ikon'], 16) !!}</span>
                                        <span class="stat-label">{{ $m['label'] }}</span>
                                    </div>
                                    <div class="stat-value">
                                        {{ $m['value'] }}
                                        @if ($m['rasio'] && $terjadwal > 0)
                                            <span class="stat-of" title="Karyawan terjadwal kerja hari ini">/ {{ $terjadwal }}</span>
                                        @endif
                                    </div>
                                    <div class="stat-meta">
                                        @if ($m['rasio'] && $persen($m['value']))
                                            <span class="stat-pct">{{ $persen($m['value']) }}</span>
                                        @endif
                                        @if ($m['key'] === 'tanpa_keterangan' && $belumAbsen > 0)
                                            <span class="stat-delta" title="Batas jam masuk belum lewat">{{ $belumAbsen }} belum absen</span>
                                        @elseif ($delta !== 0)
                                            <span class="stat-delta {{ $baik ? 'is-good' : 'is-bad' }}"
                                                title="Dibanding minggu lalu ({{ number_format((float) ($cmp['prev_week'] ?? 0), 0) }})">
                                                {{ $delta > 0 ? '▲' : '▼' }} {{ abs($delta) }}
                                            </span>
                                        @else
                                            <span class="stat-delta" title="Sama dengan minggu lalu">= minggu lalu</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="stat-secondary">
                            @foreach ($metrikSekunder as $m)
                                @php $delta = (int) round((float) ($pembanding($m['key'])['delta'] ?? 0)); @endphp
                                <div class="stat-secondary-item">
                                    <span class="stat-secondary-label">{{ $m['label'] }}</span>
                                    <span class="stat-secondary-value">{{ $m['value'] }}</span>
                                    @if ($delta !== 0)
                                        <span class="stat-secondary-delta">{{ $delta > 0 ? '+' : '−' }}{{ abs($delta) }}</span>
                                    @endif
                                </div>
                            @endforeach
                            <span class="stat-secondary-note">Perubahan dibanding hari yang sama minggu lalu</span>
                        </div>

                        <div class="dash-trend">
                            <div class="dash-trend-head">
                                <h3 class="dash-subtitle">Tren 7 hari terakhir</h3>
                                <ul class="chart-legend" aria-hidden="true">
                                    <li><span class="legend-swatch" style="--swatch: var(--chart-primary)"></span>Hadir</li>
                                    <li><span class="legend-swatch" style="--swatch: var(--chart-warning)"></span>Terlambat</li>
                                    <li><span class="legend-swatch" style="--swatch: var(--chart-danger)"></span>Alpha</li>
                                </ul>
                            </div>
                            <div id="chart-tren-kehadiran" class="chart-box" style="height: 180px;"
                                role="img" aria-label="Grafik tren hadir, terlambat, dan alpha 7 hari terakhir"></div>
                        </div>
                    </section>

                    {{-- 2. KARYAWAN: masuk / keluar / sisa kontrak --}}
                    <section class="dash-card" aria-labelledby="judul-karyawan">
                        <header class="dash-card-head">
                            <h2 class="dash-card-title" id="judul-karyawan">Karyawan</h2>
                            <ul class="dash-tabs" role="tablist">
                                <li role="presentation"><a href="#tab-kary-masuk" class="dash-tab active" data-bs-toggle="tab" role="tab" aria-selected="true">Masuk <span class="dash-tab-count">{{ count($karyawanMasuk ?? []) }}</span></a></li>
                                <li role="presentation"><a href="#tab-kary-keluar" class="dash-tab" data-bs-toggle="tab" role="tab" aria-selected="false">Keluar <span class="dash-tab-count">{{ count($karyawanKeluar ?? []) }}</span></a></li>
                                <li role="presentation"><a href="#tab-kary-kontrak" class="dash-tab" data-bs-toggle="tab" role="tab" aria-selected="false">Sisa kontrak <span class="dash-tab-count">{{ count($karyawanSisaKontrak ?? []) }}</span></a></li>
                            </ul>
                            @can('karyawan-view-admin')
                                <a href="{{ route('karyawan.index') }}" class="dash-link ms-auto">Lihat semua {!! $svg('panah', 14) !!}</a>
                            @endcan
                        </header>

                        <div class="tab-content">
                            @foreach ([
                                ['id' => 'tab-kary-masuk', 'rows' => $karyawanMasuk ?? [], 'kolom' => 'Tgl masuk', 'kosong' => 'Belum ada karyawan masuk dalam 3 bulan terakhir.', 'aktif' => true],
                                ['id' => 'tab-kary-keluar', 'rows' => $karyawanKeluar ?? [], 'kolom' => 'Tgl keluar', 'kosong' => 'Belum ada karyawan keluar dalam 3 bulan terakhir.', 'aktif' => false],
                                ['id' => 'tab-kary-kontrak', 'rows' => $karyawanSisaKontrak ?? [], 'kolom' => 'Kontrak berakhir', 'kosong' => 'Tidak ada kontrak habis dalam 3 bulan ke depan.', 'aktif' => false],
                            ] as $pane)
                                <div class="tab-pane {{ $pane['aktif'] ? 'active show' : '' }}" id="{{ $pane['id'] }}" role="tabpanel">
                                    @php $rows = collect($pane['rows'])->filter(); @endphp
                                    @if ($rows->isEmpty())
                                        <p class="dash-empty">{{ $pane['kosong'] }}</p>
                                    @else
                                        <div class="people-list" role="list">
                                            <div class="people-row people-row--head" aria-hidden="true">
                                                <span>Karyawan</span><span>Jabatan</span><span>Cabang</span><span>{{ $pane['kolom'] }}</span><span></span>
                                            </div>
                                            @foreach ($rows as $item)
                                                @php
                                                    $tautan = route('karyawan.show', data_get($item, 'nik'));
                                                    $status = null;
                                                    if ($pane['id'] === 'tab-kary-masuk') {
                                                        $tgl = $item->tmt && $item->tanggal_awal_kontrak
                                                            ? (\Carbon\Carbon::parse($item->tmt)->lt(\Carbon\Carbon::parse($item->tanggal_awal_kontrak)) ? $item->tmt : $item->tanggal_awal_kontrak)
                                                            : ($item->tmt ?? $item->tanggal_awal_kontrak);
                                                    } elseif ($pane['id'] === 'tab-kary-keluar') {
                                                        $tgl = data_get($item, 'tanggal_keluar') ?: data_get($item, 'updated_at');
                                                        if (data_get($item, 'tanggal_habis_kontrak') && \Carbon\Carbon::parse(data_get($item, 'tanggal_habis_kontrak'))->isPast()) {
                                                            $tautan = route('karyawan.monitoring.turnover');
                                                        }
                                                    } else {
                                                        $tgl = data_get($item, 'tanggal_habis_kontrak');
                                                        $sisaHari = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($tgl)->startOfDay(), false);
                                                        $status = $sisaHari < 0 ? ['Sudah habis', 'danger'] : ($sisaHari <= 7 ? [data_get($item, 'sisa_kontrak', $sisaHari . ' hari'), 'warning'] : [data_get($item, 'sisa_kontrak', $sisaHari . ' hari'), 'neutral']);
                                                        if ($sisaHari < 0) {
                                                            $tautan = route('karyawan.monitoring.turnover');
                                                        }
                                                    }
                                                    $nama = data_get($item, 'nama_lengkap', '-');
                                                @endphp
                                                <div class="people-row" role="listitem">
                                                    <div class="people-person">
                                                        <span class="avatar avatar-sm" style="background-image: url('{{ $avatarKaryawan(data_get($item, 'foto')) }}')"></span>
                                                        <div class="min-w-0">
                                                            <div class="people-name" title="{{ $nama }}">{{ $nama }}</div>
                                                            <div class="people-sub">NIK {{ data_get($item, 'nik', '-') }}<span class="people-sub-mobile"> · {{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span></div>
                                                        </div>
                                                    </div>
                                                    <div class="people-cell people-jabatan" title="{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</div>
                                                    <div class="people-cell people-cabang" title="{{ data_get($item, 'cabang.nama_cabang', '-') }}">{{ data_get($item, 'cabang.nama_cabang', '-') }}</div>
                                                    <div class="people-cell people-date">
                                                        {{ $tgl ? \Carbon\Carbon::parse($tgl)->locale('id')->translatedFormat('d M Y') : '-' }}
                                                        @if ($status)
                                                            <span class="pill pill--{{ $status[1] }}">{{ $status[0] }}</span>
                                                        @endif
                                                    </div>
                                                    <a href="{{ $tautan }}" class="row-action" aria-label="Detail {{ $nama }}" title="Detail">{!! $svg('panah', 16) !!}</a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- 3. PUSAT PERSETUJUAN --}}
                    <section class="dash-card" aria-labelledby="judul-persetujuan">
                        <header class="dash-card-head">
                            <h2 class="dash-card-title" id="judul-persetujuan">Pusat persetujuan</h2>
                            <span class="dash-card-sub ms-auto">{{ collect($antrean)->sum(fn ($a) => $a['total'] ?? $a['rows']->count()) }} menunggu</span>
                        </header>

                        <ul class="approval-summary" role="tablist">
                            @foreach ($antrean as $a)
                                <li role="presentation">
                                    <a href="#tab-approval-{{ $a['id'] }}" class="approval-count {{ $loop->first ? 'active' : '' }}"
                                        data-bs-toggle="tab" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                        <span class="approval-count-value">{{ $a['total'] ?? $a['rows']->count() }}</span>
                                        <span class="approval-count-label">{{ $a['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach ($antrean as $a)
                                @php $total = $a['total'] ?? $a['rows']->count(); @endphp
                                <div class="tab-pane {{ $loop->first ? 'active show' : '' }}" id="tab-approval-{{ $a['id'] }}" role="tabpanel">
                                    @if ($a['rows']->isEmpty())
                                        <p class="dash-empty">Tidak ada antrean {{ strtolower($a['label']) }} saat ini.</p>
                                    @else
                                        <div class="approval-list">
                                            @foreach ($a['rows']->take(5) as $item)
                                                @php
                                                    [$jenis, $detail, $review] = match ($a['id']) {
                                                        'izin' => [$jenisIzin($item), !empty($item->tgl_izin_dari) ? \Carbon\Carbon::parse($item->tgl_izin_dari)->locale('id')->translatedFormat('d F') : '-', $a['url']],
                                                        'lembur' => ['Lembur', ($item->total_jam ?? '-') . ' jam', $a['url']],
                                                        'dinas' => ['Dinas luar', \Illuminate\Support\Str::limit($item->lokasi_tujuan ?? '-', 32), $a['url']],
                                                        'kpi' => ['KPI', \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d M Y'), $a['url']],
                                                        'bpjs' => ['BPJS', ($item->requested_at ?? $item->created_at)?->locale('id')->translatedFormat('d M Y'), route('admin.bpjs.show', $item->id)],
                                                        'gaji' => ['Kenaikan gaji', (int) ($item->persentase ?? 0) . '% · ' . ($item->tanggal_pengajuan ?? $item->created_at)?->locale('id')->translatedFormat('d M Y'), route('admin.kenaikan_gaji.index', ['status' => 'pending', 'nama_karyawan' => $item->nik])],
                                                    };
                                                @endphp
                                                <div class="approval-item">
                                                    <span class="avatar avatar-sm" style="background-image: url('{{ $avatarKaryawan($item->foto ?? null) }}')"></span>
                                                    <div class="min-w-0 flex-fill">
                                                        <div class="people-name" title="{{ $item->nama_lengkap }}">{{ $item->nama_lengkap }}</div>
                                                        <div class="people-sub text-truncate">{{ $jenis }} · {{ $detail }}</div>
                                                    </div>
                                                    <a href="{{ $review }}" class="btn btn-sm approval-review">Review</a>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="dash-card-foot">
                                            @if ($total > 5)
                                                <span class="text-secondary small">+{{ $total - 5 }} lainnya</span>
                                            @endif
                                            <a href="{{ $a['url'] }}" class="dash-link ms-auto">Lihat semua {!! $svg('panah', 14) !!}</a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- 4. ANALITIK HR --}}
                    <section class="dash-card" aria-labelledby="judul-analitik">
                        <header class="dash-card-head">
                            <h2 class="dash-card-title" id="judul-analitik">Analitik HR</h2>
                            <ul class="dash-tabs" role="tablist">
                                <li role="presentation"><a href="#tab-chart-turnover" class="dash-tab active" data-bs-toggle="tab" role="tab" aria-selected="true">Turnover</a></li>
                                <li role="presentation"><a href="#tab-chart-kpi" class="dash-tab" data-bs-toggle="tab" role="tab" aria-selected="false">KPI cabang</a></li>
                                <li role="presentation"><a href="#tab-kpi-leaderboard" class="dash-tab" data-bs-toggle="tab" role="tab" aria-selected="false">Top KPI</a></li>
                            </ul>
                        </header>

                        <div class="tab-content">
                            <div class="tab-pane active show" id="tab-chart-turnover" role="tabpanel">
                                <dl class="figure-row">
                                    <div><dt>Turnover rate <span class="text-secondary">(rata-rata)</span></dt><dd>{{ number_format($turnoverRataRata, 1, ',', '.') }}%</dd></div>
                                    <div><dt>Karyawan masuk</dt><dd>{{ $turnoverMasuk }}</dd></div>
                                    <div><dt>Karyawan keluar</dt><dd>{{ $turnoverKeluar }}</dd></div>
                                    <div><dt>Kontrak habis</dt><dd>{{ $turnoverKontrak }}</dd></div>
                                </dl>
                                @if ($turnoverAdaData)
                                    <div id="chart-turnover" class="chart-box" style="height: 200px;" role="img"
                                        aria-label="Grafik turnover 12 bulan terakhir"></div>
                                @else
                                    <p class="dash-empty">Belum ada perubahan karyawan dalam 12 bulan terakhir.</p>
                                @endif
                                <div class="dash-card-foot">
                                    <span class="text-secondary small">12 bulan terakhir</span>
                                    @if (($turnoverAnomalyCount ?? 0) > 0)
                                        <span class="pill pill--warning">{{ $turnoverAnomalyCount }} karyawan keluar tanpa tanggal keluar</span>
                                    @endif
                                    <button type="button" class="dash-link ms-auto" data-bs-toggle="modal" data-bs-target="#modal-turnover">Lihat detail {!! $svg('panah', 14) !!}</button>
                                </div>
                            </div>
                            <div class="tab-pane" id="tab-chart-kpi" role="tabpanel">
                                @if ($kpiCabangAdaData)
                                    <div id="chart-kpi-by-cabang" class="chart-box" style="height: 220px;" role="img"
                                        aria-label="Grafik rata-rata KPI per cabang"></div>
                                @else
                                    <p class="dash-empty">Belum ada nilai KPI cabang pada periode ini.</p>
                                @endif
                            </div>
                            <div class="tab-pane" id="tab-kpi-leaderboard" role="tabpanel">
                                @php $leaderboard = collect($kpiLeaderboardQuery ?? [])->filter()->values(); @endphp
                                @if ($leaderboard->isEmpty())
                                    <p class="dash-empty">Belum ada data KPI.</p>
                                @else
                                    <ol class="rank-list">
                                        @foreach ($leaderboard as $rank => $kp)
                                            <li class="rank-item">
                                                <span class="rank-no">{{ $rank + 1 }}</span>
                                                <span class="avatar avatar-sm" style="background-image: url('{{ $avatarKaryawan($kp->foto ?? null) }}')"></span>
                                                <div class="min-w-0 flex-fill">
                                                    <div class="people-name" title="{{ $kp->nama_lengkap }}">{{ $kp->nama_lengkap }}</div>
                                                    <div class="people-sub text-truncate">{{ $kp->jabatan_nama ?? '-' }}</div>
                                                </div>
                                                <span class="rank-score">{{ number_format($kp->total_points ?? 0) }} poin</span>
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ═══ KOLOM SAMPING ═══ --}}
                <div class="dash-side">

                    {{-- PERLU PERHATIAN --}}
                    <section class="dash-card dash-attention {{ $perhatian->isEmpty() ? 'is-clear' : '' }}" aria-labelledby="judul-perhatian">
                        <header class="dash-card-head">
                            <span class="attention-icon">{!! $svg($perhatian->isEmpty() ? 'aman' : 'peringatan', 18) !!}</span>
                            <h2 class="dash-card-title" id="judul-perhatian">Perlu perhatian</h2>
                            @if ($perhatian->isNotEmpty())
                                <span class="dash-tab-count ms-auto">{{ $perhatian->count() }}</span>
                            @endif
                        </header>
                        @if ($perhatian->isEmpty())
                            <p class="dash-empty text-start">Tidak ada yang perlu ditindaklanjuti hari ini.</p>
                        @else
                            <ul class="attention-list">
                                @foreach ($perhatian as $p)
                                    <li class="attention-item attention-item--{{ $p['level'] }}">
                                        <span class="attention-level">{{ $p['level'] === 'danger' ? 'Kritis' : 'Peringatan' }}</span>
                                        <p class="mb-1">{{ $p['teks'] }}</p>
                                        @if ($p['url'])
                                            <a href="{{ $p['url'] }}" class="dash-link" @if (!empty($p['tab'])) data-show-tab @endif>{{ $p['cta'] }} {!! $svg('panah', 14) !!}</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    {{-- AKSI CEPAT --}}
                    <section class="dash-card" aria-labelledby="judul-aksi">
                        <header class="dash-card-head">
                            <h2 class="dash-card-title" id="judul-aksi">Aksi cepat</h2>
                        </header>
                        <div class="quick-grid">
                            @can('karyawan-view-admin')
                                <a href="{{ route('karyawan.index') }}" class="quick-action">
                                    <span class="quick-icon">{!! $svg('karyawan') !!}</span>
                                    <span class="quick-title">Tambah karyawan</span>
                                    <span class="quick-desc">Data karyawan baru</span>
                                </a>
                            @endcan
                            @can('jam-kerja-view-admin')
                                <a href="{{ route('konfigurasi.jamkerja') }}" class="quick-action">
                                    <span class="quick-icon">{!! $svg('jam') !!}</span>
                                    <span class="quick-title">Jam kerja</span>
                                    <span class="quick-desc">Kelola jadwal kerja</span>
                                </a>
                            @endcan
                            @can('laporan-view-admin')
                                <a href="{{ route('admin.lembur.rekap') }}" class="quick-action">
                                    <span class="quick-icon">{!! $svg('lembur') !!}</span>
                                    <span class="quick-title">Rekap lembur</span>
                                    <span class="quick-desc">Lihat rekap lembur</span>
                                </a>
                            @endcan
                            @can('pengumuman-view-admin')
                                <a href="{{ route('pengumuman.index') }}" class="quick-action">
                                    <span class="quick-icon">{!! $svg('pengumuman') !!}</span>
                                    <span class="quick-title">Pengumuman</span>
                                    <span class="quick-desc">Buat pengumuman</span>
                                </a>
                            @endcan
                        </div>
                    </section>

                    {{-- KALENDER HR (struktur & kelas dipakai JS navigasi/detail di bawah) --}}
                    <section class="dash-card" id="hr-calendar-card" aria-labelledby="calendarCardTitle">
                        @php
                            $calendarNow = isset($calendarMonth) ? \Carbon\Carbon::createFromFormat('Y-m', $calendarMonth) : now();
                            $startCal = $calendarNow->copy()->startOfMonth()->startOfWeek();
                            $endCal = $calendarNow->copy()->endOfMonth()->endOfWeek();
                            $daysCal = [];
                            for ($d = $startCal->copy(); $d->lte($endCal); $d->addDay()) {
                                $daysCal[] = $d->copy();
                            }
                        @endphp
                        <header class="dash-card-head">
                            <div class="min-w-0">
                                <h2 class="dash-card-title" id="calendarCardTitle">Kalender HR</h2>
                                <p class="dash-card-sub">{{ $calendarLabel ?? $calendarNow->locale('id')->translatedFormat('F Y') }}</p>
                            </div>
                            <nav class="cal-nav ms-auto" aria-label="Navigasi bulan">
                                <a href="{{ route('dashboard.admin', ['bulan' => $prevMonth ?? $calendarNow->copy()->subMonth()->format('Y-m')]) }}"
                                    class="cal-nav-btn js-calendar-nav" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">‹</a>
                                <a href="{{ route('dashboard.admin', ['bulan' => now()->format('Y-m')]) }}"
                                    class="cal-nav-btn cal-nav-today js-calendar-nav">Hari ini</a>
                                <a href="{{ route('dashboard.admin', ['bulan' => $nextMonth ?? $calendarNow->copy()->addMonth()->format('Y-m')]) }}"
                                    class="cal-nav-btn js-calendar-nav" aria-label="Bulan berikutnya" title="Bulan berikutnya">›</a>
                            </nav>
                        </header>
                        <table class="cal-table">
                            <thead>
                                <tr>
                                    @foreach (['Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb', 'Mg'] as $i => $hari)
                                        <th scope="col" class="{{ $i >= 5 ? 'is-weekend' : '' }}">{{ $hari }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (array_chunk($daysCal, 7) as $week)
                                    <tr>
                                        @foreach ($week as $day)
                                            @php
                                                $dayKey = $day->format('Y-m-d');
                                                $ev = $calendarEvents[$dayKey] ?? ['holiday' => false, 'izin' => 0, 'sakit' => 0, 'cuti' => 0, 'dinas' => 0, 'kpi_cutoff' => false];
                                                $penanda = collect(['I' => ['izin', 'Izin'], 'S' => ['sakit', 'Sakit'], 'C' => ['cuti', 'Cuti'], 'D' => ['dinas', 'Dinas']])
                                                    ->filter(fn ($v) => ($ev[$v[0]] ?? 0) > 0);
                                                $kelas = collect([
                                                    'calendar-day-cell',
                                                    $day->isToday() ? 'is-today' : null,
                                                    $day->month !== $calendarNow->month ? 'is-outside' : null,
                                                    $day->isWeekend() ? 'is-weekend' : null,
                                                    !empty($ev['holiday']) ? 'is-holiday' : null,
                                                ])->filter()->implode(' ');
                                                $label = $day->locale('id')->translatedFormat('l, d F Y') . ($penanda->isNotEmpty() ? ': ' . $penanda->map(fn ($v) => $ev[$v[0]] . ' ' . $v[1])->implode(', ') : '') . (!empty($ev['holiday']) ? ', libur nasional' : '');
                                            @endphp
                                            <td class="{{ $kelas }}" data-date="{{ $dayKey }}" data-day="{{ $day->format('d M Y') }}"
                                                tabindex="0" role="button" aria-label="{{ $label }}">
                                                <span class="cal-date">{{ $day->format('j') }}</span>
                                                <span class="cal-marks">
                                                    @foreach ($penanda as $huruf => $v)
                                                        <span class="cal-mark" title="{{ $v[1] }}: {{ $ev[$v[0]] }}">{{ $huruf }}</span>
                                                    @endforeach
                                                    @if (!empty($ev['kpi_cutoff']))
                                                        <span class="cal-dot" title="Cutoff periode"></span>
                                                    @endif
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <script type="application/json" id="calendarDetailsPayload">@json($calendarDailyDetails ?? [])</script>
                        <ul class="cal-legend">
                            <li><span class="cal-mark">I</span> Izin</li>
                            <li><span class="cal-mark">S</span> Sakit</li>
                            <li><span class="cal-mark">C</span> Cuti</li>
                            <li><span class="cal-mark">D</span> Dinas</li>
                            <li><span class="cal-date-holiday">1</span> Libur</li>
                            <li><span class="cal-dot"></span> Cutoff</li>
                        </ul>
                    </section>

                    {{-- INFORMASI HR --}}
                    <section class="dash-card" aria-labelledby="judul-info">
                        @php $ultah = collect($ulangTahunBulanIni ?? []); @endphp
                        <header class="dash-card-head">
                            <h2 class="dash-card-title" id="judul-info">Informasi HR</h2>
                            <ul class="dash-tabs ms-auto" role="tablist">
                                <li role="presentation"><a href="#tab-feed-pengumuman" class="dash-tab active" data-bs-toggle="tab" role="tab" aria-selected="true">Pengumuman</a></li>
                                <li role="presentation"><a href="#tab-feed-momen" class="dash-tab" data-bs-toggle="tab" role="tab" aria-selected="false">Ulang tahun <span class="dash-tab-count">{{ $ultah->count() }}</span></a></li>
                            </ul>
                        </header>
                        <div class="tab-content">
                            <div class="tab-pane active show" id="tab-feed-pengumuman" role="tabpanel">
                                @php $pengumuman = collect($pengumumanAktif ?? [])->filter(); @endphp
                                @if ($pengumuman->isEmpty())
                                    <p class="dash-empty">Belum ada pengumuman pada periode ini.</p>
                                @else
                                    <div class="compact-list">
                                        @foreach ($pengumuman->take(4) as $pg)
                                            <a href="{{ route('pengumuman.index') }}" class="compact-item">
                                                <div class="min-w-0 flex-fill">
                                                    <div class="people-name" title="{{ $pg->judul }}">{{ $pg->judul }}</div>
                                                    <div class="people-sub">{{ ($pg->tanggal_mulai)?->locale('id')->translatedFormat('d M') ?? '-' }} – {{ ($pg->tanggal_selesai)?->locale('id')->translatedFormat('d M Y') ?? '-' }}</div>
                                                </div>
                                                <span class="pill {{ (int) ($pg->is_active ?? 0) === 1 ? 'pill--success' : 'pill--neutral' }}">{{ (int) ($pg->is_active ?? 0) === 1 ? 'Aktif' : 'Nonaktif' }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="tab-pane" id="tab-feed-momen" role="tabpanel">
                                @if ($ultah->isEmpty())
                                    <p class="dash-empty">Tidak ada karyawan yang berulang tahun bulan ini.</p>
                                @else
                                    <div class="compact-list compact-list--scroll">
                                        @foreach ($ultah as $u)
                                            <div class="compact-item">
                                                <span class="avatar avatar-sm" style="background-image: url('{{ $avatarKaryawan($u->foto) }}')"></span>
                                                <div class="min-w-0 flex-fill">
                                                    <div class="people-name" title="{{ $u->nama_lengkap }}">{{ $u->nama_lengkap }}</div>
                                                    <div class="people-sub text-truncate">{{ data_get($u, 'cabang.nama_cabang', '-') }}</div>
                                                </div>
                                                <span class="people-date-chip">{{ \Carbon\Carbon::parse($u->tanggal_lahir)->locale('id')->translatedFormat('d M') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    {{-- AKTIVITAS TERBARU --}}
                    <section class="dash-card" aria-labelledby="judul-aktivitas">
                        <header class="dash-card-head">
                            <h2 class="dash-card-title" id="judul-aktivitas">Aktivitas terbaru</h2>
                            <a href="{{ route('presensi.izinsakit') }}" class="dash-link ms-auto">Lihat semua {!! $svg('panah', 14) !!}</a>
                        </header>
                        @if ($aktivitas->isEmpty())
                            <p class="dash-empty">Belum ada aktivitas terbaru.</p>
                        @else
                            <ol class="timeline">
                                @foreach ($aktivitas as $log)
                                    <li class="timeline-item timeline-item--{{ $log['tone'] }}">
                                        <p class="mb-0"><strong>{{ $log['jenis'] }}</strong> {{ $log['nama'] }} {{ $log['status'] }}</p>
                                        <time class="people-sub" datetime="{{ \Carbon\Carbon::parse($log['time'])->toIso8601String() }}">{{ \Carbon\Carbon::parse($log['time'])->locale('id')->diffForHumans() }}</time>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                </div>

                {{-- ═══ LEBAR PENUH: DEMOGRAFI ═══ --}}
                <section class="dash-card dash-demografi" aria-labelledby="judul-demografi">
                    <header class="dash-card-head">
                        <div class="min-w-0">
                            <h2 class="dash-card-title" id="judul-demografi">Demografi karyawan</h2>
                            <p class="dash-card-sub">Total {{ $totalAktif }} karyawan aktif</p>
                        </div>
                        <ul class="dash-tabs" role="tablist">
                            @foreach ($demografi as $key => $d)
                                <li role="presentation"><a href="#tab-demo-{{ $key }}" class="dash-tab {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $d['label'] }}</a></li>
                            @endforeach
                        </ul>
                        <button type="button" class="dash-link ms-auto" data-bs-toggle="modal" data-bs-target="#modal-domisili">Domisili {!! $svg('panah', 14) !!}</button>
                    </header>
                    <div class="tab-content">
                        @foreach ($demografi as $key => $d)
                            @php
                                $jumlah = array_sum($d['data']);
                                $chartId = ['umur' => 'chart-sebaran-umur-donut', 'gender' => 'chart-sebaran-gender', 'pendidikan' => 'chart-pendidikan'][$key];
                            @endphp
                            <div class="tab-pane {{ $loop->first ? 'active show' : '' }}" id="tab-demo-{{ $key }}" role="tabpanel">
                                @if ($jumlah <= 0)
                                    <p class="dash-empty">Data {{ strtolower($d['label']) }} karyawan belum tersedia.</p>
                                @else
                                    <div class="demo-layout">
                                        <div id="{{ $chartId }}" class="chart-box" style="height: 220px;" role="img"
                                            aria-label="Sebaran {{ strtolower($d['label']) }} karyawan"></div>
                                        <ul class="demo-legend">
                                            @foreach ($d['data'] as $nama => $nilai)
                                                <li>
                                                    <span class="legend-swatch" data-seri="{{ $loop->index }}"></span>
                                                    <span class="demo-legend-label">{{ $nama }}</span>
                                                    <span class="demo-legend-value">{{ $nilai }}</span>
                                                    <span class="demo-legend-pct">{{ number_format($nilai / $jumlah * 100, 1, ',', '.') }}%</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>


    {{-- ======================================================== --}}
    {{-- AREA MODAL & OFFCANVAS --}}
    {{-- ======================================================== --}}

    {{-- 1. Modal Turnover --}}
    <div class="modal modal-blur fade" id="modal-turnover" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Turnover Karyawan (3 Bulan Terakhir)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3 d-flex align-items-center"><span class="emp-status emp-status--success me-2">Masuk</span>
                                Total: {{ count($karyawanMasuk ?? []) }}</h6>
                            <div class="table-responsive">
                                <table class="table table-vcenter table-sm card-table">
                                    <thead>
                                        <tr>
                                            <th>NIK</th>
                                            <th>Nama</th>
                                            <th>Cabang</th>
                                            <th>Jabatan</th>
                                            <th>Tgl Masuk</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($karyawanMasuk ?? [] as $item)
                                            <tr>
                                                <td><span
                                                        class="cell-num">{{ optional($item)->nik }}</span>
                                                </td>
                                                <td>{{ optional($item)->nama_lengkap }}</td>
                                                <td><span
                                                        class="text-muted">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                </td>
                                                <td><span
                                                        class="">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
                                                </td>
                                                @php
                                                    $tmt = data_get($item, 'tmt');
                                                    $tgl_msuk = data_get($item, 'tanggal_awal_kontrak');
                                                    $tglMasukDisplay =
                                                        $tmt && $tgl_msuk
                                                            ? (\Carbon\Carbon::parse($tmt)->lt(
                                                                \Carbon\Carbon::parse($tgl_msuk),
                                                            )
                                                                ? $tmt
                                                                : $tgl_msuk)
                                                            : $tmt ?? $tgl_msuk;
                                                @endphp
                                                <td><span
                                                        class="cell-num">{{ $tglMasukDisplay ? \Carbon\Carbon::parse($tglMasukDisplay)->translatedFormat('d F Y') : '-' }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Belum ada karyawan
                                                    masuk dalam 3 bulan terakhir</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3 d-flex align-items-center"><span class="emp-status emp-status--danger me-2">Keluar</span>
                                Total: {{ count($karyawanKeluar ?? []) }}</h6>
                            <div class="table-responsive">
                                <table class="table table-vcenter table-sm card-table">
                                    <thead>
                                        <tr>
                                            <th>NIK</th>
                                            <th>Nama</th>
                                            <th>Cabang</th>
                                            <th>Jabatan</th>
                                            <th>Tgl Keluar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($karyawanKeluar ?? [] as $item)
                                            <tr>
                                                <td><span
                                                        class="cell-num">{{ optional($item)->nik }}</span>
                                                </td>
                                                <td>{{ optional($item)->nama_lengkap }}</td>
                                                <td><span
                                                        class="text-muted">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                </td>
                                                <td><span
                                                        class="">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
                                                </td>
                                                @php
                                                    $tglKeluarDisplay =
                                                        data_get($item, 'tanggal_keluar') ?:
                                                        data_get($item, 'updated_at');
                                                @endphp
                                                <td><span
                                                        class="cell-num">{{ $tglKeluarDisplay ? \Carbon\Carbon::parse($tglKeluarDisplay)->translatedFormat('d F Y') : '-' }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Belum ada karyawan
                                                    keluar dalam 3 bulan terakhir</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    {{-- Modal Detail Karyawan Aktif --}}
    <div class="modal modal-blur fade" id="modal-karyawan-aktif" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Karyawan Aktif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Karyawan</th>
                                    <th class="text-nowrap">NIK</th>
                                    <th>Jabatan</th>
                                    <th class="text-nowrap">Tgl Masuk</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($daftarKaryawanAktif ?? [] as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex py-1 align-items-center">
                                                <span class="avatar me-2 flex-shrink-0"
                                                    style="background-image: url('{{ data_get($item, 'foto') ? asset('storage/uploads/karyawan/' . data_get($item, 'foto')) : asset('assets/img/nophoto.png') }}')"></span>
                                                <div class="flex-fill">
                                                    <div class="font-weight-medium text-reset">
                                                        {{ optional($item)->nama_lengkap }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-nowrap"><span
                                                class="cell-num">{{ optional($item)->nik }}</span>
                                        </td>
                                        <td><span
                                                class="">{{ optional($item)->jabatan_nama ?? '-' }}</span>
                                        </td>
                                        <td class="text-nowrap"><span
                                                class="cell-num">{{ \Carbon\Carbon::parse(optional($item)->tanggal_awal_kontrak)->translatedFormat('d F Y') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Tidak ada data karyawan
                                            aktif</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="text-muted me-auto">Total: <strong>{{ count($daftarKaryawanAktif ?? []) }}</strong>
                        Karyawan</div>
                    <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Modal Domisili --}}
    <div class="modal modal-blur fade" id="modal-domisili" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Sebaran Domisili</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Alamat</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-end">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(collect($dataDomisili ?? [])->filter() as $item)
                                    @php $persentase = ($item->jumlah / ($totalKaryawanForDomisili > 0 ? $totalKaryawanForDomisili : 1)) * 100; @endphp
                                    <tr>
                                        <td class="fw-bold text-reset">{{ $item->alamat ?? 'Tidak Diketahui' }}</td>
                                        <td class="text-end cell-num">{{ $item->jumlah }}</td>
                                        <td class="text-end cell-num">{{ round($persentase, 1) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    {{-- 4. Modal Detail Kalender HR --}}
    <div class="modal modal-blur fade" id="modal-calendar-detail" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-blue-lt">
                    <h5 class="modal-title fw-bold" id="calendarDetailTitle">Detail Kalender</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="calendarDetailStats" class="d-flex flex-wrap gap-2 mb-3"></div>
                    <div class="row g-3" id="calendarDetailBody"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('myscript')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // FIX APEXCHARTS DI DALAM TABS BOOTSTRAP
            document.querySelectorAll('a[data-bs-toggle="tab"]').forEach((el) => {
                el.addEventListener('shown.bs.tab', () => {
                    window.dispatchEvent(new Event('resize'));
                });
            });

            const initChart = (elementId, options) => {
                const el = document.getElementById(elementId);
                if (!el || !window.ApexCharts) return;
                new ApexCharts(el, options).render();
            };

            // Warna grafik dari token (admin-tokens.css), jadi ikut tema terang/gelap.
            const token = (nama) => getComputedStyle(document.documentElement).getPropertyValue(nama).trim();
            const warna = {
                primary: token('--chart-primary'),
                success: token('--chart-success'),
                warning: token('--chart-warning'),
                danger: token('--chart-danger'),
                neutral: token('--chart-neutral'),
                soft: token('--chart-soft'),
                grid: token('--chart-grid'),
                text: token('--chart-text'),
            };
            const dasar = {
                chart: {
                    toolbar: { show: false },
                    background: 'transparent',
                    fontFamily: 'inherit',
                    foreColor: warna.text,
                    animations: { enabled: false }, // data dashboard dibaca, bukan ditonton
                },
                grid: { borderColor: warna.grid, strokeDashArray: 3, padding: { left: 4, right: 4 } },
                dataLabels: { enabled: false },
                legend: { show: false },
                tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light' },
            };

            // Isi warna penanda legenda buatan sendiri (demografi) dari palet yang sama.
            const paletDonut = [warna.primary, warna.success, warna.warning, warna.danger, warna.neutral];
            document.querySelectorAll('.demo-legend .legend-swatch[data-seri]').forEach((el) => {
                el.style.setProperty('--swatch', paletDonut[Number(el.dataset.seri) % paletDonut.length]);
            });

            // 1. TREN KEHADIRAN 7 HARI
            @php
                $labelTren = collect(range(6, 0))->map(fn ($i) => now()->subDays($i)->locale('id')->translatedFormat('D d'))->values();
            @endphp
            initChart('chart-tren-kehadiran', {
                ...dasar,
                chart: { ...dasar.chart, type: 'area', height: 180 },
                stroke: { width: 2, curve: 'smooth' },
                fill: { type: 'solid', opacity: [0.12, 0, 0] },
                markers: { size: 0, hover: { size: 4 } },
                series: [
                    { name: 'Hadir', data: @json($trendHadir ?? []) },
                    { name: 'Terlambat', data: @json($trendTerlambat ?? []) },
                    { name: 'Alpha', data: @json($trendAlpha ?? []) },
                ],
                colors: [warna.primary, warna.warning, warna.danger],
                xaxis: { categories: @json($labelTren), axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { min: 0, forceNiceScale: true, labels: { formatter: (v) => Math.round(v) } },
                tooltip: { ...dasar.tooltip, shared: true, intersect: false },
            });

            // 2. TURNOVER 12 BULAN
            @php
                $turnoverSeri = collect($turnoverData ?? []);
            @endphp
            initChart('chart-turnover', {
                ...dasar,
                chart: { ...dasar.chart, type: 'line', height: 200 },
                stroke: { width: [0, 0, 0, 2], curve: 'smooth' },
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
                series: [
                    { name: 'Masuk', type: 'column', data: @json($turnoverSeri->map(fn ($t) => (int) ($t['masuk'] ?? 0))->values()) },
                    { name: 'Keluar', type: 'column', data: @json($turnoverSeri->map(fn ($t) => (int) ($t['keluar'] ?? 0))->values()) },
                    { name: 'Kontrak habis', type: 'column', data: @json($turnoverSeri->map(fn ($t) => (int) ($t['kontrak_habis'] ?? 0))->values()) },
                    { name: 'Turnover rate', type: 'line', data: @json($turnoverSeri->map(fn ($t) => (float) ($t['turnover_rate'] ?? 0))->values()) },
                ],
                colors: [warna.primary, warna.danger, warna.soft, warna.warning],
                legend: { show: true, position: 'top', horizontalAlign: 'left', fontSize: '12px', markers: { size: 5 } },
                xaxis: { categories: @json($turnoverLabels ?? []), axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: [
                    // Tiga seri kolom berbagi satu skala jumlah; rate punya sumbu sendiri.
                    { seriesName: 'Masuk', min: 0, forceNiceScale: true, labels: { formatter: (v) => Math.round(v) } },
                    { seriesName: 'Masuk', show: false },
                    { seriesName: 'Masuk', show: false },
                    { seriesName: 'Turnover rate', opposite: true, min: 0, labels: { formatter: (v) => v.toFixed(1) + '%' } },
                ],
                tooltip: {
                    ...dasar.tooltip,
                    shared: true,
                    intersect: false,
                    y: { formatter: (v, { seriesIndex }) => seriesIndex === 3 ? v.toFixed(2) + '%' : Math.round(v) },
                },
            });

            // 3. KPI PER CABANG
            initChart('chart-kpi-by-cabang', {
                ...dasar,
                chart: { ...dasar.chart, type: 'bar', height: 220 },
                plotOptions: { bar: { borderRadius: 3, columnWidth: '45%' } },
                series: [{ name: 'Skor KPI', data: @json($kpiCabangSeries ?? []) }],
                xaxis: { categories: @json($kpiCabangLabels ?? []), axisBorder: { show: false }, axisTicks: { show: false } },
                colors: [warna.primary],
            });

            // 4–6. DEMOGRAFI (donut ringkas, legenda di samping dibuat di Blade)
            @php
                $umurRaw = $dataSebaranUmur ?? null;
                $genderRaw = $dataSebaranGender ?? null;
                $pendRaw = $dataPendidikan ?? null;
                $donut = [
                    'chart-sebaran-umur-donut' => [['< 20', '20–29', '30–39', '40–49', '≥ 50'], [optional($umurRaw)->umur_under_20 ?? 0, optional($umurRaw)->umur_20_29 ?? 0, optional($umurRaw)->umur_30_39 ?? 0, optional($umurRaw)->umur_40_49 ?? 0, optional($umurRaw)->umur_50_plus ?? 0]],
                    'chart-sebaran-gender' => [['Laki-laki', 'Perempuan'], [optional($genderRaw)->laki_laki ?? 0, optional($genderRaw)->perempuan ?? 0]],
                    'chart-pendidikan' => [['SMA', 'D3', 'S1', 'S2'], [optional($pendRaw)->sma ?? 0, optional($pendRaw)->d3 ?? 0, optional($pendRaw)->s1 ?? 0, optional($pendRaw)->s2 ?? 0]],
                ];
            @endphp
            Object.entries(@json($donut)).forEach(([id, [labels, data]]) => {
                initChart(id, {
                    ...dasar,
                    chart: { ...dasar.chart, type: 'donut', height: 220 },
                    labels,
                    series: data.map(Number),
                    colors: paletDonut,
                    stroke: { width: 2, colors: [token('--color-surface') || '#fff'] },
                    plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Total', color: warna.text } } } } },
                });
            });

            // Tautan "Lihat daftar" di Perlu perhatian membuka tab terkait lalu menggulir ke sana.
            document.querySelectorAll('[data-show-tab]').forEach((link) => {
                link.addEventListener('click', (e) => {
                    const tab = document.querySelector(`[data-bs-toggle="tab"][href="${link.getAttribute('href')}"]`);
                    if (!tab || typeof bootstrap === 'undefined') return;
                    e.preventDefault();
                    bootstrap.Tab.getOrCreateInstance(tab).show();
                    tab.closest('.dash-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    tab.focus({ preventScroll: true });
                });
            });

            // Tanggal kalender bisa dibuka dengan keyboard (Enter / Spasi) seperti klik.
            document.addEventListener('keydown', (e) => {
                const cell = e.target.closest?.('.calendar-day-cell');
                if (cell && (e.key === 'Enter' || e.key === ' ')) {
                    e.preventDefault();
                    cell.click();
                }
            });

            // SCRIPT KALENDER DETAIL
            const getCalendarDetailsFromRoot = (root) => {
                const payloadEl = root?.querySelector('#calendarDetailsPayload');
                if (!payloadEl) return {};
                try {
                    return JSON.parse(payloadEl.textContent || '{}') || {};
                } catch (err) {
                    return {};
                }
            };

            let calendarDetailsData = getCalendarDetailsFromRoot(document);
            const detailModalEl = document.getElementById('modal-calendar-detail');
            const detailBody = document.getElementById('calendarDetailBody');
            const detailTitle = document.getElementById('calendarDetailTitle');
            const detailStats = document.getElementById('calendarDetailStats');

            const escapeHtml = (value) => {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            };

            const renderList = (title, items, hue) => {
                const list = Array.isArray(items) ? items : [];
                const listClass = list.length > 6 ? 'calendar-person-list calendar-person-list-scroll' :
                    'calendar-person-list';
                const itemHtml = list.length ?
                    list.map((person) => `
                        <div class="d-flex align-items-start calendar-person-item mb-2 pb-2 border-bottom">
                            <span class="avatar avatar-xs flex-shrink-0" style="background-image:url('${person.foto ? '/storage/uploads/karyawan/' + person.foto : '/assets/img/nophoto.png'}')"></span>
                            <div class="flex-fill calendar-person-meta">
                                <div class="fw-semibold small">${escapeHtml(person.nama_lengkap || '-')}</div>
                                <div class="text-secondary" style="font-size:11px;">NIK: ${escapeHtml(person.nik || '-')}</div>
                                <div class="text-secondary" style="font-size:11px;">${escapeHtml(person.keterangan || '-')}</div>
                            </div>
                        </div>
                    `).join('') :
                    '<div class="small text-muted">Tidak ada data.</div>';

                return `
                    <div class="col-md-6">
                        <div class="card card-sm border-0 bg-light h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="tag hue-${hue}">${title}</span>
                                    <span class="fw-semibold cell-num">${list.length}</span>
                                </div>
                                <div class="${listClass}">${itemHtml}</div>
                            </div>
                        </div>
                    </div>
                `;
            };

            document.addEventListener('click', async (e) => {
                const navBtn = e.target.closest('.js-calendar-nav');
                if (navBtn) {
                    e.preventDefault();
                    const targetUrl = navBtn.getAttribute('href');
                    if (!targetUrl) return;

                    navBtn.classList.add('disabled');
                    navBtn.setAttribute('aria-disabled', 'true');

                    try {
                        const response = await fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!response.ok) throw new Error('Gagal memuat kalender');

                        const html = await response.text();
                        const parser = new DOMParser();
                        const nextDoc = parser.parseFromString(html, 'text/html');
                        const nextCard = nextDoc.querySelector('#hr-calendar-card');
                        const currentCard = document.querySelector('#hr-calendar-card');

                        if (!nextCard || !currentCard) {
                            window.location.href = targetUrl;
                            return;
                        }

                        currentCard.replaceWith(nextCard);
                        calendarDetailsData = getCalendarDetailsFromRoot(document);

                        if (window.history && window.history.pushState) {
                            window.history.pushState({}, '', targetUrl);
                        }
                    } catch (err) {
                        window.location.href = targetUrl;
                    } finally {
                        navBtn.classList.remove('disabled');
                        navBtn.removeAttribute('aria-disabled');
                    }

                    return;
                }

                const cell = e.target.closest('.calendar-day-cell');
                if (!cell) return;

                {
                    const dateKey = cell.dataset.date;
                    const dateLabel = cell.dataset.day;
                    const detail = calendarDetailsData[dateKey] || {
                        izin: [],
                        sakit: [],
                        cuti: [],
                        dinas: []
                    };

                    const allRows = [
                        ...(detail.izin || []),
                        ...(detail.sakit || []),
                        ...(detail.cuti || []),
                        ...(detail.dinas || []),
                    ];
                    const shouldScrollModal = allRows.length > 6;
                    const uniqueNiks = [...new Set(allRows.map((x) => x?.nik).filter(Boolean))];

                    if (detailModalEl) {
                        detailModalEl.classList.toggle('modal-force-scroll', shouldScrollModal);
                    }

                    detailTitle.textContent = `Detail Aktivitas ${dateLabel}`;
                    detailStats.innerHTML = [
                        `<span class="tag hue-blue">Izin: ${(detail.izin || []).length}</span>`,
                        `<span class="tag hue-pink">Sakit: ${(detail.sakit || []).length}</span>`,
                        `<span class="tag hue-teal">Cuti: ${(detail.cuti || []).length}</span>`,
                        `<span class="tag hue-indigo">Dinas luar: ${(detail.dinas || []).length}</span>`,
                        `<span class="tag">Total karyawan: ${uniqueNiks.length}</span>`
                    ].join('');

                    detailBody.innerHTML = [
                        renderList('Izin', detail.izin, 'blue'),
                        renderList('Sakit', detail.sakit, 'pink'),
                        renderList('Cuti', detail.cuti, 'teal'),
                        renderList('Dinas Luar', detail.dinas, 'indigo')
                    ].join('');

                    if (detailModalEl) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
                        } else {
                            // Fallback manual modal display when Bootstrap namespace is unavailable
                            detailModalEl.style.display = 'block';
                            detailModalEl.classList.add('show');
                            detailModalEl.removeAttribute('aria-hidden');
                            detailModalEl.setAttribute('aria-modal', 'true');
                            document.body.classList.add('modal-open');

                            let backdrop = document.querySelector('.modal-backdrop.fade.show');
                            if (!backdrop) {
                                backdrop = document.createElement('div');
                                backdrop.className = 'modal-backdrop fade show';
                                document.body.appendChild(backdrop);
                            }
                        }
                    }
                }
            });

            // Manual close handlers for fallback mode
            document.addEventListener('click', (e) => {
                if (!detailModalEl) return;
                const isCloseBtn = e.target.closest(
                    '#modal-calendar-detail .btn-close, #modal-calendar-detail [data-bs-dismiss="modal"]'
                    );
                const clickedBackdrop = e.target === detailModalEl;
                if (!(isCloseBtn || clickedBackdrop)) return;

                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(detailModalEl).hide();
                } else {
                    detailModalEl.classList.remove('show');
                    detailModalEl.style.display = 'none';
                    detailModalEl.setAttribute('aria-hidden', 'true');
                    detailModalEl.removeAttribute('aria-modal');
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());
                }
            });


        });
    </script>
@endpush
