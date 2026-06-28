<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use App\Models\Upload;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'total_performances' => Performance::count(),
            'pending'            => Performance::pending()->count(),
            'approved'           => Performance::approved()->count(),
            'rejected'           => Performance::rejected()->count(),
            'recent_uploads'     => Upload::with('branch')
                ->orderByDesc('created_at')
                ->take(5)
                ->get(),
        ]);
    }
}
