<?php

namespace Keystone\Toolkit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Keystone\Toolkit\Exceptions\InadequateModelResponse;
use RuntimeException;

class OpenRouterService
{
    public const MAX_IMAGES_PER_REQUEST = 8;

    /** Single cache entry holding the strike ledger: [task][model] => ['count' => int, 'failures' => [...]]. */
    protected const STRIKE_KEY = 'openrouter:model_strikes';

    /** Most recent failure reasons retained per (task, model) for visibility. */
    protected const STRIKE_HISTORY_LIMIT = 10;

    public string $model;

    /** The model that produced the most recent successful response (for attribution / strikes). */
    public ?string $lastModel = null;

    public array $prompt = [];

    public string $directives;

    public string $responseType = 'json_object';

    public float $temperature = 0.2;

    protected string $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * @param  string|null  $model  The OpenRouter model to use for analysis
     * @param  string|null  $directives  The directives to use for analysis
     *                                   Example: 'You are a web design analysis assistant. Return valid JSON only.'
     */
    public function __construct(?string $model = null, ?string $directives = null)
    {
        $this->model = $model ?? config('openrouter.model');
        $this->directives = $directives ?? config('openrouter.directives', 'You are a helpful assistant.');
    }

    /**
     * @param array{
     *     prompt?: string|array<int, string|array<string, mixed>>,
     *     directives?: string,
     *     model?: string,
     *     task?: string,
     *     response_type?: string,
     *     temperature?: float,
     *     max_tokens?: int,
     *     reasoning?: array<string, mixed>
     * } $payload
     * @param  (callable(array|string): bool)|null  $adequate  Returns false when the model's output is
     *                                                          unusable for the task; that model takes a strike
     *                                                          and we fall through to the next candidate.
     */
    public function request(array $payload = [], ?callable $adequate = null): array|string
    {
        $prompt = $this->resolvePrompt($payload['prompt'] ?? null);
        $directives = $payload['directives'] ?? $this->directives;
        $responseType = $payload['response_type'] ?? $this->responseType;
        $temperature = $payload['temperature'] ?? $this->temperature;
        $maxTokens = $payload['max_tokens'] ?? null;
        $reasoning = $payload['reasoning'] ?? null;
        $task = $this->resolveTask($payload['task'] ?? null);

        if (! config('openrouter.key')) {
            throw new RuntimeException('OpenRouter API key is not configured.');
        }

        if (! $directives) {
            throw new RuntimeException('OpenRouter directives are not configured.');
        }

        if (! $prompt) {
            throw new RuntimeException('OpenRouter prompt is empty.');
        }

        $models = $this->modelChain($payload['model'] ?? null, $task);

        if ($models === []) {
            throw new RuntimeException('OpenRouter model is not configured.');
        }

        $this->lastModel = null;

        // Try each model in order (free first, then the configured paid model). A transient failure
        // falls through silently; an inadequate response strikes the model for this task before
        // moving on. The last error surfaces if every candidate fails.
        $lastException = null;

        foreach ($models as $model) {
            try {
                $result = $this->attempt($model, $directives, $prompt, $responseType, $temperature, $maxTokens, $reasoning);
            } catch (InadequateModelResponse $exception) {
                $this->recordStrike($task, $model, $exception->getMessage());
                $lastException = $exception;

                continue;
            } catch (RuntimeException $exception) {
                // Transient/infra failure: not the model's fault, so no strike.
                $lastException = $exception;

                continue;
            }

            if ($adequate !== null && ! $adequate($result)) {
                $reason = "Response rejected by adequacy check for task '{$task}'.";
                $this->recordStrike($task, $model, $reason);
                $lastException = new InadequateModelResponse($reason);

                continue;
            }

            $this->lastModel = $model;

            return $result;
        }

        throw $lastException ?? new RuntimeException('OpenRouter request failed: no model produced a response.');
    }

