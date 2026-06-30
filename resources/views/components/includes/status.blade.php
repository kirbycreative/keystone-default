@if (session('status'))
    @php

        if (is_array(session('status'))) {
            $status = session('status');
        } else {
            $status = ['success' => session('status')];
        }
    @endphp
    <div class="alerts">

        @foreach ($status as $type => $txt)
            <div class="alert {{ $type }}">
                {!! $txt !!}
            </div>
        @endforeach
    </div>
@endif
