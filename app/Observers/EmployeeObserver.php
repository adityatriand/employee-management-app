<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

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
    }
}