    /**
     * Ordered, de-duplicated list of models to try for a task. An explicit per-request model wins
     * outright (no fallback, and ignores rule-out — the caller forced it). Otherwise the free models
     * are tried first, then the env-configured model, with any models ruled out for this task removed.
     *
     * @return list<string>
     */
    protected function modelChain(?string $explicit, string $task): array
    {
        if (is_string($explicit) && $explicit !== '') {
            return [$explicit];
        }

        $chain = [];

        foreach ((array) config('openrouter.free_models', []) as $free) {
            if (is_string($free) && $free !== '') {
                $chain[] = $free;
            }
        }

        if (is_string($this->model) && $this->model !== '') {
            $chain[] = $this->model;
        }

        $chain = array_values(array_unique($chain));

        $eligible = array_values(array_filter($chain, fn (string $model) => ! $this->ruledOut($task, $model)));

        // If every candidate has been ruled out, degrade to the full chain rather than refuse
        // outright — a best-effort attempt beats a hard outage.
        return $eligible !== [] ? $eligible : $chain;
    }

    protected function resolveTask(?string $task): string
    {
        return is_string($task) && $task !== '' ? $task : 'default';
    }

    /**
     * The full strike ledger: [task][model] => ['count' => int, 'failures' => [['at' => iso, 'reason' => str]]].
     *
     * @return array<string, array<string, array{count: int, failures: list<array{at: string, reason: string}>}>>
     */
    public function strikes(): array
    {
        $ledger = Cache::get(self::STRIKE_KEY, []);

        return is_array($ledger) ? $ledger : [];
    }

    public function strikeCount(string $task, string $model): int
    {
        return (int) data_get($this->strikes(), [$this->resolveTask($task), $model, 'count'], 0);
    }

    /**
     * A model is ruled out for a task once its inadequate-response strikes reach the threshold.
     */
    public function ruledOut(string $task, string $model): bool
    {
        $threshold = (int) config('openrouter.rule_out_threshold', 3);

        return $threshold > 0 && $this->strikeCount($task, $model) >= $threshold;
    }

    /**
     * Record one inadequate-response strike against a model for a task and return the new count.
     */
    public function recordStrike(string $task, string $model, ?string $reason = null): int
    {
        $task = $this->resolveTask($task);
        $ledger = $this->strikes();

        $entry = $ledger[$task][$model] ?? ['count' => 0, 'failures' => []];
        $entry['count'] = ((int) ($entry['count'] ?? 0)) + 1;
        $entry['failures'][] = [
            'at' => now()->toIso8601String(),
            'reason' => $reason ?? 'inadequate response',
        ];
        $entry['failures'] = array_slice($entry['failures'], -self::STRIKE_HISTORY_LIMIT);

        $ledger[$task][$model] = $entry;
        Cache::forever(self::STRIKE_KEY, $ledger);

        return $entry['count'];
    }

    /**
     * Clear strikes for everything, a whole task, or a single (task, model) pairing.
     */
    public function clearStrikes(?string $task = null, ?string $model = null): void
    {
        if ($task === null) {
            Cache::forget(self::STRIKE_KEY);

            return;
        }

        $task = $this->resolveTask($task);
        $ledger = $this->strikes();

        if ($model === null) {
            unset($ledger[$task]);
        } else {
            unset($ledger[$task][$model]);

            if (($ledger[$task] ?? []) === []) {
                unset($ledger[$task]);
            }
        }

        Cache::forever(self::STRIKE_KEY, $ledger);
    }

