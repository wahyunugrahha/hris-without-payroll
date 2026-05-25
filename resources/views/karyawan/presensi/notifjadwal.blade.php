@extends('layouts.presensi')
@section('header')
    <div class="presensi-header">
        <div class="header-spacer"></div>
        <span class="header-title">Presensi</span>
        <a href="#" class="header-spacer headerButton" data-toggle="modal" data-target="#infoModal"
            style="flex: 0; width: auto;">
            <ion-icon name="information-circle-outline"></ion-icon>
        </a>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
@endsection

<style>
    .jam-digital-malasngoding {
        background-color: #27272783;
        position: absolute;
        top: 0px;
        right: 5px;
        z-index: 9999;
        width: 150px;
        border-radius: 10px;
        padding: 5px;
    }

    .jam-digital-malasngoding p {
        color: #fff;
        font-size: 16px;
        text-align: left;
        margin-top: 0;
        margin-bottom: 0;
    }
</style>

@section('content')
    <div class="row" style="margin-top: 24px">
        <div class="col">
            <div class="alert alert-warning">
                <p>Maaf, Anda Tidak Memiliki Jadwal Pada Hari ini !, Silahkan Hubungi HRD</p>
            </div>
        </div>
    </div>
@endsection
