@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Konfigurasi Umum</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <!-- Tom Select CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">


    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('konfigurasi.umum.update') }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Pengaturan Sistem</h3>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2" />
                                <circle cx="12" cy="14" r="2" />
                                <polyline points="14 4 14 8 8 8 8 4" />
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    @php $first = true; @endphp
                                    @foreach($konfigurasi as $group => $items)
                                        <a class="nav-link {{ $first ? 'active' : '' }}" id="v-pills-{{ \Illuminate\Support\Str::slug($group) }}-tab" data-bs-toggle="pill" href="#v-pills-{{ \Illuminate\Support\Str::slug($group) }}" role="tab" aria-controls="v-pills-{{ \Illuminate\Support\Str::slug($group) }}" aria-selected="{{ $first ? 'true' : 'false' }}">{{ $group }}</a>
                                        @php $first = false; @endphp
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="tab-content" id="v-pills-tabContent">
                                    @php $first = true; @endphp
                                    @foreach($konfigurasi as $group => $items)
                                        <div class="tab-pane fade {{ $first ? 'show active' : '' }}" id="v-pills-{{ \Illuminate\Support\Str::slug($group) }}" role="tabpanel" aria-labelledby="v-pills-{{ \Illuminate\Support\Str::slug($group) }}-tab">
                                            <div class="card">
                                                <div class="card-body">
                                                    @foreach($items as $item)
                                                        <div class="mb-3">
                                                            <label class="form-label font-weight-bold">
                                                                @if($item->key == 'point_gaji_kantor')
                                                                    Point Gaji & Bonus Kantor
                                                                @elseif($item->key == 'point_gaji_tambang')
                                                                    Point Gaji & Bonus Tambang
                                                                @else
                                                                    {{ ucwords(str_replace('_', ' ', $item->key)) }}
                                                                @endif
                                                            </label>
                                                            @if($item->description)
                                                                <div class="form-hint mb-2">{{ $item->description }}</div>
                                                            @endif
                                                            
                                                            @if($item->type == 'number')
                                                                <input type="number" name="settings[{{ $item->key }}]" class="form-control" value="{{ $item->value }}">
                                                            @elseif($item->type == 'textarea')
                                                                <textarea name="settings[{{ $item->key }}]" class="form-control" rows="3">{{ $item->value }}</textarea>
                                                            @elseif($item->type == 'select-multiple')
                                                                @php
                                                                    $selectedValues = array_map('trim', explode(',', $item->value ?? ''));
                                                                @endphp
                                                                <select name="settings[{{ $item->key }}][]" class="form-select tom-select-multiple" multiple autocomplete="off">
                                                                    @foreach($cabang as $c)
                                                                        <option value="{{ $c->kode_cabang }}" {{ in_array($c->kode_cabang, $selectedValues) ? 'selected' : '' }}>
                                                                            {{ $c->nama_cabang }} ({{ $c->kode_cabang }})
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            @elseif($item->type == 'select' && $item->key == 'sp_tambang_aktif')
                                                                <select name="settings[{{ $item->key }}]" class="form-select">
                                                                    <option value="1" {{ $item->value == '1' ? 'selected' : '' }}>Aktif</option>
                                                                    <option value="0" {{ $item->value == '0' ? 'selected' : '' }}>Nonaktif</option>
                                                                </select>
                                                            @else
                                                                <input type="text" name="settings[{{ $item->key }}]" class="form-control" value="{{ $item->value }}">
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        @php $first = false; @endphp
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('myscript')
    <!-- Tom Select JS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.tom-select-multiple').forEach(function (el) {
                new TomSelect(el, {
                    plugins: ['remove_button'],
                    create: false,
                    persist: false,
                    placeholder: 'Pilih Cabang...',
                    maxItems: null,
                });
            });
        });
    </script>
@endpush
