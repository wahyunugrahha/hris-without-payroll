@props(['label'])
{{-- Menu aksi baris tabel (⋯). Isi slot dengan .dropdown-item. --}}
<div class="dropdown">
    <button type="button" class="row-menu-btn" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}'
        aria-expanded="false" aria-label="Aksi untuk {{ $label }}" title="Aksi">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="18" height="18" viewBox="0 0 24 24" stroke-width="2"
            stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
            <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
            <path d="M19 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
        </svg>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        {{ $slot }}
    </div>
</div>
