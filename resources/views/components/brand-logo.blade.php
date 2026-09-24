@props([
    'variant' => 'horizontal',
    'alt' => 'wndev',
])

@php
    $sizeMap = [
        'sidebar' => 'width:min(150px, 100%); max-width:min(150px, 100%);',
        'square' => 'width:auto; max-width:clamp(132px, 16vw, 178px);',
        'print-square' => 'width:auto; max-width:72px;',
        'icon' => 'width:auto; max-width:clamp(72px, 8vw, 96px);',
        'vertical' => 'width:auto; max-width:clamp(96px, 10vw, 148px);',
        'landscape' => 'width:auto; max-width:clamp(148px, 16vw, 220px);',
        'horizontal' => 'width:auto; max-width:clamp(160px, 18vw, 260px);',
        'compact' => 'width:auto; max-width:72px;',
    ];

    $sourceMap = [
        'sidebar' => asset_v('assets/img/logo-horizontal.png'),
        'square' => asset_v('assets/img/logo.png'),
        'print-square' => asset_v('assets/img/logo.png'),
        'icon' => asset_v('assets/img/logo.png'),
        'vertical' => asset_v('assets/img/logo.png'),
        'landscape' => asset_v('assets/img/logo.png'),
        'horizontal' => asset_v('assets/img/logo.png'),
        'compact' => asset_v('assets/img/logo.png'),
    ];

    $inlineStyle = ($sizeMap[$variant] ?? $sizeMap['horizontal']) . ' height:auto; display:block;';
    $source = $sourceMap[$variant] ?? $sourceMap['horizontal'];
@endphp

<img
    src="{{ $source }}"
    alt="{{ $alt }}"
    {{ $attributes->merge(['style' => $inlineStyle]) }}
/>