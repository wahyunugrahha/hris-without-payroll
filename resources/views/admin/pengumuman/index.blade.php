@extends('layouts.admin.tabler')

@section('page-header')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Data Master</div>
                    <h2 class="page-title">Pengumuman</h2>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $tglPendek = fn ($t) => $t ? \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d M Y') : '-';
    @endphp
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-3">
                <div class="{{ auth('user')->user()->can('pengumuman-create-admin') ? 'col-lg-7' : 'col-12' }}">
                    <section class="card" aria-labelledby="judul-daftar-pengumuman">
                        <div class="card-header">
                            <h3 class="card-title" id="judul-daftar-pengumuman">Daftar pengumuman</h3>
                            <span class="card-subtitle ms-auto">{{ count($pengumuman) }} pengumuman</span>
                        </div>
                        @if (count($pengumuman) === 0)
                            <div class="list-empty">
                                <p class="mb-1 fw-medium">Belum ada pengumuman.</p>
                                <p class="mb-0 text-secondary small">Pengumuman yang dibuat akan tampil di sini dan di aplikasi karyawan.</p>
                            </div>
                        @else
                            <ul class="announce-list">
                                @foreach ($pengumuman as $p)
                                    <li class="announce-item">
                                        <div class="min-w-0 flex-fill">
                                            <div class="announce-title" title="{{ $p->judul }}">{{ $p->judul }}</div>
                                            <div class="announce-meta">
                                                {{ $tglPendek($p->tanggal_mulai) }} – {{ $tglPendek($p->tanggal_selesai) }}
                                                <span class="emp-status emp-status--{{ $p->is_active ? 'success' : 'neutral' }}">{{ $p->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                            </div>
                                            @if (filled($p->isi))
                                                <p class="announce-body">{{ \Illuminate\Support\Str::limit($p->isi, 140) }}</p>
                                            @endif
                                        </div>
                                        @canany(['pengumuman-edit-admin', 'pengumuman-delete-admin'])
                                            <div class="dropdown">
                                                <button type="button" class="row-menu-btn" data-bs-toggle="dropdown" aria-expanded="false"
                                                    aria-label="Aksi untuk {{ $p->judul }}" title="Aksi">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24"
                                                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                                        stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                        <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                        <path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                                    </svg>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    @can('pengumuman-edit-admin')
                                                        <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                            data-bs-target="#modal-edit-{{ $p->id }}">Edit</button>
                                                        <form action="{{ route('pengumuman.toggle', $p->id) }}" method="post">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">{{ $p->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                                        </form>
                                                    @endcan
                                                    @can('pengumuman-delete-admin')
                                                        <div class="dropdown-divider"></div>
                                                        <form action="{{ route('pengumuman.destroy', $p->id) }}" method="post">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger" data-hapus-pengumuman
                                                                data-judul="{{ $p->judul }}">Hapus</button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </div>
                                        @endcanany
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                @can('pengumuman-create-admin')
                    <div class="col-lg-5">
                        <section class="card" aria-labelledby="judul-buat-pengumuman">
                            <div class="card-header">
                                <h3 class="card-title" id="judul-buat-pengumuman">Buat pengumuman</h3>
                            </div>
                            <form action="{{ route('pengumuman.store') }}" method="post" enctype="multipart/form-data" class="card-body">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label required" for="pengumuman-judul">Judul</label>
                                    <input type="text" name="judul" id="pengumuman-judul" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="pengumuman-isi">Isi pengumuman</label>
                                    <textarea name="isi" id="pengumuman-isi" class="form-control" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="pengumuman-gambar">Gambar <span class="text-secondary fw-normal">(opsional)</span></label>
                                    <input type="file" name="gambar" id="pengumuman-gambar" class="form-control" accept="image/*">
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-sm-6">
                                        <label class="form-label required" for="pengumuman-mulai">Tanggal mulai</label>
                                        <input type="date" name="tanggal_mulai" id="pengumuman-mulai" class="form-control" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label required" for="pengumuman-selesai">Tanggal selesai</label>
                                        <input type="date" name="tanggal_selesai" id="pengumuman-selesai" class="form-control" required>
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100" type="submit">Simpan pengumuman</button>
                            </form>
                        </section>
                    </div>
                @endcan
            </div>
        </div>
    </div>

    @can('pengumuman-edit-admin')
        @foreach ($pengumuman as $p)
            <div class="modal modal-blur fade" id="modal-edit-{{ $p->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit pengumuman</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <form action="{{ route('pengumuman.update', $p->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label required" for="edit-judul-{{ $p->id }}">Judul</label>
                                    <input type="text" name="judul" id="edit-judul-{{ $p->id }}" class="form-control" value="{{ $p->judul }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit-isi-{{ $p->id }}">Isi pengumuman</label>
                                    <textarea name="isi" id="edit-isi-{{ $p->id }}" class="form-control" rows="4">{{ $p->isi }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="edit-gambar-{{ $p->id }}">Gambar <span class="text-secondary fw-normal">(opsional)</span></label>
                                    <input type="file" name="gambar" id="edit-gambar-{{ $p->id }}" class="form-control" accept="image/*"
                                        data-pratinjau="#preview-img-{{ $p->id }}">
                                    <div class="form-hint">Biarkan kosong jika gambar tidak diubah.</div>
                                </div>
                                <div class="mb-3 {{ $p->gambar ? '' : 'd-none' }}" data-pratinjau-wadah>
                                    <img id="preview-img-{{ $p->id }}" src="{{ $p->gambar ? asset_v('storage/' . $p->gambar) : '' }}"
                                        alt="Pratinjau gambar pengumuman" class="announce-preview">
                                </div>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label required" for="edit-mulai-{{ $p->id }}">Tanggal mulai</label>
                                        <input type="date" name="tanggal_mulai" id="edit-mulai-{{ $p->id }}" class="form-control" value="{{ $p->tanggal_mulai }}" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label required" for="edit-selesai-{{ $p->id }}">Tanggal selesai</label>
                                        <input type="date" name="tanggal_selesai" id="edit-selesai-{{ $p->id }}" class="form-control" value="{{ $p->tanggal_selesai }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endcan
@endsection

@push('myscript')
    <script>
        // Pratinjau gambar saat mengganti gambar pengumuman.
        document.querySelectorAll('[data-pratinjau]').forEach(function(input) {
            input.addEventListener('change', function() {
                if (!input.files[0]) return;
                var img = document.querySelector(input.dataset.pratinjau);
                img.src = URL.createObjectURL(input.files[0]);
                img.closest('[data-pratinjau-wadah]').classList.remove('d-none');
            });
        });

        // Hapus lewat konfirmasi yang sama dengan halaman lain.
        document.querySelectorAll('[data-hapus-pengumuman]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus pengumuman?',
                    text: '"' + btn.dataset.judul + '" akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--color-danger)',
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(function(hasil) {
                    if (hasil.isConfirmed) btn.closest('form').submit();
                });
            });
        });
    </script>
@endpush