    /**
     * Run a single request against one model and return its decoded content.
     */
    protected function attempt(
        string $model,
        string $directives,
        string|array $prompt,
        string $responseType,
        float $temperature,
        ?int $maxTokens,
        ?array $reasoning,
    ): array|string {
        $requestBody = $this->body($model, $directives, $prompt, $responseType, $temperature, $maxTokens, $reasoning);
        $response = retry(3, function () use ($requestBody) {
            $response = Http::withHeaders($this->headers())
                ->timeout(config('openrouter.timeout', 120))
                ->post($this->endpoint, $requestBody);

            if ($this->isTransientFailure($response)) {
                throw new RuntimeException('Transient OpenRouter failure: '.$this->responseDetails($response));
            }

            return $response;
        }, 2000);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenRouter request failed: '.$this->responseDetails($response)
            );
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! $content) {
            // Model-quality failure (returned nothing usable) — counts as a strike for the task.
            throw new InadequateModelResponse('OpenRouter returned an empty response: '.$this->responseDetails($response));
        }

        if ($responseType === 'json_object') {
            $json = json_decode($content, true);

            if (! is_array($json)) {
                throw new InadequateModelResponse('OpenRouter response was not valid JSON: '.$this->responseDetails($response));
            }

            return $json;
        }

        return $content;
    }

    protected function responseDetails(Response $response): string
    {
        $body = $response->json();
        $responseBody = preg_replace(
            '/\?X-Amz-[^\'"\s]+/i',
            '?[signed-query-redacted]',
            $response->body(),
        );
        $details = array_filter([
            'http_status' => $response->status(),
            'request_id' => $response->header('x-request-id'),
            'response_id' => data_get($body, 'id'),
            'model' => data_get($body, 'model'),
            'provider' => data_get($body, 'provider'),
            'finish_reason' => data_get($body, 'choices.0.finish_reason'),
            'native_finish_reason' => data_get($body, 'choices.0.native_finish_reason'),
            'error' => data_get($body, 'error'),
            'usage' => data_get($body, 'usage'),
            'body' => mb_strimwidth($responseBody, 0, 4000, '...'),
        ], fn (mixed $value) => $value !== null && $value !== '');

        return json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'unavailable response details';
    }

    protected function isTransientFailure(Response $response): bool
    {
        $embeddedErrorCode = (int) data_get($response->json(), 'error.code', 0);

        return $response->serverError() || $embeddedErrorCode >= 500;
    }

    public function beginPrompt(string $prompt): void
    {
        $this->prompt = [$prompt];
    }

    public function addPrompt(string $prompt): void
    {
        $this->prompt[] = $prompt;
    }

    public static function multimodalPrompt(string $prompt, array $images): array
    {
        $images = collect($images)->filter()->all();

        if (count($images) > self::MAX_IMAGES_PER_REQUEST) {
            throw new RuntimeException('OpenRouter requests support at most eight images.');
        }

        $labels = collect(array_keys($images))
            ->values()
            ->map(fn (string|int $label, int $index) => ($index + 1).'. '.$label)
            ->implode("\n");
        $content = [[
            'type' => 'text',
            'text' => $labels ? "{$prompt}\n\nImages in order:\n{$labels}" : $prompt,
        ]];

        foreach ($images as $url) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
        }

        return $content;
    }

    protected function resolvePrompt(string|array|null $prompt): string|array
    {
        if (is_array($prompt)) {
            if (array_is_list($prompt) && collect($prompt)->every(fn (mixed $part) => is_string($part))) {
                return trim(implode("\n", $prompt));
            }

            return $prompt;
        }

        return trim($prompt ?? implode("\n", $this->prompt));
    }

    protected function systemPrompt(string $directives, string $responseType): string
    {
        if ($responseType === 'json_object') {
            return rtrim($directives, " \t\n\r\0\x0B.;").'. Return valid JSON only.';
        }

        return $directives;
    }

    protected function headers(): array
    {
        return array_filter([
            'Authorization' => 'Bearer '.config('openrouter.key'),
            'HTTP-Referer' => config('openrouter.site_url'),
            'X-OpenRouter-Title' => config('openrouter.site_name'),
            'Content-Type' => 'application/json',
        ]);
    }

    protected function body(
        string $model,
        string $directives,
        string|array $prompt,
        string $responseType,
        float $temperature,
        ?int $maxTokens,
        ?array $reasoning,
    ): array {
        $body = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt($directives, $responseType),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $temperature,
        ];

        if ($responseType === 'json_object') {
            $body['response_format'] = [
                'type' => $responseType,
            ];
        }

        if ($maxTokens !== null) {
            $body['max_tokens'] = $maxTokens;
        }

        if ($reasoning !== null) {
            $body['reasoning'] = $reasoning;
        }

        return $body;
    }
}
