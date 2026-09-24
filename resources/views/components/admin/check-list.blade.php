@props(['name', 'items', 'kode', 'label', 'selected' => [], 'judul' => 'Semua', 'itemClass' => ''])
{{-- Daftar centang dengan "pilih semua" + penghitung (perilaku di admin.js). --}}
<div {{ $attributes->merge(['class' => 'check-list']) }} data-check-list>
    <div class="check-list-head">
        <label class="form-check m-0">
            <input class="form-check-input" type="checkbox" data-check-all>
            <span class="form-check-label fw-medium">{{ $judul }}</span>
        </label>
        <span class="check-list-count" data-check-count>0 dipilih</span>
    </div>
    <div class="check-list-body">
        @forelse ($items as $item)
            <label class="form-check">
                <input type="checkbox" class="form-check-input {{ $itemClass }}" data-check-item name="{{ $name }}[]"
                    value="{{ $item->{$kode} }}" @checked(in_array($item->{$kode}, (array) $selected))>
                <span class="form-check-label text-truncate" title="{{ $item->{$label} }}">{{ $item->{$label} }}</span>
            </label>
        @empty
            <span class="text-secondary small">Tidak ada pilihan.</span>
        @endforelse
    </div>
    @error($name)
        <div class="text-danger small px-3 pb-2">{{ $message }}</div>
    @enderror
</div>
