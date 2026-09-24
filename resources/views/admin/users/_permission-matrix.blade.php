{{--
    Matriks izin per modul. $izin = koleksi Permission satu guard, $dimiliki = nama izin yang sudah dimiliki (array flip).
    Nama izin "modul-aksi" dikelompokkan per modul.
--}}
@php
    $perModul = collect($izin)->groupBy(fn ($p) => explode('-', $p->name, 2)[0]);
    $dimiliki = $dimiliki ?? [];
@endphp
<div class="perm-matrix" data-perm-matrix>
    <div class="perm-matrix-head">
        <label class="form-check m-0">
            <input type="checkbox" class="form-check-input global-master-check">
            <span class="form-check-label fw-medium">Pilih semua izin</span>
        </label>
        <span class="check-list-count">{{ $perModul->count() }} modul</span>
    </div>
    @forelse ($perModul as $modul => $daftar)
        @php $semua = $daftar->every(fn ($p) => isset($dimiliki[$p->name])); @endphp
        <div class="perm-row">
            <label class="form-check m-0 perm-module">
                <input type="checkbox" class="form-check-input row-master-check" @checked($semua)>
                <span class="form-check-label text-capitalize">{{ str_replace('_', ' ', $modul) }}</span>
            </label>
            <div class="perm-actions">
                @foreach ($daftar as $p)
                    <label class="perm-chip">
                        <input class="form-check-input perm-item-check" type="checkbox" name="permissions[]" value="{{ $p->name }}"
                            @checked(isset($dimiliki[$p->name]))>
                        <span>{{ explode('-', $p->name, 2)[1] ?? $p->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-secondary small p-3 mb-0">Belum ada izin untuk panel ini.</p>
    @endforelse
</div>
