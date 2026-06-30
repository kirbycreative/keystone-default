<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\ContentAsset;
use Illuminate\View\View;

class DashboardController extends AdminController
{
    public function __invoke(): View
    {
        page()->setTitle('Admin Dashboard');

        return view('admin.dashboard', [
            'assetCount' => ContentAsset::count(),
            'latestAssets' => ContentAsset::latest()->take(5)->get(),
        ]);
    }
}
