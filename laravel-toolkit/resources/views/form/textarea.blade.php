@props(['label' => '', 'value' => '', 'name'])

<label for="textarea--{{ $name }}">{{ $label }}</label>
<input-textarea
    name="{{ $name }}"
    value="{{ $value }}"
    {{ $attributes }}
></input-textarea>
