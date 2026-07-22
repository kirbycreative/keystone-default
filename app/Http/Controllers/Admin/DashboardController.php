<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ContentAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Keystone\Toolkit\Services\KeystoneApiService;
use Throwable;

class DashboardController extends AdminController
{
    public function __invoke(Request $request): View
    {
        page()->setTitle('Admin Dashboard');

        return view('admin.dashboard', [
            'onboarding' => $request->user()->onboardingState(),
            'assetCount' => ContentAsset::count(),
            'latestAssets' => ContentAsset::latest()->take(5)->get(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $onboarding = $request->user()->onboardingState();

        if (! $onboarding->generation_remote_id) {
            return response()->json(['message' => 'Generation has not been submitted.'], 404);
        }

        try {
            $response = app(KeystoneApiService::class)
                ->onboardingCompletion($onboarding->generation_remote_id);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Build status is temporarily unavailable.'], 502);
        }

        $submission = data_get($response, 'submission');

        if (! is_array($submission)) {
            return response()->json(['message' => 'Kirby Creative returned an invalid build status.'], 502);
        }

        $onboarding->update([
            'generation_status' => $submission['status'] ?? $onboarding->generation_status,
            'generation_stage' => $submission['stage'] ?? $onboarding->generation_stage,
            'generation_error' => data_get($submission, 'error.message'),
            'generation_result' => $submission['result'] ?? $onboarding->generation_result,
        ]);

        return response()->json(['submission' => $submission]);
    }
}
