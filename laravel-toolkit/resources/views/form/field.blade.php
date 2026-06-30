@php
    $options ??= [];
    $type ??= 'text';
    $label ??= '';
    $value ??= '';
    $help ??= null;
    $wrapper_class ??= null;
    $errorName = str_replace('[]', '', $name);
@endphp
<div class="{{ $wrapper_class ?? 'field field--' . str_replace(['[', ']'], '-', $name) }}">
    @isset($icon)
        <div class="icon">

        </div>
    @endisset
    <div class="input-wrapper">
        @include('toolkit::form.input')
        @if ($help)
            <p class="muted font-size:0o8 margin:top:0o5">{{ $help }}</p>
        @endif
    </div>
    @isset($status)
        <div class="status">

        </div>
    @endisset
    @if ($errors->has($errorName))
        <div class="text:red font-size:0o9 margin:top:0o5">
            {{ $errors->first($errorName) }}
        </div>
    @endif
</div>
