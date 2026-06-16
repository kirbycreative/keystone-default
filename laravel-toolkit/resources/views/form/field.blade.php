@props(['name', 'icon', 'status', 'options' => [], 'type' => 'text', 'label' => '', 'value' => ''])
<div class="field field--{{ $name }}">
    @isset($icon)
        <div class="icon">

        </div>
    @endisset
    <div class="input-wrapper">
        @include('toolkit::form.input')
        {{ $slot }}
    </div>
    @isset($status)
        <div class="status">

        </div>
    @endisset
    @if ($errors->has($name))
        <div class="errors">
            {{ $errors->first($name) }}
        </div>
    @endif
</div>
