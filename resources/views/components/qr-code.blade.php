@props(['data', 'size' => 250, 'dynamic' => true])

@if($dynamic)
    <img :src="'{{ route('admin.basic-settings.qr-code') }}?size={{ $size }}&data=' + encodeURIComponent({{ $data }})" 
         {{ $attributes->merge(['class' => 'object-contain']) }}
         alt="QR Code">
@else
    <img src="{{ route('qr-code.public', ['data' => $data, 'size' => $size]) }}"
         {{ $attributes->merge(['class' => 'object-contain']) }}
         alt="QR Code">
@endif
