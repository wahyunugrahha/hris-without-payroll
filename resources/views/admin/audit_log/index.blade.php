@extends('layouts.admin.tabler')

@section('content')
    <div class="page-header d-print-none" aria-label="Page header">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Konfigurasi</div>
                    <h2 class="page-title">Audit Log System</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            {{-- Search / Filter Form --}}
                            <div class="row mt-2">
                                <div class="col-12">
                                    <form action="{{ route('audit-log.index') }}" method="GET">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <select name="event" class="form-select">
                                                        <option value="">-- Semua Aksi --</option>
                                                        <option value="created" {{ request()->event == 'created' ? 'selected' : '' }}>Created</option>
                                                        <option value="updated" {{ request()->event == 'updated' ? 'selected' : '' }}>Updated</option>
                                                        <option value="deleted" {{ request()->event == 'deleted' ? 'selected' : '' }}>Deleted</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-search">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                                        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                                        <path d="M21 21l-6 -6" />
                                                    </svg>
                                                    <span class="ms-1">Filter</span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {{-- Data Table --}}
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table class="table table-vcenter table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Waktu</th>
                                                    <th>User (Causer)</th>
                                                    <th>Aksi</th>
                                                    <th>Model (Subjek)</th>
                                                    <th>Detail Perubahan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($logs as $log)
                                                    <tr>
                                                        <td>{{ $loop->iteration + $logs->firstItem() - 1 }}</td>
                                                        <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                                        <td>
                                                            @if($log->causer)
                                                                <span class="badge bg-blue-lt">{{ $log->causer->name ?? 'Unknown' }}</span>
                                                            @else
                                                                <span class="badge bg-secondary-lt">System</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if($log->event == 'created')
                                                                <span class="badge bg-success-lt">Created</span>
                                                            @elseif($log->event == 'updated')
                                                                <span class="badge bg-warning-lt">Updated</span>
                                                            @elseif($log->event == 'deleted')
                                                                <span class="badge bg-danger-lt">Deleted</span>
                                                            @else
                                                                <span class="badge bg-primary-lt">{{ $log->event }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <small class="text-muted d-block">{{ str_replace('App\\Models\\', '', $log->subject_type) }}</small>
                                                            <strong>ID: {{ $log->subject_id }}</strong>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modal-detail-{{ $log->id }}">
                                                                Lihat Detail
                                                            </button>

                                                            {{-- Modal Detail --}}
                                                            <div class="modal modal-blur fade" id="modal-detail-{{ $log->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Detail Perubahan Data ({{ str_replace('App\\Models\\', '', $log->subject_type) }} #{{ $log->subject_id }})</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <div class="row">
                                                                                @if(isset($log->properties['old']))
                                                                                <div class="col-md-6">
                                                                                    <h6>Data Lama (Old)</h6>
                                                                                    <pre class="bg-dark text-light p-2 rounded" style="max-height: 300px; overflow-y: auto;">{{ json_encode($log->properties['old'], JSON_PRETTY_PRINT) }}</pre>
                                                                                </div>
                                                                                @endif

                                                                                @if(isset($log->properties['attributes']))
                                                                                <div class="col-md-{{ isset($log->properties['old']) ? '6' : '12' }}">
                                                                                    <h6>Data Baru (New)</h6>
                                                                                    <pre class="bg-dark text-success p-2 rounded" style="max-height: 300px; overflow-y: auto;">{{ json_encode($log->properties['attributes'], JSON_PRETTY_PRINT) }}</pre>
                                                                                </div>
                                                                                @endif

                                                                                @if(!isset($log->properties['old']) && !isset($log->properties['attributes']))
                                                                                <div class="col-12">
                                                                                    <div class="alert alert-secondary">Tidak ada detail atribut yang dilacak untuk aksi ini.</div>
                                                                                </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center">Belum ada data audit log.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-center justify-content-md-end">
                                        {{ $logs->links('pagination::bootstrap-5') }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
