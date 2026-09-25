<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('user')->orderBy('id', 'desc')->paginate(50);
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
