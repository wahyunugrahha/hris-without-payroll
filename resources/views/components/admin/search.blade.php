@props(['name', 'placeholder' => 'Cari…', 'label' => 'Cari'])
{{-- Kolom cari + tombol Cari untuk toolbar halaman daftar (.list-toolbar-row). --}}
<label class="search-field">
    <span class="visually-hidden">{{ $label }}</span>
    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="1.75"
        stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
        <path d="M21 21l-6 -6" />
    </svg>
    <input type="search" name="{{ $name }}" value="{{ request($name) }}" placeholder="{{ $placeholder }}"
        autocomplete="off" {{ $attributes }}>
</label>
<button type="submit" class="btn btn-primary list-search-btn">Cari</button>
