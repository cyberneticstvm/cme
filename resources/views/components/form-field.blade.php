@props(['name', 'label', 'type' => 'text', 'required' => false, 'hint' => null])
<div class="delegate-field">
    <label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@else<small>Optional</small>@endif</label>
    <input {{ $attributes->merge(['type' => $type, 'id' => $name, 'name' => $name, 'value' => is_scalar(old($name)) ? old($name) : '']) }}
        @required($required) @if($errors->has($name)) aria-invalid="true" @endif
        @if($hint || $errors->has($name)) aria-describedby="{{ $hint ? $name.'-hint ' : '' }}{{ $errors->has($name) ? $name.'-error' : '' }}" @endif>
    @if($hint)<p id="{{ $name }}-hint" class="delegate-hint">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="delegate-error">{{ $message }}</p>@enderror
</div>
