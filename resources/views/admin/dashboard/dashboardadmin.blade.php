@extends('layouts.admin.tabler')

@section('content')
    <style>
        .realtime-strip {
            display: flex;
            flex-wrap: nowrap;
            align-items: stretch;
            gap: 0.5rem;
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x proximity;
            scrollbar-width: thin;
        }

        .dashboard-shell {
            width: 100%;
            max-width: none;
            padding-left: 0.9rem;
            padding-right: 0.9rem;
        }

        .realtime-card {
            background: var(--rt-soft);
            border-top: 3px solid var(--rt-accent) !important;
            min-height: 98px;
            flex: 0 0 calc((100% - 2.5rem) / 6);
            max-width: calc((100% - 2.5rem) / 6);
            min-width: 0;
            scroll-snap-align: start;
        }

        .realtime-dot {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: var(--rt-accent);
            color: #fff;
            font-size: 0.58rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .realtime-card .card-body {
            padding: 0.45rem !important;
        }

        .realtime-card .h2 {
            font-size: clamp(0.88rem, 1.2vw, 1.15rem);
            line-height: 1.05;
        }

        .realtime-card .small {
            font-size: clamp(0.54rem, 0.62vw, 0.66rem);
            line-height: 1.15;
        }

        .realtime-delta-badge {
            font-size: 0.62rem;
            line-height: 1.05;
            padding: 0.16rem 0.34rem;
        }

        .action-center-card .card-body {
            height: 290px;
            overflow-y: auto;
        }

        .dashboard-panel-card {
            border: 1px solid #e9eef5;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        }

        .dashboard-panel-card .card-header,
        .action-center-card .card-header {
            position: sticky;
            top: 0;
            z-index: 5;
            background:
                radial-gradient(circle at top right, rgba(32, 107, 196, 0.10), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .action-center-card {
            border: 1px solid #e9eef5;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        }

        .action-center-tabs,
        .people-insight-tabs,
        .culture-tabs,
        .analytics-tabs,
        .demography-tabs {
            margin: 0 !important;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            overflow: visible !important;
        }

        .action-center-tabs .nav-item,
        .people-insight-tabs .nav-item,
        .culture-tabs .nav-item,
        .analytics-tabs .nav-item,
        .demography-tabs .nav-item {
            flex: 1 1 0;
            min-width: 0;
        }

        .action-center-tabs .nav-link,
        .people-insight-tabs .nav-link,
        .culture-tabs .nav-link,
        .analytics-tabs .nav-link,
        .demography-tabs .nav-link {
            width: 100%;
            text-align: center;
            border: 0;
            border-radius: 999px;
            color: #5b667a;
            font-weight: 700;
            padding: 0.48rem 0.8rem !important;
            transition: all 0.2s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-center-tabs .nav-link:hover,
        .people-insight-tabs .nav-link:hover,
        .culture-tabs .nav-link:hover,
        .analytics-tabs .nav-link:hover,
        .demography-tabs .nav-link:hover {
            color: #206bc4;
            background: #eef5ff;
        }

        .action-center-tabs .nav-link.active,
        .people-insight-tabs .nav-link.active,
        .culture-tabs .nav-link.active,
        .analytics-tabs .nav-link.active,
        .demography-tabs .nav-link.active {
            color: #206bc4;
            background: #e9f2ff;
            box-shadow: inset 0 0 0 1px rgba(32, 107, 196, 0.08);
        }

        .action-center-card .list-group {
            padding: 0.2rem 0;
        }

        .action-center-card .list-group-item {
            padding: 0.9rem 1rem;
            margin: 0;
            border: 0;
            border-bottom: 1px solid #e9eef5;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            transition: background-color 0.2s ease;
        }

        .action-center-card .list-group-item:first-child {
            margin-top: 0;
        }

        .action-center-card .list-group-item:last-child {
            margin-bottom: 0;
            border-bottom: 0;
        }

        .action-center-card .list-group-item:hover {
            background: #f8fbff;
            border-color: #e9eef5;
        }

        .action-center-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            min-width: 0;
        }

        .action-center-info {
            min-width: 0;
            flex: 1 1 auto;
        }

        .action-center-name {
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.2;
            color: #1f2937;
        }

        .action-center-meta {
            font-size: 0.73rem;
            color: #6b7280;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-center-subline {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.22rem;
            min-width: 0;
        }

        .action-type-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.45rem;
            border-radius: 999px;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .action-center-card .avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            flex: 0 0 auto;
        }

        .people-insight-card {
            border: 1px solid #e9eef5;
            overflow: hidden;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        }

        .people-insight-card .card-header {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            background:
                radial-gradient(circle at top right, rgba(32, 107, 196, 0.10), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .people-insight-card .card-body {
            padding: 0;
            max-height: 320px;
            overflow: auto;
        }

        .people-insight-table {
            margin-bottom: 0;
            min-width: 760px;
        }

        .people-insight-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: rgba(248, 250, 252, 0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e7edf5;
            font-size: 0.72rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #6b7280;
            padding: 0.8rem 0.9rem;
            white-space: nowrap;
        }

        .people-insight-table td {
            white-space: nowrap;
        }

        .people-insight-table tbody tr {
            transition: background-color 0.18s ease;
        }

        .people-insight-table tbody tr:hover {
            background: #f8fbff;
        }

        .people-insight-table td {
            padding: 0.8rem 0.9rem;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7;
        }

        .people-insight-person {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }

        .people-insight-person .avatar {
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            border-radius: 12px;
        }

        .people-insight-name {
            font-size: 0.86rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .people-insight-subtext {
            margin-top: 0.14rem;
            font-size: 0.72rem;
            color: #6b7280;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .people-insight-date {
            display: inline-flex;
            align-items: center;
            padding: 0.22rem 0.55rem;
            border-radius: 999px;
            background: #f6f8fb;
            color: #4b5563;
            font-size: 0.74rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .people-insight-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.24rem 0.55rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1.1;
            white-space: nowrap;
        }

        .people-insight-action {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid #dbe5f1;
            background: #fff;
            color: #5b667a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
        }

        .people-insight-table th:last-child,
        .people-insight-table td[data-label="Aksi"] {
            width: 52px;
            min-width: 52px;
            max-width: 52px;
            text-align: center !important;
            padding-left: 0.3rem;
            padding-right: 0.3rem;
        }

        .people-insight-table .people-insight-action {
            margin-left: auto;
            margin-right: auto;
            flex: 0 0 auto;
        }

        .people-insight-action:hover {
            color: #206bc4;
            border-color: rgba(32, 107, 196, 0.24);
            background: #eef5ff;
        }

        .birthday-feed-wrap {
            padding: 0.1rem 0;
            overflow-x: hidden;
        }

        .birthday-feed-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
            min-width: 0;
            padding: 0.62rem 0.8rem;
            border-bottom: 1px solid #e9eef5;
            transition: background-color 0.2s ease;
        }

        .birthday-feed-item:hover {
            background: #f8fbff;
        }

        .birthday-feed-item:last-child {
            border-bottom: 0;
        }

        .birthday-feed-item .avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            flex: 0 0 auto;
        }

        .birthday-feed-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }

        .birthday-feed-meta {
            font-size: 0.66rem;
            color: #6b7280;
            line-height: 1.25;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .birthday-feed-date {
            display: inline-flex;
            align-items: center;
            flex: 0 0 auto;
            padding: 0.16rem 0.45rem;
            border-radius: 999px;
            background: #fdf1f7;
            color: #c2255c;
            font-size: 0.66rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .analytics-card .card-body,
        .demography-card .card-body {
            padding: 0.75rem;
            overflow: hidden;
        }

        .chart-tab-pane {
            height: 100%;
        }

        .chart-surface {
            height: 100%;
            border: 1px solid #edf2f7;
            border-radius: 18px;
            background:
                radial-gradient(circle at top right, rgba(32, 107, 196, 0.06), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 0.65rem 0.8rem;
            overflow: hidden;
        }

        .chart-canvas {
            width: 100%;
            height: 100%;
            min-height: 200px;
        }

        .leaderboard-table {
            margin-bottom: 0;
        }

        .leaderboard-table tbody tr {
            transition: background-color 0.18s ease;
        }

        .leaderboard-table tbody tr:hover {
            background: #f8fbff;
        }

        .leaderboard-table td {
            padding: 0.75rem 0.8rem;
            vertical-align: middle;
            border-bottom: 1px solid #eef2f7;
        }

        .leaderboard-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .leaderboard-rank {
            min-width: 2.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.28rem 0.48rem;
            border-radius: 999px;
            background: #eef5ff;
            color: #206bc4;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .leaderboard-person {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            min-width: 0;
        }

        .leaderboard-person .avatar {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            flex: 0 0 auto;
        }

        .leaderboard-name {
            font-size: 0.84rem;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.2;
        }

        .leaderboard-role {
            margin-top: 0.15rem;
            font-size: 0.7rem;
            color: #6b7280;
            line-height: 1.2;
        }

        .leaderboard-score {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            background: #eefbf2;
            color: #2b8a3e;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .demography-action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            color: #16a34a !important;
            background: #effcf2;
            box-shadow: inset 0 0 0 1px rgba(22, 163, 74, 0.12);
        }

        .demography-action-link:hover {
            color: #15803d !important;
            background: #e7f8ec;
        }


        .hr-calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .hr-calendar-wrap {
            overflow: hidden;
            width: 100%;
        }

        .hr-calendar-legend {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            flex-wrap: wrap;
            color: #6b7280;
            font-size: 10px;
            line-height: 1.35;
            text-align: center;
        }

        .hr-calendar-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .hr-calendar-legend-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            display: inline-block;
            flex: 0 0 auto;
        }

        .hr-calendar-table {
            width: 100%;
            min-width: 0;
            font-size: clamp(9px, 0.9vw, 11px);
            table-layout: fixed;
        }

        .hr-calendar-table th,
        .hr-calendar-table td {
            white-space: normal;
        }

        .hr-calendar-table .calendar-day-cell {
            height: clamp(40px, 4.2vw, 48px);
            width: 14.28%;
            cursor: pointer;
            padding: clamp(0.08rem, 0.3vw, 0.2rem) !important;
        }

        .hr-calendar-table .badge {
            padding: 0.04rem 0.18rem;
            font-size: clamp(8px, 0.7vw, 10px);
            line-height: 1;
        }

        .hr-calendar-table .calendar-day-cell .d-flex {
            flex-wrap: wrap;
            gap: 2px !important;
            margin-top: 2px !important;
        }

        #modal-calendar-detail .modal-dialog {
            width: min(1080px, 96vw);
            max-width: min(1080px, 96vw);
        }

        #modal-calendar-detail .modal-body {
            padding: 1rem;
            max-height: none;
            overflow-y: visible;
        }

        #modal-calendar-detail.modal-force-scroll .modal-body {
            max-height: min(68vh, 520px);
            overflow-y: auto;
            overscroll-behavior: contain;
        }

        #calendarDetailStats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        #calendarDetailStats .badge {
            font-size: 0.75rem;
            white-space: nowrap;
            line-height: 1.2;
        }

        #calendarDetailBody .card-body {
            padding: 0.75rem;
        }

        #calendarDetailBody .calendar-person-list.calendar-person-list-scroll {
            max-height: 260px;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding-right: 0.2rem;
        }

        #calendarDetailBody .calendar-person-item {
            gap: 0.55rem !important;
        }

        #calendarDetailBody .calendar-person-meta {
            min-width: 0;
        }

        #calendarDetailBody .calendar-person-meta .fw-semibold,
        #calendarDetailBody .calendar-person-meta .text-secondary {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        #calendarDetailBody .avatar.avatar-xs {
            width: 28px;
            height: 28px;
        }

        @media (max-width: 1200px) {
            .realtime-strip {
                flex-wrap: wrap;
                overflow-x: visible;
                scroll-snap-type: none;
                gap: 0.5rem;
            }

            .realtime-card {
                flex: 0 0 calc((100% - 1.5rem) / 4);
                max-width: calc((100% - 1.5rem) / 4);
            }
        }

        @media (max-width: 768px) {
            .dashboard-shell {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .realtime-strip {
                gap: 0.55rem;
                padding-bottom: 0.2rem;
                overflow-x: visible;
                scroll-snap-type: none;
            }

            .realtime-card {
                flex: 0 0 calc((100% - 0.55rem) / 2);
                max-width: calc((100% - 0.55rem) / 2);
                min-height: 92px;
            }

            .realtime-card .card-body {
                padding: 0.35rem !important;
            }

            .realtime-card .h2 {
                font-size: 0.84rem;
            }

            .realtime-card .small {
                font-size: 0.52rem;
            }

            .realtime-delta-badge {
                font-size: 0.56rem;
                padding: 0.14rem 0.3rem;
            }

            .action-center-card .card-body {
                height: 320px;
            }

            .action-center-tabs,
            .people-insight-tabs,
            .culture-tabs,
            .analytics-tabs,
            .demography-tabs {
                flex-wrap: nowrap;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 0.15rem;
            }

            .action-center-tabs .nav-item,
            .people-insight-tabs .nav-item,
            .culture-tabs .nav-item,
            .analytics-tabs .nav-item,
            .demography-tabs .nav-item {
                flex: 0 0 auto;
            }

            .action-center-tabs .nav-link,
            .people-insight-tabs .nav-link,
            .culture-tabs .nav-link,
            .analytics-tabs .nav-link,
            .demography-tabs .nav-link {
                width: auto;
                font-size: 0.77rem;
                padding: 0.38rem 0.68rem !important;
            }

            .action-center-row {
                align-items: flex-start;
            }

            .people-insight-card .card-body {
                max-height: none;
            }

            .people-insight-card .card-header {
                gap: 0.5rem;
            }

            .birthday-feed-item {
                padding: 0.58rem 0.72rem;
            }

            .analytics-card .card-body,
            .demography-card .card-body {
                padding: 0.6rem;
                height: auto !important;
                min-height: 248px;
            }

            .chart-surface {
                padding: 0.55rem 0.65rem;
            }

            .chart-canvas {
                min-height: 180px;
            }

            .people-insight-table {
                min-width: 640px;
            }

            .action-center-card .list-group-item {
                padding: 0.85rem 0.9rem;
            }


            .hr-calendar-table {
                font-size: 9px;
            }

            .hr-calendar-table .calendar-day-cell {
                height: 36px;
                padding: 0.08rem !important;
            }

            .hr-calendar-table .calendar-day-cell .fw-semibold {
                font-size: 0.72rem;
            }

            .hr-calendar-table .badge {
                padding: 0.03rem 0.12rem;
                font-size: 7px;
            }

            .hr-calendar-note {
                font-size: 9px !important;
            }

            .hr-calendar-nav .btn {
                padding: 0.2rem 0.5rem;
                font-size: 0.75rem;
            }

            #modal-calendar-detail .modal-dialog {
                width: calc(100vw - 0.8rem);
                max-width: calc(100vw - 0.8rem);
                margin: 0.4rem auto;
            }

            #modal-calendar-detail .modal-content {
                max-height: calc(100vh - 0.8rem);
            }

            #modal-calendar-detail .modal-header,
            #modal-calendar-detail .modal-footer {
                padding: 0.65rem 0.75rem;
            }

            #modal-calendar-detail .modal-title {
                font-size: 0.92rem;
                line-height: 1.25;
                padding-right: 0.5rem;
            }

            #modal-calendar-detail .modal-body {
                padding: 0.7rem;
            }

            #modal-calendar-detail.modal-force-scroll .modal-body {
                max-height: min(72vh, 560px);
            }

            #calendarDetailStats {
                gap: 0.35rem;
                margin-bottom: 0.7rem !important;
            }

            #calendarDetailStats .badge {
                font-size: 0.68rem;
                max-width: 100%;
                white-space: normal;
            }

            #calendarDetailBody {
                row-gap: 0.6rem !important;
            }

            #calendarDetailBody .card-body {
                padding: 0.62rem;
            }

            #calendarDetailBody .calendar-person-list.calendar-person-list-scroll {
                max-height: 220px;
            }

            #calendarDetailBody .calendar-person-item {
                margin-bottom: 0.45rem !important;
                padding-bottom: 0.45rem !important;
            }

            #calendarDetailBody .avatar.avatar-xs {
                width: 24px;
                height: 24px;
            }

            #calendarDetailBody .calendar-person-meta .fw-semibold {
                font-size: 0.76rem;
                line-height: 1.2;
            }

            #calendarDetailBody .calendar-person-meta .text-secondary {
                font-size: 10px !important;
                line-height: 1.2;
            }
        }

        .karyawan-masuk-pane,
        .karyawan-keluar-pane,
        .sisa-kontrak-pane {
            overflow-x: hidden;
        }

        .karyawan-masuk-table {
            width: 100%;
            min-width: 0 !important;
            table-layout: fixed;
        }

        .karyawan-masuk-table th:nth-child(1),
        .karyawan-masuk-table td:nth-child(1) {
            width: 36%;
        }

        .karyawan-masuk-table th:nth-child(2),
        .karyawan-masuk-table td:nth-child(2) {
            width: 20%;
        }

        .karyawan-masuk-table th:nth-child(3),
        .karyawan-masuk-table td:nth-child(3) {
            width: 18%;
        }

        .karyawan-masuk-table th:nth-child(4),
        .karyawan-masuk-table td:nth-child(4) {
            width: 20%;
        }

        .karyawan-masuk-table th:nth-child(5),
        .karyawan-masuk-table td:nth-child(5) {
            width: 52px;
        }

        .karyawan-masuk-table th,
        .karyawan-masuk-table td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .karyawan-keluar-table {
            width: 100%;
            min-width: 0 !important;
            table-layout: fixed;
        }

        .karyawan-keluar-table th:nth-child(1),
        .karyawan-keluar-table td:nth-child(1) {
            width: 36%;
        }

        .karyawan-keluar-table th:nth-child(2),
        .karyawan-keluar-table td:nth-child(2) {
            width: 20%;
        }

        .karyawan-keluar-table th:nth-child(3),
        .karyawan-keluar-table td:nth-child(3) {
            width: 18%;
        }

        .karyawan-keluar-table th:nth-child(4),
        .karyawan-keluar-table td:nth-child(4) {
            width: 20%;
        }

        .karyawan-keluar-table th:nth-child(5),
        .karyawan-keluar-table td:nth-child(5) {
            width: 52px;
        }

        .karyawan-keluar-table th,
        .karyawan-keluar-table td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .sisa-kontrak-table {
            width: 100%;
            min-width: 0 !important;
            table-layout: fixed;
            font-size: 0.7rem;
        }

        .sisa-kontrak-table th:nth-child(1),
        .sisa-kontrak-table td:nth-child(1) {
            width: 36%;
        }

        .sisa-kontrak-table th:nth-child(2),
        .sisa-kontrak-table td:nth-child(2) {
            width: 21%;
        }

        .sisa-kontrak-table th:nth-child(3),
        .sisa-kontrak-table td:nth-child(3) {
            width: 18%;
        }

        .sisa-kontrak-table th:nth-child(4),
        .sisa-kontrak-table td:nth-child(4) {
            width: 19%;
        }

        .sisa-kontrak-table th:nth-child(5),
        .sisa-kontrak-table td:nth-child(5) {
            width: 52px;
        }

        .sisa-kontrak-table thead th,
        .sisa-kontrak-table td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .sisa-kontrak-table td {
            padding: 0.46rem 0.42rem;
        }

        .sisa-kontrak-table thead th {
            font-size: 0.6rem;
            padding: 0.55rem 0.42rem;
            line-height: 1.15;
        }

        .sisa-kontrak-table .people-insight-person {
            align-items: flex-start;
            gap: 0.45rem;
        }

        .sisa-kontrak-table .people-insight-person .avatar {
            width: 30px;
            height: 30px;
            border-radius: 10px;
        }

        .sisa-kontrak-table .people-insight-name {
            font-size: 0.7rem;
            line-height: 1.15;
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
        }

        .sisa-kontrak-table .people-insight-subtext,
        .sisa-kontrak-table .people-insight-date,
        .sisa-kontrak-table .people-insight-status,
        .sisa-kontrak-table .badge,
        .sisa-kontrak-table .text-muted {
            font-size: 0.58rem !important;
            line-height: 1.15;
        }

        .sisa-kontrak-table .people-insight-subtext {
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
        }

        .sisa-kontrak-table .min-w-0 {
            min-width: 0;
            flex: 1 1 auto;
        }

        .sisa-kontrak-meta {
            display: block;
            color: #6b7280;
            line-height: 1.25;
        }

        .sisa-kontrak-cell-stack {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }

        .sisa-kontrak-table .people-insight-date,
        .sisa-kontrak-table .people-insight-status {
            padding: 0.18rem 0.38rem;
        }

        .sisa-kontrak-table .people-insight-action {
            width: 30px;
            height: 30px;
            border-radius: 9px;
        }

        @media (max-width: 768px) {

            .people-insight-table th:last-child,
            .people-insight-table td[data-label="Aksi"] {
                width: 46px;
                min-width: 46px;
                max-width: 46px;
            }

            .people-insight-table .people-insight-action {
                width: 30px;
                height: 30px;
                border-radius: 9px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-shell {
                padding-left: 0.6rem;
                padding-right: 0.6rem;
            }

            .page-title {
                font-size: 1.15rem;
            }

            .realtime-card {
                flex: 0 0 calc((100% - 0.55rem) / 2);
                max-width: calc((100% - 0.55rem) / 2);
            }

            .realtime-card .h2 {
                font-size: 0.8rem;
            }

            .realtime-card .small {
                font-size: 0.5rem;
            }

            .action-center-card .card-header {
                padding: 0.75rem !important;
            }

            .action-center-tabs .nav-link {
                padding: 0.42rem 0.7rem !important;
                font-size: 0.8rem;
            }

            .action-center-row {
                gap: 0.65rem;
            }

            .action-center-card .list-group {
                padding: 0.15rem 0;
            }

            .people-insight-card .card-header {
                padding: 0.75rem !important;
            }

            .people-insight-tabs .nav-link {
                padding: 0.42rem 0.7rem !important;
                font-size: 0.8rem;
            }

            .people-insight-action {
                width: 34px;
                justify-content: center;
                padding: 0;
                border-radius: 12px;
            }

            .birthday-feed-item {
                align-items: flex-start;
            }

            .action-center-tabs .nav-item,
            .people-insight-tabs .nav-item,
            .culture-tabs .nav-item,
            .analytics-tabs .nav-item,
            .demography-tabs .nav-item {
                flex: 0 0 auto;
            }

            .action-center-tabs .nav-link,
            .people-insight-tabs .nav-link,
            .culture-tabs .nav-link,
            .analytics-tabs .nav-link,
            .demography-tabs .nav-link {
                font-size: 0.73rem;
                padding: 0.34rem 0.58rem !important;
            }

            .leaderboard-table td {
                padding: 0.65rem 0.7rem;
            }

            .leaderboard-person .avatar {
                width: 34px;
                height: 34px;
            }

            .leaderboard-name {
                font-size: 0.8rem;
            }

            .leaderboard-role,
            .leaderboard-score,
            .leaderboard-rank {
                font-size: 0.68rem;
            }

            .birthday-feed-item .avatar {
                width: 30px;
                height: 30px;
            }

            .birthday-feed-name {
                font-size: 0.76rem;
            }

            .birthday-feed-meta,
            .birthday-feed-date {
                font-size: 0.63rem;
            }

            .hr-calendar-table {
                font-size: 8px;
            }

            .hr-calendar-table .calendar-day-cell {
                height: 32px;
            }

            .hr-calendar-table .calendar-day-cell .fw-semibold {
                font-size: 0.62rem;
                line-height: 1;
            }

            .hr-calendar-note {
                font-size: 8px !important;
            }
        }

        @media (max-width: 576px) {
            .page-header .row {
                align-items: flex-start !important;
            }

            .page-header-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
                gap: 0.35rem !important;
            }

            .dashboard-top-action {
                font-size: 0.72rem;
                padding: 0.35rem 0.55rem;
                border-radius: 10px;
            }

            .dashboard-top-action .icon {
                width: 16px;
                height: 16px;
                margin-right: 0.28rem !important;
            }

            .people-insight-card .card-body {
                padding: 0.45rem;
            }

            .people-insight-table {
                min-width: 0;
                width: 100%;
                table-layout: auto;
            }

            .people-insight-table thead {
                display: none;
            }

            .people-insight-table tbody,
            .people-insight-table tr,
            .people-insight-table td {
                display: block;
                width: 100%;
            }

            .people-insight-table tbody tr {
                border: 1px solid #e8eef8;
                border-radius: 14px;
                margin-bottom: 0.45rem;
                background: #fff;
                padding: 0.5rem 0.6rem;
            }

            .people-insight-table tbody tr:last-child {
                margin-bottom: 0;
            }

            .karyawan-masuk-table td,
            .karyawan-keluar-table td,
            .sisa-kontrak-table td {
                border: 0;
                padding: 0.2rem 0;
                white-space: normal;
            }

            .people-insight-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                font-size: 0.64rem;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                margin-bottom: 0.08rem;
                font-weight: 700;
            }

            .people-insight-table td[data-label="Karyawan"]::before {
                display: none;
            }

            .people-insight-table .people-insight-person {
                align-items: center;
                gap: 0.55rem;
            }

            .people-insight-table .people-insight-person .avatar {
                width: 34px;
                height: 34px;
                border-radius: 11px;
            }

            .people-insight-table .people-insight-name {
                font-size: 0.8rem;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }

            .people-insight-table .people-insight-subtext {
                font-size: 0.68rem;
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }

            .people-insight-table td[data-label="Aksi"] {
                text-align: right !important;
                padding-top: 0.35rem;
            }

            .people-insight-table td[data-label="Aksi"]::before {
                display: none;
            }

            .karyawan-masuk-table th:nth-child(1),
            .karyawan-masuk-table td:nth-child(1),
            .karyawan-masuk-table th:nth-child(2),
            .karyawan-masuk-table td:nth-child(2),
            .karyawan-masuk-table th:nth-child(3),
            .karyawan-masuk-table td:nth-child(3),
            .karyawan-masuk-table th:nth-child(4),
            .karyawan-masuk-table td:nth-child(4),
            .karyawan-masuk-table th:nth-child(5),
            .karyawan-masuk-table td:nth-child(5),
            .karyawan-keluar-table th:nth-child(1),
            .karyawan-keluar-table td:nth-child(1),
            .karyawan-keluar-table th:nth-child(2),
            .karyawan-keluar-table td:nth-child(2),
            .karyawan-keluar-table th:nth-child(3),
            .karyawan-keluar-table td:nth-child(3),
            .karyawan-keluar-table th:nth-child(4),
            .karyawan-keluar-table td:nth-child(4),
            .karyawan-keluar-table th:nth-child(5),
            .karyawan-keluar-table td:nth-child(5),
            .sisa-kontrak-table th:nth-child(1),
            .sisa-kontrak-table td:nth-child(1),
            .sisa-kontrak-table th:nth-child(2),
            .sisa-kontrak-table td:nth-child(2),
            .sisa-kontrak-table th:nth-child(3),
            .sisa-kontrak-table td:nth-child(3),
            .sisa-kontrak-table th:nth-child(4),
            .sisa-kontrak-table td:nth-child(4),
            .sisa-kontrak-table th:nth-child(5),
            .sisa-kontrak-table td:nth-child(5) {
                width: 100%;
            }

            .action-center-card .card-body {
                height: auto;
                max-height: 62vh;
            }

            .action-center-card .list-group {
                padding: 0.35rem;
            }

            .action-center-card .list-group-item.action-row-item {
                border: 1px solid #e8eef8;
                border-radius: 14px;
                margin-bottom: 0.45rem;
                background: #fff;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            }

            .action-center-card .list-group-item.action-row-item:last-child {
                margin-bottom: 0;
            }

            .action-center-row {
                align-items: flex-start;
                gap: 0.6rem;
            }

            .action-center-subline {
                flex-wrap: wrap;
            }

            .action-center-name,
            .action-center-meta {
                white-space: normal;
                overflow: visible;
                text-overflow: clip;
            }

            .action-center-row .people-insight-action {
                margin-left: 0 !important;
            }

            .analytics-card .card-body {
                height: auto !important;
                min-height: 0;
                padding: 0.55rem;
            }

            .analytics-card .chart-surface {
                border-radius: 14px;
                padding: 0.5rem 0.55rem;
            }

            .analytics-card .small {
                font-size: 0.7rem;
            }

            #tab-kpi-leaderboard .leaderboard-table,
            #tab-kpi-leaderboard .leaderboard-table tbody {
                display: block;
            }

            #tab-kpi-leaderboard .leaderboard-table tr {
                display: grid;
                grid-template-columns: 42px 1fr auto;
                gap: 0.45rem;
                align-items: center;
                padding: 0.45rem 0.4rem;
                border: 1px solid #e8eef8;
                border-radius: 12px;
                margin-bottom: 0.4rem;
                background: #fff;
            }

            #tab-kpi-leaderboard .leaderboard-table td {
                padding: 0;
                border: 0;
            }

            #tab-kpi-leaderboard .leaderboard-person .avatar {
                width: 30px;
                height: 30px;
                border-radius: 10px;
            }

            #tab-kpi-leaderboard .leaderboard-score {
                font-size: 0.64rem;
                padding: 0.22rem 0.45rem;
            }
        }

        [data-bs-theme="dark"] .dashboard-panel-card,
        [data-bs-theme="dark"] .action-center-card,
        [data-bs-theme="dark"] .people-insight-card {
            border-color: #2a3548;
            background: linear-gradient(180deg, #111a28 0%, #0f1724 100%);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.28);
        }

        [data-bs-theme="dark"] .dashboard-panel-card .card-header,
        [data-bs-theme="dark"] .action-center-card .card-header,
        [data-bs-theme="dark"] .people-insight-card .card-header {
            background:
                radial-gradient(circle at top right, rgba(83, 162, 255, 0.14), transparent 28%),
                linear-gradient(180deg, #172235 0%, #121c2c 100%);
            border-color: #2a3548;
        }

        [data-bs-theme="dark"] .action-center-tabs .nav-link,
        [data-bs-theme="dark"] .people-insight-tabs .nav-link,
        [data-bs-theme="dark"] .culture-tabs .nav-link,
        [data-bs-theme="dark"] .analytics-tabs .nav-link,
        [data-bs-theme="dark"] .demography-tabs .nav-link {
            color: #aab8cf;
        }

        [data-bs-theme="dark"] .action-center-tabs .nav-link:hover,
        [data-bs-theme="dark"] .people-insight-tabs .nav-link:hover,
        [data-bs-theme="dark"] .culture-tabs .nav-link:hover,
        [data-bs-theme="dark"] .analytics-tabs .nav-link:hover,
        [data-bs-theme="dark"] .demography-tabs .nav-link:hover {
            color: #dbeafe;
            background: #1b2a40;
        }

        [data-bs-theme="dark"] .action-center-tabs .nav-link.active,
        [data-bs-theme="dark"] .people-insight-tabs .nav-link.active,
        [data-bs-theme="dark"] .culture-tabs .nav-link.active,
        [data-bs-theme="dark"] .analytics-tabs .nav-link.active,
        [data-bs-theme="dark"] .demography-tabs .nav-link.active {
            color: #e2edff;
            background: #233650;
            box-shadow: inset 0 0 0 1px rgba(147, 197, 253, 0.2);
        }

        [data-bs-theme="dark"] .action-center-card .list-group-item {
            border-bottom-color: #2a3548;
        }

        [data-bs-theme="dark"] .action-center-card .list-group-item:hover {
            background: #162335;
        }

        [data-bs-theme="dark"] .action-center-name,
        [data-bs-theme="dark"] .people-insight-name,
        [data-bs-theme="dark"] .birthday-feed-name,
        [data-bs-theme="dark"] .leaderboard-name {
            color: #e5e7eb;
        }

        [data-bs-theme="dark"] .action-center-meta,
        [data-bs-theme="dark"] .people-insight-subtext,
        [data-bs-theme="dark"] .birthday-feed-meta,
        [data-bs-theme="dark"] .leaderboard-role,
        [data-bs-theme="dark"] .sisa-kontrak-meta {
            color: #9aa8bf;
        }

        [data-bs-theme="dark"] .people-insight-table thead th {
            background: rgba(18, 27, 41, 0.96);
            border-bottom-color: #2a3548;
            color: #9aa8bf;
        }

        [data-bs-theme="dark"] .people-insight-table tbody tr:hover,
        [data-bs-theme="dark"] .leaderboard-table tbody tr:hover,
        [data-bs-theme="dark"] .birthday-feed-item:hover {
            background: #162335;
        }

        [data-bs-theme="dark"] .people-insight-table td,
        [data-bs-theme="dark"] .leaderboard-table td,
        [data-bs-theme="dark"] .birthday-feed-item {
            border-color: #2a3548;
        }

        [data-bs-theme="dark"] .people-insight-date,
        [data-bs-theme="dark"] .people-insight-action {
            background: #172437;
            border-color: #32425a;
            color: #aebcd2;
        }

        [data-bs-theme="dark"] .people-insight-action:hover {
            background: #233650;
            border-color: #4d698f;
            color: #dbeafe;
        }

        [data-bs-theme="dark"] .birthday-feed-date {
            background: #3a1d2d;
            color: #f9a8d4;
        }

        [data-bs-theme="dark"] .chart-surface {
            border-color: #2a3548;
            background:
                radial-gradient(circle at top right, rgba(83, 162, 255, 0.12), transparent 28%),
                linear-gradient(180deg, #131f31 0%, #0f1827 100%);
        }

        [data-bs-theme="dark"] .leaderboard-rank {
            background: #1e3a5f;
            color: #bfdbfe;
        }

        [data-bs-theme="dark"] .leaderboard-score {
            background: #123326;
            color: #86efac;
        }

        [data-bs-theme="dark"] .demography-action-link {
            background: #123126;
            color: #86efac !important;
            box-shadow: inset 0 0 0 1px rgba(134, 239, 172, 0.2);
        }

        [data-bs-theme="dark"] .demography-action-link:hover {
            background: #174230;
            color: #bbf7d0 !important;
        }

        [data-bs-theme="dark"] .hr-calendar-table tr.bg-light th,
        [data-bs-theme="dark"] .hr-calendar-table tr.bg-light td,
        [data-bs-theme="dark"] .hr-calendar-table .bg-light {
            background: #1a273a !important;
            color: #c9d7ea;
        }

        [data-bs-theme="dark"] .hr-calendar-table,
        [data-bs-theme="dark"] .hr-calendar-table td,
        [data-bs-theme="dark"] .hr-calendar-table th {
            border-color: #2a3548 !important;
        }

        [data-bs-theme="dark"] .hr-calendar-legend {
            color: #9aa8bf;
        }

        [data-bs-theme="dark"] .action-center-card .list-group-item.action-row-item,
        [data-bs-theme="dark"] .people-insight-table tbody tr,
        [data-bs-theme="dark"] #tab-kpi-leaderboard .leaderboard-table tr {
            background: #121d2c;
            border-color: #2a3548;
        }
    </style>

    <div class="page-header d-print-none mb-2">
        <div class="container-fluid dashboard-shell">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Dashboard HRIS</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="d-flex gap-2 page-header-actions">
                        @can('pengumuman-create-admin')
                            <a href="{{ route('pengumuman.index') }}" class="btn btn-sm btn-success d-inline-flex align-items-center dashboard-top-action">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 8a3 3 0 0 1 0 6"/><path d="M10 8v8"/><path d="M12 8h-2a4 4 0 0 0 -4 4a4 4 0 0 0 4 4h2"/><path d="M16 8l4 -2v12l-4 -2"/></svg>
                                Buat Pengumuman
                            </a>
                        @endcan
                        <a href="{{ route('overview') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center dashboard-top-action">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0"/><path d="M13 18l6 -6"/><path d="M13 6l6 6"/></svg>
                            Overview
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-fluid dashboard-shell">

            {{-- MASTER GRID: 8 LEFT (OPERATIONAL) | 4 RIGHT (SIDEBAR WIDGETS) --}}
            <div class="row g-3">

                {{-- ======================================================== --}}
                {{-- LEFT COLUMN (KOLOM UTAMA - LEBAR 8)                      --}}
                {{-- ======================================================== --}}
                <div class="col-lg-8">

                    {{-- A. REALTIME STATS (8 Kartu Mini) --}}
                    @php
                        $realtimeCards = [
                            ['key' => 'hadir', 'label' => 'Hadir', 'value' => (int) ($jmlhadir ?? 0), 'accent' => '#2fb344', 'soft' => '#edf9f0', 'text' => '#1d6f34', 'initial' => 'H', 'goodWhenUp' => true],
                            ['key' => 'terlambat', 'label' => 'Telat', 'value' => (int) ($jmlterlambat ?? 0), 'accent' => '#f59f00', 'soft' => '#fff8e6', 'text' => '#8a5800', 'initial' => 'T', 'goodWhenUp' => false],
                            ['key' => 'izin', 'label' => 'Izin', 'value' => (int) ($jmlizin ?? 0), 'accent' => '#0ea5e9', 'soft' => '#e9f7fe', 'text' => '#0b5f89', 'initial' => 'I', 'goodWhenUp' => false],
                            ['key' => 'sakit', 'label' => 'Sakit', 'value' => (int) ($jmlsakit ?? 0), 'accent' => '#e83e8c', 'soft' => '#fdebf5', 'text' => '#9a2c62', 'initial' => 'S', 'goodWhenUp' => false],
                            ['key' => 'cuti', 'label' => 'Cuti', 'value' => (int) ($jmlcuti ?? 0), 'accent' => '#7c4dff', 'soft' => '#f1ecff', 'text' => '#4a2d94', 'initial' => 'C', 'goodWhenUp' => false],
                            ['key' => 'roster', 'label' => 'Roster', 'value' => (int) ($jmlroster ?? 0), 'accent' => '#0dcaf0', 'soft' => '#e7f9fd', 'text' => '#0b6e81', 'initial' => 'R', 'goodWhenUp' => false],
                            ['key' => 'dinas_luar', 'label' => 'Dinas Luar', 'value' => (int) ($jmlDinasLuar ?? 0), 'accent' => '#0d6efd', 'soft' => '#eaf1ff', 'text' => '#0b4da1', 'initial' => 'DL', 'goodWhenUp' => true],
                            ['key' => 'tanpa_keterangan', 'label' => 'Alpha', 'value' => (int) ($jmlTanpaKeterangan ?? $jmltidakabsen ?? 0), 'accent' => '#dc3545', 'soft' => '#fdecee', 'text' => '#8d1f2a', 'initial' => 'A', 'goodWhenUp' => false],
                        ];
                    @endphp

                    <div class="realtime-strip mb-3">
                        @foreach ($realtimeCards as $card)
                            @php
                                $cmp = $realtimeComparison[$card['key']] ?? ['prev_week' => 0, 'delta' => 0, 'delta_pct' => null, 'prev_week_date' => null];
                                $delta = (float) ($cmp['delta'] ?? 0);
                                $deltaPct = $cmp['delta_pct'];
                                $isUp = $delta >= 0;
                                $trendPositive = ($card['goodWhenUp'] ?? true) ? $isUp : !$isUp;
                            @endphp
                            <div class="card card-sm border-0 shadow-sm rounded-3 realtime-card" style="--rt-accent: {{ $card['accent'] }}; --rt-soft: {{ $card['soft'] }}; --rt-text: {{ $card['text'] }};">
                                <div class="card-body p-2">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="realtime-dot">{{ $card['initial'] }}</span>
                                            <div class="small fw-semibold text-truncate" style="color: var(--rt-text);">{{ $card['label'] }}</div>
                                        </div>
                                        <span class="badge realtime-delta-badge {{ $trendPositive ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' }}">{{ $isUp ? '+' : '' }}{{ number_format($delta, 0) }}</span>
                                    </div>
                                    <div class="h2 mb-1" style="color: var(--rt-text);">{{ $card['value'] }}</div>
                                    <div class="small text-secondary">
                                        vs minggu lalu: <span class="fw-semibold">{{ number_format((float) ($cmp['prev_week'] ?? 0), 0) }}</span>
                                        @if (!empty($cmp['prev_week_date']))
                                            <span
                                                class="text-muted">({{ \Carbon\Carbon::parse($cmp['prev_week_date'])->translatedFormat('d F') }})</span>
                                        @endif
                                        @if (!is_null($deltaPct))
                                            <span
                                                class="ms-1 {{ $trendPositive ? 'text-success' : 'text-danger' }}">({{ $isUp ? '+' : '' }}{{ number_format((float) $deltaPct, 0) }}%)</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- A2. STATISTIK KARYAWAN (Masuk / Keluar / Sisa Kontrak) --}}
                    <div class="card border-0 shadow-sm mb-3 people-insight-card">
                        <div class="card-header p-2 border-bottom-0">
                            <ul class="nav nav-tabs card-header-tabs w-100 people-insight-tabs" data-bs-toggle="tabs"
                                role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-kary-masuk" class="nav-link active px-2 py-1" data-bs-toggle="tab"
                                        role="tab">
                                        Karyawan Masuk <span
                                            class="badge bg-success-lt text-success ms-1">{{ count($karyawanMasuk ?? []) }}</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-kary-keluar" class="nav-link px-2 py-1" data-bs-toggle="tab"
                                        role="tab">
                                        Karyawan Keluar <span
                                            class="badge bg-danger-lt text-danger ms-1">{{ count($karyawanKeluar ?? []) }}</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-kary-kontrak" class="nav-link px-2 py-1" data-bs-toggle="tab"
                                        role="tab">
                                        Sisa Kontrak <span
                                            class="badge bg-warning-lt text-warning ms-1">{{ count($karyawanSisaKontrak ?? []) }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                {{-- TAB MASUK --}}
                                <div class="tab-pane active karyawan-masuk-pane" id="tab-kary-masuk" role="tabpanel">
                                    <table
                                        class="table table-vcenter table-sm card-table people-insight-table karyawan-masuk-table">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Cabang</th>
                                                <th>Jabatan</th>
                                                <th>Tgl Masuk</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(collect($karyawanMasuk ?? [])->filter() as $item)
                                                <tr>
                                                    <td data-label="Karyawan">
                                                        <div class="people-insight-person">
                                                            <span class="avatar"
                                                                style="background-image:url('{{ data_get($item, 'foto') ? asset('storage/uploads/karyawan/' . data_get($item, 'foto')) : asset('assets/img/nophoto.png') }}')"></span>
                                                            <div class="min-w-0">
                                                                <div class="people-insight-name text-truncate">
                                                                    {{ data_get($item, 'nama_lengkap', '-') }}</div>
                                                                <div class="people-insight-subtext text-truncate">NIK
                                                                    {{ data_get($item, 'nik', '-') }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td data-label="Cabang"><span class="text-muted"
                                                            style="font-size:0.8rem;">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                    </td>
                                                    <td data-label="Jabatan"><span
                                                            class="badge bg-success-lt text-success"
                                                            style="font-size:0.7rem;">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
                                                    </td>
                                                    @php
                                                        $tglMasukDisplay =
                                                            $item->tmt && $item->tanggal_awal_kontrak
                                                                ? (\Carbon\Carbon::parse($item->tmt)->lt(
                                                                    \Carbon\Carbon::parse($item->tanggal_awal_kontrak),
                                                                )
                                                                    ? $item->tmt
                                                                    : $item->tanggal_awal_kontrak)
                                                                : $item->tmt ?? $item->tanggal_awal_kontrak;
                                                    @endphp
                                                    <td data-label="Tanggal Masuk"><span
                                                            class="people-insight-date">{{ $tglMasukDisplay ? \Carbon\Carbon::parse($tglMasukDisplay)->translatedFormat('d F Y') : '-' }}</span>
                                                    </td>
                                                    <td class="text-end" data-label="Aksi">
                                                        <a href="{{ route('karyawan.show', data_get($item, 'nik')) }}"
                                                            class="people-insight-action" title="Detail karyawan">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="icon icon-sm m-0" width="24" height="24"
                                                                viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M9 6l6 6l-6 6" />
                                                            </svg>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Belum ada
                                                        karyawan masuk dalam 3 bulan terakhir.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                {{-- TAB KELUAR --}}
                                <div class="tab-pane karyawan-keluar-pane" id="tab-kary-keluar" role="tabpanel">
                                    <table
                                        class="table table-vcenter table-sm card-table people-insight-table karyawan-keluar-table">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Cabang</th>
                                                <th>Jabatan</th>
                                                <th>Tgl Keluar</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(collect($karyawanKeluar ?? [])->filter() as $item)
                                                <tr>
                                                    <td data-label="Karyawan">
                                                        <div class="people-insight-person">
                                                            <span class="avatar"
                                                                style="background-image:url('{{ data_get($item, 'foto') ? asset('storage/uploads/karyawan/' . data_get($item, 'foto')) : asset('assets/img/nophoto.png') }}')"></span>
                                                            <div class="min-w-0">
                                                                <div class="people-insight-name text-truncate">
                                                                    {{ data_get($item, 'nama_lengkap', '-') }}</div>
                                                                <div class="people-insight-subtext text-truncate">NIK
                                                                    {{ data_get($item, 'nik', '-') }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td data-label="Cabang"><span class="text-muted"
                                                            style="font-size:0.8rem;">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                    </td>
                                                    <td data-label="Jabatan"><span class="badge bg-danger-lt text-danger"
                                                            style="font-size:0.7rem;">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
                                                    </td>
                                                    @php
                                                        $tglKeluarDisplay =
                                                            data_get($item, 'tanggal_keluar') ?:
                                                            data_get($item, 'updated_at');
                                                    @endphp
                                                    <td data-label="Tanggal Keluar"><span
                                                            class="people-insight-date">{{ $tglKeluarDisplay ? \Carbon\Carbon::parse($tglKeluarDisplay)->translatedFormat('d F Y') : '-' }}</span>
                                                    </td>
                                                    <td class="text-end" data-label="Aksi">
                                                        @php
                                                            $keluarHabisKontrak =
                                                                data_get($item, 'tanggal_habis_kontrak') &&
                                                                \Carbon\Carbon::parse(
                                                                    data_get($item, 'tanggal_habis_kontrak'),
                                                                )->isPast();
                                                            $keluarRoute = $keluarHabisKontrak
                                                                ? route('karyawan.monitoring.turnover')
                                                                : route('karyawan.show', data_get($item, 'nik'));
                                                            $keluarTitle = $keluarHabisKontrak
                                                                ? 'Lihat data turnover'
                                                                : 'Detail karyawan';
                                                        @endphp
                                                        <a href="{{ $keluarRoute }}" class="people-insight-action"
                                                            title="{{ $keluarTitle }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="icon icon-sm m-0" width="24" height="24"
                                                                viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M9 6l6 6l-6 6" />
                                                            </svg>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Belum ada
                                                        karyawan keluar dalam 3 bulan terakhir.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                {{-- TAB SISA KONTRAK --}}
                                <div class="tab-pane sisa-kontrak-pane" id="tab-kary-kontrak" role="tabpanel">
                                    <table
                                        class="table table-vcenter table-sm card-table people-insight-table sisa-kontrak-table">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Cabang</th>
                                                <th>Jabatan</th>
                                                <th>Sisa Waktu</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(collect($karyawanSisaKontrak ?? [])->filter() as $item)
                                                @php
                                                    $tanggalHabis = \Carbon\Carbon::parse(
                                                        data_get($item, 'tanggal_habis_kontrak'),
                                                    )->startOfDay();
                                                    $hariSelisih = \Carbon\Carbon::today()->diffInDays(
                                                        $tanggalHabis,
                                                        false,
                                                    );
                                                    $sisaBadge =
                                                        $hariSelisih < 0
                                                            ? 'bg-red-lt text-danger'
                                                            : ($hariSelisih <= 7
                                                                ? 'bg-orange-lt text-orange'
                                                                : 'bg-yellow-lt text-yellow');
                                                    $statusKontrak =
                                                        $hariSelisih < 0
                                                            ? 'Sudah habis'
                                                            : ($hariSelisih <= 7
                                                                ? 'Sangat dekat'
                                                                : 'Perlu review');
                                                @endphp
                                                <tr>
                                                    <td data-label="Karyawan">
                                                        <div class="people-insight-person">
                                                            <span class="avatar"
                                                                style="background-image:url('{{ data_get($item, 'foto') ? asset('storage/uploads/karyawan/' . data_get($item, 'foto')) : asset('assets/img/nophoto.png') }}')"></span>
                                                            <div class="min-w-0">
                                                                <div class="people-insight-name">
                                                                    {{ data_get($item, 'nama_lengkap', '-') }}</div>
                                                                <div class="people-insight-subtext">NIK
                                                                    {{ data_get($item, 'nik', '-') }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td data-label="Cabang">
                                                        <div class="sisa-kontrak-cell-stack">
                                                            <span
                                                                class="sisa-kontrak-meta">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                        </div>
                                                    </td>
                                                    <td data-label="Jabatan">
                                                        <span
                                                            class="badge bg-orange-lt">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
                                                    </td>

                                                    <td data-label="Sisa Waktu">
                                                        <div class="sisa-kontrak-cell-stack align-items-start">
                                                            <span
                                                                class="people-insight-date">{{ data_get($item, 'tanggal_habis_kontrak') ? \Carbon\Carbon::parse(data_get($item, 'tanggal_habis_kontrak'))->translatedFormat('d F Y') : '-' }}</span>
                                                            <span
                                                                class="people-insight-status {{ $sisaBadge }}">{{ data_get($item, 'sisa_kontrak', '-') }}</span>
                                                            <span
                                                                class="people-insight-subtext">{{ $statusKontrak }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center" data-label="Aksi">
                                                        @php
                                                            $kontrakRoute =
                                                                $hariSelisih < 0
                                                                    ? route('karyawan.monitoring.turnover')
                                                                    : route('karyawan.show', data_get($item, 'nik'));
                                                            $kontrakTitle =
                                                                $hariSelisih < 0
                                                                    ? 'Lihat data turnover'
                                                                    : 'Detail karyawan';
                                                        @endphp
                                                        <a href="{{ $kontrakRoute }}" class="people-insight-action"
                                                            title="{{ $kontrakTitle }}">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="icon icon-sm m-0" width="24" height="24"
                                                                viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none"
                                                                stroke-linecap="round" stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                                <path d="M9 6l6 6l-6 6" />
                                                            </svg>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">Tidak ada
                                                        kontrak habis dalam 3 bulan ke depan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- B. PUSAT TINDAKAN (TABBED ACTION CENTER) --}}
                    <div class="card border-0 shadow-sm mb-3 action-center-card">
                        <div class="card-header p-2 border-bottom-0">
                            {{-- flex-nowrap dan overflow-auto mencegah tab menumpuk di layar kecil --}}
                            <ul class="nav nav-tabs card-header-tabs w-100 action-center-tabs" data-bs-toggle="tabs"
                                role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-izin" class="nav-link active px-2 py-1" data-bs-toggle="tab"
                                        aria-selected="true" role="tab">
                                        <span class="d-none d-sm-inline me-1">Approve</span> Izin <span
                                            class="badge bg-yellow-lt ms-1">{{ isset($izinPending) ? $izinPending->count() : 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-lembur" class="nav-link px-2 py-1" data-bs-toggle="tab"
                                        aria-selected="false" role="tab">
                                        <span class="d-none d-sm-inline me-1">Approve</span> Lembur <span
                                            class="badge bg-blue-lt ms-1">{{ isset($lemburPending) ? $lemburPending->count() : 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-dinas" class="nav-link px-2 py-1" data-bs-toggle="tab"
                                        aria-selected="false" role="tab">
                                        <span class="d-none d-sm-inline me-1">Approve</span> Dinas <span
                                            class="badge bg-green-lt ms-1">{{ isset($dinasLuarPending) ? $dinasLuarPending->count() : 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-kpi" class="nav-link px-2 py-1" data-bs-toggle="tab"
                                        aria-selected="false" role="tab">
                                        KPI <span
                                            class="badge bg-purple ms-1 text-white">{{ isset($kpiPending) ? $kpiPending->count() : 0 }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-0 card-body-scrollable">
                            <div class="tab-content">
                                {{-- TAB IZIN --}}
                                <div class="tab-pane active show" id="tab-izin">
                                    <div class="list-group list-group-flush">
                                        @forelse($izinPending ?? [] as $item)
                                            @php
                                                $statusCode = strtolower((string) ($item->status ?? ''));
                                                $jenisLabel = 'Izin Absen';
                                                $jenisBadgeClass = 'bg-blue-lt text-blue';

                                                if ($statusCode === 's') {
                                                    $jenisLabel = 'Sakit';
                                                    $jenisBadgeClass = 'bg-pink-lt text-pink';
                                                } elseif ($statusCode === 't') {
                                                    $jenisLabel = 'Izin Terlambat';
                                                    $jenisBadgeClass = 'bg-orange-lt text-orange';
                                                } elseif ($statusCode === 'p') {
                                                    $jenisLabel = 'Izin Pulang Cepat';
                                                    $jenisBadgeClass = 'bg-indigo-lt text-indigo';
                                                } elseif ($statusCode === 'c' || !empty($item->kode_cuti)) {
                                                    $jenisLabel = 'Cuti';
                                                    $jenisBadgeClass = 'bg-teal-lt text-teal';
                                                }

                                                $detailJenis = $jenisLabel;
                                                if ($jenisLabel === 'Cuti') {
                                                    $detailJenis =
                                                        $item->jenis_cuti_formal ?? ($item->nama_cuti ?? 'Cuti');
                                                } elseif (
                                                    in_array($statusCode, ['i', 's'], true) &&
                                                    !empty($item->nama_cuti)
                                                ) {
                                                    $detailJenis = $item->nama_cuti;
                                                }

                                                $tanggalMulai = !empty($item->tgl_izin_dari)
                                                    ? \Carbon\Carbon::parse($item->tgl_izin_dari)->translatedFormat(
                                                        'd F',
                                                    )
                                                    : '-';
                                            @endphp
                                            <div class="list-group-item action-row-item">
                                                <div class="action-center-row">
                                                    <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                                        style="background-image: url('{{ $item->foto ? asset('storage/uploads/karyawan/' . $item->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                    <div class="action-center-info text-truncate">
                                                        <div class="action-center-name text-truncate">
                                                            {{ $item->nama_lengkap }}</div>
                                                        <div class="action-center-subline text-truncate">
                                                            <span
                                                                class="badge {{ $jenisBadgeClass }} action-type-badge">{{ $jenisLabel }}</span>
                                                            <span
                                                                class="action-center-meta text-truncate">{{ $detailJenis }}
                                                                ({{ $tanggalMulai }})</span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('presensi.izinsakit') }}"
                                                        class="people-insight-action ms-auto flex-shrink-0"
                                                        title="Ke halaman approval izin">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm m-0"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 6l6 6l-6 6" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada antrean izin.</div>
                                        @endforelse
                                    </div>
                                </div>
                                {{-- TAB LEMBUR --}}
                                <div class="tab-pane" id="tab-lembur">
                                    <div class="list-group list-group-flush">
                                        @forelse($lemburPending ?? [] as $item)
                                            <div class="list-group-item action-row-item">
                                                <div class="action-center-row">
                                                    <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                                        style="background-image: url('{{ $item->foto ? asset('storage/uploads/karyawan/' . $item->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                    <div class="action-center-info text-truncate">
                                                        <div class="action-center-name text-truncate">
                                                            {{ $item->nama_lengkap }}</div>
                                                        <div class="action-center-subline text-truncate">
                                                            <span
                                                                class="badge bg-blue-lt text-blue action-type-badge">Lembur</span>
                                                            <span class="action-center-meta text-truncate">Total:
                                                                {{ $item->total_jam ?? '-' }} Jam</span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('admin.lembur.approval') }}"
                                                        class="people-insight-action ms-auto flex-shrink-0"
                                                        title="Ke halaman approval lembur">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm m-0"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 6l6 6l-6 6" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada antrean lembur.</div>
                                        @endforelse
                                    </div>
                                </div>
                                {{-- TAB DINAS --}}
                                <div class="tab-pane" id="tab-dinas">
                                    <div class="list-group list-group-flush">
                                        @forelse($dinasLuarPending ?? [] as $item)
                                            <div class="list-group-item action-row-item">
                                                <div class="action-center-row">
                                                    <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                                        style="background-image: url('{{ $item->foto ? asset('storage/uploads/karyawan/' . $item->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                    <div class="action-center-info text-truncate">
                                                        <div class="action-center-name text-truncate">
                                                            {{ $item->nama_lengkap }}</div>
                                                        <div class="action-center-subline text-truncate">
                                                            <span
                                                                class="badge bg-azure-lt text-azure action-type-badge">Dinas</span>
                                                            <span
                                                                class="action-center-meta text-truncate">{{ Str::limit($item->lokasi_tujuan ?? '-', 30) }}</span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('dinasluars.approval') }}"
                                                        class="people-insight-action ms-auto flex-shrink-0"
                                                        title="Ke halaman approval dinas luar">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm m-0"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 6l6 6l-6 6" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada antrean dinas luar.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                                {{-- TAB KPI --}}
                                <div class="tab-pane" id="tab-kpi">
                                    <div class="list-group list-group-flush">
                                        @forelse($kpiPending ?? [] as $item)
                                            <div class="list-group-item action-row-item">
                                                <div class="action-center-row">
                                                    <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                                        style="background-image: url('{{ $item->foto ? asset('storage/uploads/karyawan/' . $item->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                    <div class="action-center-info text-truncate">
                                                        <div class="action-center-name text-truncate">
                                                            {{ $item->nama_lengkap }}</div>
                                                        <div class="action-center-subline text-truncate">
                                                            <span
                                                                class="badge bg-purple-lt text-purple action-type-badge">KPI</span>
                                                            <span class="action-center-meta text-truncate">KPI
                                                                {{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('kpi.indikator.index') }}"
                                                        class="people-insight-action ms-auto flex-shrink-0"
                                                        title="Ke halaman approval KPI">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm m-0"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 6l6 6l-6 6" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada evaluasi KPI pending.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- C. ANALITIK KINERJA (TABBED CHARTS) --}}
                    <div class="card border-0 shadow-sm mb-3 dashboard-panel-card analytics-card">
                        <div class="card-header p-2 border-bottom-0">
                            <ul class="nav nav-tabs card-header-tabs w-100 analytics-tabs" data-bs-toggle="tabs">
                                <li class="nav-item"><a href="#tab-chart-turnover" class="nav-link active px-3 py-1"
                                        data-bs-toggle="tab">📈 Turnover Aktual (12 Bulan)</a></li>
                                <li class="nav-item"><a href="#tab-chart-kpi" class="nav-link px-3 py-1"
                                        data-bs-toggle="tab">📊 KPI Cabang</a></li>
                                <li class="nav-item"><a href="#tab-kpi-leaderboard" class="nav-link px-3 py-1"
                                        data-bs-toggle="tab">🏆 Top KPI</a></li>
                            </ul>
                        </div>
                        <div class="card-body" style="height: 260px;">
                            <div class="tab-content">
                                <div class="tab-pane active show chart-tab-pane" id="tab-chart-turnover">
                                    <div class="chart-surface">
                                        <div class="small text-secondary mb-1">
                                            Masuk Aktual, Keluar Aktual, Kontrak Habis, dan Turnover Rate (%) untuk 12 bulan
                                            terakhir.
                                        </div>
                                        <div id="chart-turnover" class="chart-canvas" style="height: 220px;"></div>
                                        @if (($turnoverAnomalyCount ?? 0) > 0)
                                            <div class="text-warning small mt-1">
                                                Perhatian: {{ $turnoverAnomalyCount }} karyawan berstatus keluar tetapi
                                                belum memiliki tanggal keluar.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="tab-pane chart-tab-pane" id="tab-chart-kpi">
                                    <div class="chart-surface">
                                        <div id="chart-kpi-by-cabang" class="chart-canvas" style="height: 220px;"></div>
                                    </div>
                                </div>
                                <div class="tab-pane chart-tab-pane" id="tab-kpi-leaderboard">
                                    <div class="chart-surface">
                                        <table class="table table-sm table-vcenter table-hover leaderboard-table">
                                            <tbody>
                                                @forelse(collect($kpiLeaderboardQuery ?? [])->filter() as $rank => $kp)
                                                    <tr>
                                                        <td style="width:56px;"><span
                                                                class="leaderboard-rank">#{{ $rank + 1 }}</span></td>
                                                        <td>
                                                            <div class="leaderboard-person">
                                                                <span class="avatar flex-shrink-0"
                                                                    style="background-image: url('{{ $kp->foto ? asset('storage/uploads/karyawan/' . $kp->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                                <div class="min-w-0">
                                                                    <div class="leaderboard-name text-truncate">
                                                                        {{ $kp->nama_lengkap }}</div>
                                                                    <div class="leaderboard-role text-truncate">
                                                                        {{ $kp->jabatan_nama ?? '-' }}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-end"><span
                                                                class="leaderboard-score">{{ number_format($kp->total_points ?? 0) }}
                                                                Poin</span></td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-3">Belum ada
                                                            data KPI</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- D. DEMOGRAFI (TABBED CHARTS) --}}
                    <div class="card border-0 shadow-sm mb-3 dashboard-panel-card demography-card">
                        <div class="card-header p-2 border-bottom-0">
                            <ul class="nav nav-tabs card-header-tabs w-100 demography-tabs" data-bs-toggle="tabs">
                                <li class="nav-item"><a href="#tab-demo-umur" class="nav-link active px-3 py-1"
                                        data-bs-toggle="tab">Umur</a></li>
                                <li class="nav-item"><a href="#tab-demo-gender" class="nav-link px-3 py-1"
                                        data-bs-toggle="tab">Gender</a></li>
                                <li class="nav-item"><a href="#tab-demo-pendidikan" class="nav-link px-3 py-1"
                                        data-bs-toggle="tab">Pendidikan</a></li>
                                <li class="nav-item"><a href="#" class="nav-link px-3 py-1 demography-action-link"
                                        data-bs-toggle="modal" data-bs-target="#modal-domisili">Lihat Domisili</a></li>
                            </ul>
                        </div>
                        <div class="card-body" style="height: 240px;">
                            <div class="tab-content">
                                <div class="tab-pane active show chart-tab-pane" id="tab-demo-umur">
                                    <div class="chart-surface">
                                        <div id="chart-sebaran-umur-donut" class="chart-canvas" style="height: 200px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane chart-tab-pane" id="tab-demo-gender">
                                    <div class="chart-surface">
                                        <div id="chart-sebaran-gender" class="chart-canvas" style="height: 200px;"></div>
                                    </div>
                                </div>
                                <div class="tab-pane chart-tab-pane" id="tab-demo-pendidikan">
                                    <div class="chart-surface">
                                        <div id="chart-pendidikan" class="chart-canvas" style="height: 200px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- ======================================================== --}}
                {{-- RIGHT COLUMN (SIDEBAR - LEBAR 4)                         --}}
                {{-- ======================================================== --}}
                <div class="col-lg-4">

                    {{-- 1. CRITICAL ALERTS (Jika Ada) --}}
                    @if (($spWarningCount ?? 0) > 0 || ($isLateMassive ?? false))
                        <div class="card border-danger shadow-sm rounded-3 mb-3 bg-danger-lt">
                            <div class="card-body p-3">
                                <h4 class="text-danger mb-2">⚠️ Peringatan Sistem</h4>
                                <ul class="mb-0 ps-3 small text-danger">
                                    @if (($spWarningCount ?? 0) > 0)
                                        <li>Ada <strong>{{ $spWarningCount }} Karyawan</strong> dengan SP akan habis (≤ 7
                                            hari).</li>
                                    @endif
                                    @if ($isLateMassive ?? false)
                                        <li>Keterlambatan Masif! <strong>{{ $lateMassiveCount }} orang</strong> terlambat
                                            hari ini.</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    @endif

                    {{-- 2. TAUTAN CEPAT (GRID) --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body p-2">
                            <div class="row g-1 text-center">
                                <div class="col-4"><a href="{{ route('karyawan.index') }}"
                                        class="btn btn-sm btn-outline-primary w-100 py-2 d-flex flex-column"><svg
                                            xmlns="http://www.w3.org/2000/svg" class="icon mb-1 mx-0" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                                            <path d="M16 19h6" />
                                            <path d="M19 16v6" />
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                                        </svg>Karyawan</a></div>
                                <div class="col-4"><a href="{{ route('konfigurasi.jamkerja') }}"
                                        class="btn btn-sm btn-outline-azure w-100 py-2 d-flex flex-column"><svg
                                            xmlns="http://www.w3.org/2000/svg" class="icon mb-1 mx-0" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" />
                                            <path d="M12 7v5l3 3" />
                                        </svg>Jam Kerja</a></div>
                                <div class="col-4"><a href="{{ route('admin.lembur.rekap') }}"
                                        class="btn btn-sm btn-outline-orange w-100 py-2 d-flex flex-column"><svg
                                            xmlns="http://www.w3.org/2000/svg" class="icon mb-1 mx-0" width="24"
                                            height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                            <path
                                                d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" />
                                            <path d="M12 11v6" />
                                            <path d="M9.5 13.5l2.5 -2.5l2.5 2.5" />
                                        </svg>Rekap Lmb</a></div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. REQUEST BPJS & KENAIKAN GAJI --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3 action-center-card">
                        <div class="card-header p-2 border-bottom-0">
                            <ul class="nav nav-tabs card-header-tabs w-100 action-center-tabs" data-bs-toggle="tabs"
                                role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-req-bpjs" class="nav-link active px-2 py-1" data-bs-toggle="tab"
                                        aria-selected="true" role="tab">
                                        Request BPJS <span
                                            class="badge bg-azure-lt ms-1">{{ isset($bpjsPendingCount) ? $bpjsPendingCount : 0 }}</span>
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tab-req-salary" class="nav-link px-2 py-1" data-bs-toggle="tab"
                                        aria-selected="false" role="tab">
                                        Kenaikan Gaji <span
                                            class="badge bg-success-lt ms-1">{{ isset($salaryIncreasePendingCount) ? $salaryIncreasePendingCount : 0 }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-0 card-body-scrollable">
                            @php
                                $bpjsPendingRows = collect($bpjsPendingList ?? []);
                                $salaryPendingRows = collect($salaryIncreasePendingList ?? []);
                            @endphp
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tab-req-bpjs">
                                    <div class="list-group list-group-flush">
                                        @forelse($bpjsPendingRows as $item)
                                            <div class="list-group-item action-row-item">
                                                <div class="action-center-row">
                                                    <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                                        style="background-image: url('{{ $item->foto ? asset('storage/uploads/karyawan/' . $item->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                    <div class="action-center-info text-truncate">
                                                        <div class="action-center-name text-truncate">
                                                            {{ $item->nama_lengkap }}</div>
                                                        <div class="action-center-subline text-truncate">
                                                            <span class="badge bg-azure-lt text-azure action-type-badge">BPJS</span>
                                                            <span class="action-center-meta text-truncate">{{ optional($item->requested_at ?? $item->created_at)->format('d M Y H:i') }}</span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('admin.bpjs.show', $item->id) }}"
                                                        class="people-insight-action ms-auto flex-shrink-0"
                                                        title="Detail request BPJS">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm m-0"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 6l6 6l-6 6" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada antrean request BPJS.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="tab-pane" id="tab-req-salary">
                                    <div class="list-group list-group-flush">
                                        @forelse($salaryPendingRows as $item)
                                            <div class="list-group-item action-row-item">
                                                <div class="action-center-row">
                                                    <span class="avatar avatar-sm rounded-circle flex-shrink-0"
                                                        style="background-image: url('{{ $item->foto ? asset('storage/uploads/karyawan/' . $item->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                    <div class="action-center-info text-truncate">
                                                        <div class="action-center-name text-truncate">
                                                            {{ $item->nama_lengkap }}</div>
                                                        <div class="action-center-subline text-truncate">
                                                            <span class="badge bg-success-lt text-success action-type-badge">Gaji</span>
                                                            <span class="action-center-meta text-truncate">{{ optional($item->tanggal_pengajuan ?? $item->created_at)->format('d M Y') }} • {{ (int) ($item->persentase ?? 0) }}%</span>
                                                        </div>
                                                    </div>
                                                    <a href="{{ route('admin.kenaikan_gaji.index', ['status' => 'pending', 'nama_karyawan' => $item->nik]) }}"
                                                        class="people-insight-action ms-auto flex-shrink-0"
                                                        title="Detail request kenaikan gaji">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-sm m-0"
                                                            width="24" height="24" viewBox="0 0 24 24"
                                                            stroke-width="2" stroke="currentColor" fill="none"
                                                            stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                            <path d="M9 6l6 6l-6 6" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada antrean kenaikan gaji.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. KALENDER HR --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3" id="hr-calendar-card">
                        <div class="card-header p-2 border-bottom-0">
                            <div class="hr-calendar-header w-100">
                                <h3 class="card-title fs-5 mb-0" id="calendarCardTitle">📅 Kalender HR
                                    ({{ $calendarLabel ?? now()->translatedFormat('F Y') }})</h3>
                                <div class="hr-calendar-nav ms-auto d-flex gap-1">
                                    <a href="{{ route('dashboard.admin', ['bulan' => $prevMonth ?? now()->copy()->subMonth()->format('Y-m')]) }}"
                                        class="btn btn-sm btn-outline-secondary px-2 js-calendar-nav">Prev</a>
                                    <a href="{{ route('dashboard.admin', ['bulan' => $nextMonth ?? now()->copy()->addMonth()->format('Y-m')]) }}"
                                        class="btn btn-sm btn-outline-secondary px-2 js-calendar-nav">Next</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            @php
                                $calendarNow = isset($calendarMonth)
                                    ? \Carbon\Carbon::createFromFormat('Y-m', $calendarMonth)
                                    : now();
                                $startCal = $calendarNow->copy()->startOfMonth()->startOfWeek();
                                $endCal = $calendarNow->copy()->endOfMonth()->endOfWeek();
                                $daysCal = [];
                                for ($d = $startCal->copy(); $d->lte($endCal); $d->addDay()) {
                                    $daysCal[] = $d->copy();
                                }
                            @endphp
                            <div class="hr-calendar-wrap">
                                <table class="table table-vcenter table-bordered text-center mb-1 hr-calendar-table">
                                    <thead>
                                        <tr class="bg-light">
                                            <th class="p-1">Sn</th>
                                            <th class="p-1">Sl</th>
                                            <th class="p-1">Rb</th>
                                            <th class="p-1">Km</th>
                                            <th class="p-1">Jm</th>
                                            <th class="p-1">Sb</th>
                                            <th class="p-1">Mn</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (array_chunk($daysCal, 7) as $week)
                                            <tr>
                                                @foreach ($week as $day)
                                                    @php
                                                        $dayKey = $day->format('Y-m-d');
                                                        $ev = $calendarEvents[$dayKey] ?? [
                                                            'holiday' => false,
                                                            'leave' => 0,
                                                            'izin' => 0,
                                                            'sakit' => 0,
                                                            'cuti' => 0,
                                                            'dinas' => 0,
                                                            'kpi_cutoff' => false,
                                                        ];
                                                        $bgClass = $day->isToday()
                                                            ? 'bg-primary-lt fw-bold'
                                                            : ($day->month !== $calendarNow->month
                                                                ? 'text-muted bg-light'
                                                                : '');
                                                    @endphp
                                                    <td class="{{ $bgClass }} calendar-day-cell"
                                                        data-date="{{ $dayKey }}"
                                                        data-day="{{ $day->format('d M Y') }}">
                                                        <div class="fw-semibold">{{ $day->format('d') }}</div>
                                                        <div class="d-flex justify-content-center gap-1 mt-1">
                                                            @if (($ev['izin'] ?? 0) > 0)
                                                                <span class="badge bg-blue-lt" title="Izin">I</span>
                                                            @endif
                                                            @if (($ev['sakit'] ?? 0) > 0)
                                                                <span class="badge bg-red-lt" title="Sakit">S</span>
                                                            @endif
                                                            @if (($ev['cuti'] ?? 0) > 0)
                                                                <span class="badge bg-indigo-lt" title="Cuti">C</span>
                                                            @endif
                                                            @if (($ev['dinas'] ?? 0) > 0)
                                                                <span class="badge bg-azure-lt" title="Dinas">D</span>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex justify-content-center gap-1 mt-1">
                                                            @if ($ev['holiday'])
                                                                <span
                                                                    style="width:5px; height:5px; border-radius:50%; background:#d63939;"
                                                                    title="Libur Nasional"></span>
                                                            @endif
                                                            @if ($ev['kpi_cutoff'])
                                                                <span
                                                                    style="width:5px; height:5px; border-radius:50%; background:#206bc4;"
                                                                    title="Cutoff KPI"></span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <script type="application/json" id="calendarDetailsPayload">@json($calendarDailyDetails ?? [])</script>
                            <div class="hr-calendar-note hr-calendar-legend mt-2">
                                <span class="hr-calendar-legend-item">Klik tanggal untuk detail</span>
                                <span class="hr-calendar-legend-item">I = Izin</span>
                                <span class="hr-calendar-legend-item">S = Sakit</span>
                                <span class="hr-calendar-legend-item">C = Cuti</span>
                                <span class="hr-calendar-legend-item">D = Dinas</span>
                                <span class="hr-calendar-legend-item"><span class="hr-calendar-legend-dot"
                                        style="background:#d63939;"></span> = Libur nasional</span>
                                <span class="hr-calendar-legend-item"><span class="hr-calendar-legend-dot"
                                        style="background:#206bc4;"></span> = Cutoff Periode</span>
                            </div>
                        </div>
                    </div>

                    {{-- 4. PENGUMUMAN & MOMEN SPESIAL (TABBED) --}}
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-header p-2 border-bottom-0">
                            @php $jumlahUltahBulanIni = collect($ulangTahunBulanIni ?? [])->count(); @endphp
                            <ul class="nav nav-tabs card-header-tabs w-100 culture-tabs" data-bs-toggle="tabs">
                                <li class="nav-item"><a href="#tab-feed-pengumuman" class="nav-link active px-2 py-1"
                                        data-bs-toggle="tab">📢 Pengumuman</a></li>
                                <li class="nav-item">
                                    <a href="#tab-feed-momen" class="nav-link px-2 py-1" data-bs-toggle="tab">
                                        🎉 Ultah Bulan Ini
                                        <span class="badge bg-pink-lt text-pink ms-1">{{ $jumlahUltahBulanIni }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-0 card-body-scrollable" style="height: 180px;">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tab-feed-pengumuman">
                                    <div class="list-group list-group-flush">
                                        @forelse(collect($pengumumanAktif ?? [])->filter() as $pg)
                                            <a href="{{ route('pengumuman.index') }}"
                                                class="list-group-item list-group-item-action p-2">
                                                <div class="d-flex align-items-start justify-content-between gap-2">
                                                    <div class="fw-semibold text-truncate small">{{ $pg->judul }}</div>
                                                    <span
                                                        class="badge {{ (int) ($pg->is_active ?? 0) === 1 ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                                        {{ (int) ($pg->is_active ?? 0) === 1 ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </div>
                                                <div class="text-secondary" style="font-size: 10px;">
                                                    {{ optional($pg->tanggal_mulai)->format('d M') ?? '-' }} -
                                                    {{ optional($pg->tanggal_selesai)->format('d M Y') ?? '-' }}
                                                </div>
                                            </a>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Belum ada data pengumuman pada
                                                periode ini.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="tab-pane" id="tab-feed-momen">
                                    <div class="birthday-feed-wrap card-body-scrollable"
                                        style="max-height: 180px; overflow-y: auto; overflow-x: hidden;">
                                        @forelse(($ulangTahunBulanIni ?? collect()) as $u)
                                            <div class="birthday-feed-item">
                                                <span class="avatar flex-shrink-0"
                                                    style="background-image:url('{{ $u->foto ? asset('storage/uploads/karyawan/' . $u->foto) : asset('assets/img/nophoto.png') }}')"></span>
                                                <div class="min-w-0 flex-fill">
                                                    <div class="birthday-feed-name text-truncate">{{ $u->nama_lengkap }}
                                                    </div>
                                                    <div class="birthday-feed-meta text-truncate">
                                                        {{ data_get($u, 'cabang.nama_cabang', '-') }}
                                                    </div>
                                                </div>
                                                <span
                                                    class="birthday-feed-date">{{ \Carbon\Carbon::parse($u->tanggal_lahir)->format('d M') }}</span>
                                            </div>
                                        @empty
                                            <div class="p-3 text-center text-muted small">Tidak ada karyawan yang ulang
                                                tahun pada bulan ini.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. ACTIVITY FEED --}}
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header p-2 border-bottom-0">
                            <h3 class="card-title fs-5">📋 Activity Feed</h3>
                        </div>
                        <div class="card-body p-3 card-body-scrollable" style="height: 180px;">
                            <ul class="list list-timeline list-timeline-simple mb-0">
                                @php
                                    $recentActivities = collect([]);
                                    foreach (($izinPending ?? collect())->take(2) as $i) {
                                        $recentActivities->push([
                                            'time' => $i->created_at ?? now(),
                                            'text' => 'Izin dari ' . ($i->nama_lengkap ?? '-') . ' menunggu review.',
                                            'color' => 'bg-yellow',
                                        ]);
                                    }
                                    foreach (($lemburPending ?? collect())->take(2) as $l) {
                                        $recentActivities->push([
                                            'time' => $l->created_at ?? now(),
                                            'text' => 'Lembur ' . ($l->nama_lengkap ?? '-') . ' masuk antrean.',
                                            'color' => 'bg-blue',
                                        ]);
                                    }
                                    $recentActivities = $recentActivities->sortByDesc('time')->take(5);
                                @endphp
                                @forelse($recentActivities as $log)
                                    <li class="list-timeline-item pt-0 pb-2">
                                        <div class="list-timeline-icon {{ $log['color'] }}"></div>
                                        <div class="list-timeline-content text-truncate">
                                            <div class="text-truncate fw-semibold small">{{ $log['text'] }}</div>
                                            <div class="text-secondary" style="font-size: 10px;">
                                                {{ \Carbon\Carbon::parse($log['time'])->diffForHumans() }}</div>
                                        </div>
                                    </li>
                                @empty
                                    <div class="text-center text-muted small">Belum ada aktivitas terbaru.</div>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                </div>
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
                            <h6 class="mb-3 d-flex align-items-center"><span class="badge bg-success me-2">Masuk</span>
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
                                                        class="badge bg-blue-lt font-monospace">{{ optional($item)->nik }}</span>
                                                </td>
                                                <td>{{ optional($item)->nama_lengkap }}</td>
                                                <td><span
                                                        class="text-muted">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                </td>
                                                <td><span
                                                        class="badge bg-orange-lt">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
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
                                                        class="badge bg-green-lt">{{ $tglMasukDisplay ? \Carbon\Carbon::parse($tglMasukDisplay)->translatedFormat('d F Y') : '-' }}</span>
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
                            <h6 class="mb-3 d-flex align-items-center"><span class="badge bg-danger me-2">Keluar</span>
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
                                                        class="badge bg-blue-lt font-monospace">{{ optional($item)->nik }}</span>
                                                </td>
                                                <td>{{ optional($item)->nama_lengkap }}</td>
                                                <td><span
                                                        class="text-muted">{{ data_get($item, 'cabang.nama_cabang', '-') }}</span>
                                                </td>
                                                <td><span
                                                        class="badge bg-orange-lt">{{ data_get($item, 'jabatanRel.nama_jabatan', '-') }}</span>
                                                </td>
                                                @php
                                                    $tglKeluarDisplay =
                                                        data_get($item, 'tanggal_keluar') ?:
                                                        data_get($item, 'updated_at');
                                                @endphp
                                                <td><span
                                                        class="badge bg-red-lt">{{ $tglKeluarDisplay ? \Carbon\Carbon::parse($tglKeluarDisplay)->translatedFormat('d F Y') : '-' }}</span>
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
                <div class="modal-footer"><button type="button" class="btn btn-primary"
                        data-bs-dismiss="modal">Tutup</button></div>
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
                        <table class="table table-vcenter card-table table-striped">
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
                                                class="badge bg-blue-lt font-monospace">{{ optional($item)->nik }}</span>
                                        </td>
                                        <td><span
                                                class="badge bg-orange-lt text-wrap">{{ optional($item)->jabatan_nama ?? '-' }}</span>
                                        </td>
                                        <td class="text-nowrap"><span
                                                class="badge bg-lime-lt">{{ \Carbon\Carbon::parse(optional($item)->tanggal_awal_kontrak)->translatedFormat('d F Y') }}</span>
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
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
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
                        <table class="table table-vcenter card-table table-striped">
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
                                        <td class="text-end"><span class="badge bg-dark-lt">{{ $item->jumlah }}</span>
                                        </td>
                                        <td class="text-end"><span
                                                class="badge bg-azure-lt">{{ round($persentase, 1) }}%</span></td>
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
                <div class="modal-footer"><button type="button" class="btn btn-primary"
                        data-bs-dismiss="modal">Tutup</button></div>
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
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
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

            // 1-4. SPARKLINE STATS
            const sparkOpts = {
                chart: {
                    type: 'line',
                    height: 20,
                    sparkline: {
                        enabled: true
                    },
                    animations: {
                        enabled: false
                    }
                },
                stroke: {
                    width: 2,
                    curve: 'smooth'
                }
            };

            initChart('sparkline-hadir', {
                ...sparkOpts,
                colors: ['#2fb344'],
                series: [{
                    data: @json($trendHadir ?? [0, 0, 0])
                }]
            });
            initChart('sparkline-terlambat', {
                ...sparkOpts,
                colors: ['#f59f00'],
                series: [{
                    data: @json($trendTerlambat ?? [0, 0, 0])
                }]
            });
            initChart('sparkline-izin', {
                ...sparkOpts,
                colors: ['#4299e1'],
                series: [{
                    data: @json($trendIzin ?? [0, 0, 0])
                }]
            });
            initChart('sparkline-alpha', {
                ...sparkOpts,
                colors: ['#d63939'],
                series: [{
                    data: @json($trendAlpha ?? [0, 0, 0])
                }]
            });

            // 5. CHART TURNOVER
            @php
                $masukArr = [];
                $keluarArr = [];
                $kontrakHabisArr = [];
                $turnoverRateArr = [];
                if (isset($turnoverData)) {
                    foreach ($turnoverData as $t) {
                        $masukArr[] = (int) ($t['masuk'] ?? 0);
                        $keluarArr[] = (int) ($t['keluar'] ?? 0);
                        $kontrakHabisArr[] = (int) ($t['kontrak_habis'] ?? 0);
                        $turnoverRateArr[] = (float) ($t['turnover_rate'] ?? 0);
                    }
                }
            @endphp
            initChart('chart-turnover', {
                chart: {
                    type: "line",
                    height: 220,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent'
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: [0, 0, 0, 2.5],
                    curve: "smooth"
                },
                series: [{
                        name: "Masuk Aktual",
                        type: "column",
                        data: @json($masukArr)
                    },
                    {
                        name: "Keluar Aktual",
                        type: "column",
                        data: @json($keluarArr)
                    },
                    {
                        name: "Kontrak Habis",
                        type: "column",
                        data: @json($kontrakHabisArr)
                    },
                    {
                        name: "Turnover Rate (%)",
                        type: "line",
                        data: @json($turnoverRateArr)
                    }
                ],
                colors: ["#206bc4", "#d63939", "#f59f00", "#2b8a3e"],
                xaxis: {
                    categories: @json($turnoverLabels ?? [''])
                },
                yaxis: [{
                        title: {
                            text: 'Jumlah Karyawan'
                        },
                        labels: {
                            formatter: function(v) {
                                return Math.round(v);
                            }
                        }
                    },
                    {
                        opposite: true,
                        title: {
                            text: 'Turnover Rate (%)'
                        },
                        min: 0,
                        labels: {
                            formatter: function(v) {
                                return v.toFixed(1) + '%';
                            }
                        }
                    }
                ],
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(value, {
                            seriesIndex
                        }) {
                            return seriesIndex === 3 ? value.toFixed(2) + '%' : Math.round(value);
                        }
                    }
                },
                legend: {
                    position: 'bottom'
                }
            });

            // 6. CHART KPI CABANG
            initChart('chart-kpi-by-cabang', {
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        columnWidth: '50%'
                    }
                },
                dataLabels: {
                    enabled: false
                },
                series: [{
                    name: 'Skor KPI',
                    data: @json($kpiCabangSeries ?? [])
                }],
                xaxis: {
                    categories: @json($kpiCabangLabels ?? [])
                },
                colors: ['#6f42c1']
            });

            // 7. CHART DEMOGRAFI UMUR
            @php
                $umur = $dataSebaranUmur ?? null;
                $umurData = [optional($umur)->umur_under_20 ?? 0, optional($umur)->umur_20_29 ?? 0, optional($umur)->umur_30_39 ?? 0, optional($umur)->umur_40_49 ?? 0, optional($umur)->umur_50_plus ?? 0];
            @endphp
            initChart('chart-sebaran-umur-donut', {
                chart: {
                    type: "donut",
                    height: 200
                },
                series: @json($umurData),
                labels: ["< 20", "20-29", "30-39", "40-49", "≥ 50"],
                colors: ["#4299e1", "#48bb78", "#ecc94b", "#ed8936", "#f56565"],
                legend: {
                    position: 'right'
                }
            });

            // 8. CHART GENDER
            @php
                $gender = $dataSebaranGender ?? null;
                $genderData = [optional($gender)->laki_laki ?? 0, optional($gender)->perempuan ?? 0];
            @endphp
            initChart('chart-sebaran-gender', {
                chart: {
                    type: "pie",
                    height: 200
                },
                series: @json($genderData),
                labels: ["Laki-laki", "Perempuan"],
                colors: ["#206bc4", "#ff4d6d"],
                legend: {
                    position: 'right'
                }
            });

            // 9. CHART PENDIDIKAN
            @php
                $pend = $dataPendidikan ?? null;
                $pendData = [optional($pend)->sma ?? 0, optional($pend)->d3 ?? 0, optional($pend)->s1 ?? 0, optional($pend)->s2 ?? 0];
            @endphp
            initChart('chart-pendidikan', {
                chart: {
                    type: "donut",
                    height: 200
                },
                series: @json($pendData),
                labels: ["SMA", "D3", "S1", "S2"],
                colors: ["#206bc4", "#2fb344", "#f59f00", "#d63939"],
                legend: {
                    position: 'right'
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

            const renderList = (title, items, badgeClass) => {
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
                                    <span class="fw-semibold">${title}</span>
                                    <span class="badge ${badgeClass}">${list.length}</span>
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
                        `<span class="badge bg-blue-lt">Izin: ${(detail.izin || []).length}</span>`,
                        `<span class="badge bg-red-lt">Sakit: ${(detail.sakit || []).length}</span>`,
                        `<span class="badge bg-indigo-lt">Cuti: ${(detail.cuti || []).length}</span>`,
                        `<span class="badge bg-azure-lt">Dinas Luar: ${(detail.dinas || []).length}</span>`,
                        `<span class="badge bg-dark-lt text-dark">Total Karyawan: ${uniqueNiks.length}</span>`
                    ].join('');

                    detailBody.innerHTML = [
                        renderList('Izin', detail.izin, 'bg-blue-lt'),
                        renderList('Sakit', detail.sakit, 'bg-red-lt'),
                        renderList('Cuti', detail.cuti, 'bg-indigo-lt'),
                        renderList('Dinas Luar', detail.dinas, 'bg-azure-lt')
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
