@php
    $value ??= 'Submit';
    $attributes ??= [];
@endphp

<input-button type="submit" label="{{ $value }}"></input-button>
