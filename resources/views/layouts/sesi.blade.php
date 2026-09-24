{{-- Jaga token CSRF tetap segar (lihat public/assets/js/sesi.js). --}}
<script src="{{ asset_v('assets/js/sesi.js') }}" data-token="{{ csrf_token() }}" data-url="{{ route('csrf.token') }}"></script>
