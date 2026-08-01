<section id="{{ $settings['id'] ?? 'lead' }}" class="section lead-section">
    <div class="container">
        <div class="lead-copy">
            <p class="eyebrow">{{ $content['eyebrow'] }}</p>
            <h2>{{ $content['heading'] }}</h2>
            <p>{{ $content['body'] }}</p>
        </div>
        <div class="lead-form-panel">
            @if (session('status'))<div class="form-status" role="status">{{ session('status') }}</div>@endif
            {{ form($content['form_slug'], ['class' => 'lead-form']) }}
            <div class="contact-lines">
                <a href="mailto:{{ $content['contact_email'] }}">{{ $content['contact_email'] }}</a>
                <span>{{ $content['contact_note'] }}</span>
            </div>
        </div>
    </div>
</section>
