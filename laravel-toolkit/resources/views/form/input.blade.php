@php
    $type ??= 'text';
    $label ??= '';
    $value ??= '';
    $options ??= null;
    $details ??= [];
    $attributes ??= [];
    $placeholder ??= null;
    $checked ??= false;
    $id = $attributes['id'] ?? 'input--' . trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name), '-');
    $baseClass = $attributes['class'] ?? 'admin-field margin:top:0o5 w:100';
    $checkboxValue = $value !== '' && $value !== null ? $value : 1;
    $requiredAttr = !empty($required ?? false) ? ' required' : '';
    $validationAttr = !empty($validation ?? null) ? ' validation="' . e($validation) . '"' : '';
    $constraintAttr = $requiredAttr . $validationAttr;
    $placeholderAttr = !empty($placeholder ?? null) ? ' placeholder="' . e($placeholder) . '"' : '';
@endphp

@switch($type)
    @case('hidden')
        <input type="hidden" name="{{ $name }}" value="{{ $value }}" />
    @break;
    @case('image')
        <input-image name="{{ $name }}" label="{{ $label }}"{!! $constraintAttr !!}></input-image>
        <div>{{ $value }}</div>
    @break

    @case('file')
        <input-file name="{{ $name }}" label="{{ $label }}"{!! $constraintAttr !!}></input-file>
        <div>{{ $value }}</div>
    @break

    @case('ProjectMedia')
        <input-file name="{{ $name }}[]" label="{{ $label }}" multiple></input-file>
    @break

    @case('tags')
        @if (!empty($value))
            @foreach ($value as $olabel => $v)
                <input-checkbox name="{{ $name }}[]" label="{{ is_string($olabel) ? $olabel : $v }}"
                    value="{{ $v }}" checked></input-checkbox>
            @endforeach
        @endif
    @break

    @case('select')
        <input-select name="{{ $name }}" label="{{ $label }}"
            value="{{ $value }}"{!! $constraintAttr !!}>
            @foreach ($options ?? [] as $olabel => $ovalue)
                <select-option value="{{ $ovalue }}" label="{{ $olabel }}" @selected((string) $ovalue === (string) $value)>
                    {{ $olabel }}
                </select-option>
            @endforeach
        </input-select>
    @break

    @case('checkbox')
        @if (!empty($options))
            @php
                $i = -1;
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
            @endphp
            @foreach ($options as $label => $v)
                <?php $i++; ?>
                <input-checkbox name="{{ $name }}[]" label="{{ $label }}" value="{{ $v }}"
                    @checked(in_array($v, (array) $value))></input-checkbox>
            @endforeach
        @else
            <input-checkbox name="{{ $name }}" label="{{ $label }}" value="1"
                @checked((bool) $value)></input-checkbox>
        @endif
    @break

    @case('radio')
        @if (!empty($options))
            @php
                $i = -1;
                if (is_string($value)) {
                    $value = explode(',', $value);
                }
            @endphp
            @foreach ($options as $label => $v)
                <?php $i++; ?>
                <input-radio name="{{ $name }}" label="{{ $label }}" value="{{ $v }}"
                    @checked((string) $v === (string) $value)></input-radio>
            @endforeach
        @else
            <input-radio name="{{ $name }}" label="{{ $label }}" value="{{ $value }}"
                @checked((bool) $value)></input-radio>
        @endif
    @break

    @case('textarea')
        <input-textarea name="{{ $name }}" label="{{ $label }}"
            value="{{ $value }}"{!! $constraintAttr !!}></input-textarea>
    @break

    @case('number')
        <input-number name="{{ $name }}" label="{{ $label }}" value="{{ $value }}"
            step="{{ $details['step'] ?? 1 }}"></input-number>
    @break

    @default
        <?php if (is_array($value)) {
            dd([$type, $value, $name]);
        } ?>
        <input-text name="{{ $name }}" type="{{ $type }}" label="{{ $label }}"
            value="{{ $value }}"{!! $constraintAttr !!}{!! $placeholderAttr !!}></input-text>
    @break

@endswitch
