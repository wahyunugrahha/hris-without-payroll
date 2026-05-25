@extends('layouts.admin.tabler')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12 mt-3">
                <h3>Pengumuman</h3>
            </div>

            @can('pengumuman-create-admin')
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('pengumuman.store') }}" method="post" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Judul</label>
                                    <input type="text" name="judul" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Isi Pengumuman</label>
                                    <textarea name="isi" class="form-control" rows="4"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gambar (opsional)</label>
                                    <input type="file" name="gambar" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Selesai</label>
                                    <input type="date" name="tanggal_selesai" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <button class="btn btn-primary" type="submit">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5>Daftar Pengumuman</h5>
                        <div class="list-group">
                            @foreach ($pengumuman as $p)
                                <div class="list-group-item d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold">{{ $p->judul }}</div>
                                        <div class="small text-muted">{{ $p->tanggal_mulai }} - {{ $p->tanggal_selesai }}
                                        </div>
                                    </div>
                                    <div>
                                        @can('pengumuman-edit-admin')
                                            <form action="{{ route('pengumuman.toggle', $p->id) }}" method="post"
                                                style="display:inline-block">
                                                @csrf
                                                <button class="btn btn-sm btn-ghost-secondary" title="Ganti Status">
                                                    {{ $p->is_active ? 'Non-aktifkan' : 'Aktifkan' }}
                                                </button>
                                            </form>
                                            <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                                data-bs-target="#modal-edit-{{ $p->id }}">Edit</a>
                                        @endcan

                                        @can('pengumuman-delete-admin')
                                            <form action="{{ route('pengumuman.destroy', $p->id) }}" method="post"
                                                onsubmit="return confirm('Hapus pengumuman?')" style="display:inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">Hapus</button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>

                                @can('pengumuman-edit-admin')
                                    <div class="modal modal-blur fade" id="modal-edit-{{ $p->id }}" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Pengumuman</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('pengumuman.update', $p->id) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Judul</label>
                                                            <input type="text" name="judul" class="form-control"
                                                                value="{{ $p->judul }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Isi Pengumuman</label>
                                                            <textarea name="isi" class="form-control" rows="4">{{ $p->isi }}</textarea>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Gambar (opsional)</label>
                                                            <input type="file" name="gambar" class="form-control" accept="image/*"
                                                                onchange="document.getElementById('preview-img-{{ $p->id }}').src = window.URL.createObjectURL(this.files[0]); document.getElementById('preview-container-{{ $p->id }}').classList.remove('d-none');">
                                                            <small class="text-muted">Biarkan kosong jika tidak ingin mengubah gambar.</small>
                                                        </div>
                                                        
                                                        <div class="mb-3 text-center {{ $p->gambar ? '' : 'd-none' }}" id="preview-container-{{ $p->id }}">
                                                            <p class="mb-1 small text-muted">Preview Gambar:</p>
                                                            <img id="preview-img-{{ $p->id }}" src="{{ $p->gambar ? asset('storage/' . $p->gambar) : '' }}" class="img-fluid rounded border" style="max-height: 200px; object-fit: contain;">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Tanggal Mulai</label>
                                                            <input type="date" name="tanggal_mulai" class="form-control"
                                                                value="{{ $p->tanggal_mulai }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Tanggal Selesai</label>
                                                            <input type="date" name="tanggal_selesai" class="form-control"
                                                                value="{{ $p->tanggal_selesai }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn me-auto"
                                                            data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan
                                                            Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endcan
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
