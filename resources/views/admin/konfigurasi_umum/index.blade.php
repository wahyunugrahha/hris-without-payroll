@extends('layouts.admin.tabler')

@php
    $labelKhusus = [
        'point_gaji_kantor' => 'Point gaji & bonus kantor',
        'point_gaji_tambang' => 'Point gaji & bonus tambang',
    ];
    $labelSetting = fn ($item) => $labelKhusus[$item->key] ?? ucfirst(str_replace('_', ' ', $item->key));
@endphp

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Konfigurasi Umum</h2>
                    <p class="page-subtitle">Pengaturan sistem yang berlaku untuk seluruh cabang.</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('konfigurasi.umum.update') }}" method="POST">
                @csrf
                <div class="settings-layout">
                    <nav class="settings-nav" aria-label="Kelompok pengaturan" role="tablist">
                        @foreach ($konfigurasi as $group => $items)
                            @php $slug = \Illuminate\Support\Str::slug($group); @endphp
                            <button type="button" class="settings-nav-link {{ $loop->first ? 'active' : '' }}" id="tab-{{ $slug }}"
                                data-bs-toggle="pill" data-bs-target="#panel-{{ $slug }}" role="tab"
                                aria-controls="panel-{{ $slug }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                {{ $group }}
                                <span class="settings-nav-count">{{ count($items) }}</span>
                            </button>
                        @endforeach
                    </nav>

                    <div class="tab-content">
                        @foreach ($konfigurasi as $group => $items)
                            @php $slug = \Illuminate\Support\Str::slug($group); @endphp
                            <section class="card tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="panel-{{ $slug }}"
                                role="tabpanel" aria-labelledby="tab-{{ $slug }}">
                                <div class="card-header">
                                    <h3 class="card-title">{{ $group }}</h3>
                                </div>
                                <div class="card-body p-0">
                                    @foreach ($items as $item)
                                        @php $idField = 'setting-' . $item->key; @endphp
                                        <div class="setting-field">
                                            <div>
                                                <label class="setting-title" for="{{ $idField }}">{{ $labelSetting($item) }}</label>
                                                @if ($item->description)
                                                    <span class="setting-desc">{{ $item->description }}</span>
                                                @endif
                                            </div>
                                            <div>
                                                @if ($item->type == 'number')
                                                    <input type="number" name="settings[{{ $item->key }}]" id="{{ $idField }}" class="form-control" value="{{ $item->value }}">
                                                @elseif ($item->type == 'textarea')
                                                    <textarea name="settings[{{ $item->key }}]" id="{{ $idField }}" class="form-control" rows="3">{{ $item->value }}</textarea>
                                                @elseif ($item->type == 'select-multiple')
                                                    @php $terpilih = array_map('trim', explode(',', $item->value ?? '')); @endphp
                                                    <select name="settings[{{ $item->key }}][]" id="{{ $idField }}" class="form-select tom-select-multiple" multiple autocomplete="off">
                                                        @foreach ($cabang as $c)
                                                            <option value="{{ $c->kode_cabang }}" @selected(in_array($c->kode_cabang, $terpilih))>{{ $c->nama_cabang }} ({{ $c->kode_cabang }})</option>
                                                        @endforeach
                                                    </select>
                                                @elseif ($item->type == 'select' && $item->key == 'sp_tambang_aktif')
                                                    <select name="settings[{{ $item->key }}]" id="{{ $idField }}" class="form-select">
                                                        <option value="1" @selected($item->value == '1')>Aktif</option>
                                                        <option value="0" @selected($item->value == '0')>Nonaktif</option>
                                                    </select>
                                                @else
                                                    <input type="text" name="settings[{{ $item->key }}]" id="{{ $idField }}" class="form-control" value="{{ $item->value }}">
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </div>

                <div class="save-bar mt-3">
                    <span class="text-secondary small">Perubahan di semua kelompok disimpan sekaligus.</span>
                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('myscript')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.querySelectorAll('.tom-select-multiple').forEach(function(el) {
            new TomSelect(el, {
                plugins: ['remove_button'],
                create: false,
                persist: false,
                placeholder: 'Pilih cabang…',
                maxItems: null
            });
        });
    </script>
@endpush
