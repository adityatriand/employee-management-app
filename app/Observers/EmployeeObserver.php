<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     *
     * @param  \App\Models\Employee  $employee
     * @return void
     */
    public function created(Employee $employee)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $employee->workspace_id,
            'model_type' => get_class($employee),
            'model_id' => $employee->id,
            'action' => 'created',
            'new_values' => $employee->getAttributes(),
            'description' => "Pegawai '{$employee->name}' telah ditambahkan",
        ]);

        // Clear dashboard and chart caches for this workspace
        Cache::forget("dashboard_stats_{$employee->workspace_id}");
        Cache::forget("avg_age_{$employee->workspace_id}");
        Cache::forget("chart_data_{$employee->workspace_id}");
    }

    /**
     * Handle the Employee "updated" event.
     *
     * @param  \App\Models\Employee  $employee
     * @return void
     */
    public function updated(Employee $employee)
    {
        $oldValues = [];
        $newValues = [];
        
        foreach ($employee->getDirty() as $key => $value) {
            $oldValues[$key] = $employee->getOriginal($key);
            $newValues[$key] = $value;
        }

        if (!empty($oldValues)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'workspace_id' => $employee->workspace_id,
                'model_type' => get_class($employee),
                'model_id' => $employee->id,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => "Pegawai '{$employee->name}' telah diupdate",
            ]);

            // Clear dashboard and chart caches if relevant fields changed
            $relevantFields = ['gender', 'birth_date', 'position_id'];
            if (array_intersect_key(array_flip($relevantFields), $oldValues)) {
                Cache::forget("dashboard_stats_{$employee->workspace_id}");
                Cache::forget("avg_age_{$employee->workspace_id}");
                Cache::forget("chart_data_{$employee->workspace_id}");
            }
        }
    }

    /**
     * Handle the Employee "deleted" event.
     *
     * @param  \App\Models\Employee  $employee
     * @return void
     */
    public function deleted(Employee $employee)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $employee->workspace_id,
            'model_type' => get_class($employee),
            'model_id' => $employee->id,
            'action' => 'deleted',
            'old_values' => $employee->getAttributes(),
            'description' => "Pegawai '{$employee->name}' telah dihapus",
        ]);

        // Clear dashboard and chart caches for this workspace
        Cache::forget("dashboard_stats_{$employee->workspace_id}");
        Cache::forget("avg_age_{$employee->workspace_id}");
        Cache::forget("chart_data_{$employee->workspace_id}");
    }

    /**
     * Handle the Employee "restored" event.
     *
     * @param  \App\Models\Employee  $employee
     * @return void
     */
    public function restored(Employee $employee)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $employee->workspace_id,
            'model_type' => get_class($employee),
            'model_id' => $employee->id,
            'action' => 'restored',
            'new_values' => $employee->getAttributes(),
            'description' => "Pegawai '{$employee->name}' telah dipulihkan",
        ]);

        // Clear dashboard and chart caches for this workspace
        Cache::forget("dashboard_stats_{$employee->workspace_id}");
        Cache::forget("avg_age_{$employee->workspace_id}");
        Cache::forget("chart_data_{$employee->workspace_id}");
    }
}

