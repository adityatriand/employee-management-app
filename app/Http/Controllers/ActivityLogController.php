<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkLevel')->except(['show']);
    }

    /**
     * Display a listing of the activity logs.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by model type
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $activityLogs = $query->paginate(20)->appends($request->query());

        // Get filter options
        $users = User::orderBy('name', 'asc')->get();
        $modelTypes = ActivityLog::select('model_type')
            ->distinct()
            ->orderBy('model_type', 'asc')
            ->pluck('model_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => class_basename($type),
                ];
            });

        $actions = ['created', 'updated', 'deleted', 'restored'];

        // Calculate filter status
        $hasFilters = $request->filled('user_id') ||
                     $request->filled('model_type') ||
                     $request->filled('action') ||
                     $request->filled('date_from') ||
                     $request->filled('date_to');

        return view('activity-logs.index', compact(
            'activityLogs',
            'users',
            'modelTypes',
            'actions',
            'hasFilters'
        ));
    }

    /**
     * Display the specified activity log.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $activityLog = ActivityLog::with('user')->findOrFail($id);
        
        return view('activity-logs.show', compact('activityLog'));
    }
}

