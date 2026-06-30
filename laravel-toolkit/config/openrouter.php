<?php

return [
    'key' => env('OPENROUTER_API_KEY'),
    // Free models tried in order before falling back to the paid 'model'. Override with a
    // comma-separated OPENROUTER_FREE_MODELS, or set it empty to skip the free tier entirely.
    'free_models' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'OPENROUTER_FREE_MODELS',
            'nvidia/nemotron-3-ultra-550b-a55b:free,nvidia/nemotron-3-super-120b-a12b:free,openai/gpt-oss-120b:free',
        )),
    ))),
    'model' => env('OPENROUTER_MODEL', 'openai/gpt-4o-mini'),
    // A model that returns inadequate output for a task this many times is ruled out of that
    // task's rotation. Transient/infra failures do not count toward this.
    'rule_out_threshold' => (int) env('OPENROUTER_RULE_OUT_THRESHOLD', 3),
    'directives' => env('OPENROUTER_DIRECTIVES', 'You are a helpful assistant.'),
    'site_url' => env('OPENROUTER_SITE_URL'),
    'site_name' => env('OPENROUTER_SITE_NAME', config('app.name')),
    'timeout' => env('OPENROUTER_TIMEOUT', 600),
];
