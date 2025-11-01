<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;

class ActivityLogController extends Controller
{
    /**
     * Fetch activity logs, optional filter by table or target_id
     */
    public function index()
    {
        $logs = ActivityLogger::getAll(); // Ambil semua activity log

        return response()->json($logs);
    }
}
