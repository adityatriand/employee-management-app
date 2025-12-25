<?php

namespace App\Observers;

use App\Models\Workspace;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class WorkspaceObserver
{
    /**
     * Handle the Workspace "created" event.
     *
     * @param  \App\Models\Workspace  $workspace
     * @return void
     */
    public function created(Workspace $workspace)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $workspace->id,
            'model_type' => get_class($workspace),
            'model_id' => $workspace->id,
            'action' => 'created',
            'new_values' => $workspace->getAttributes(),
            'description' => "Workspace '{$workspace->name}' telah dibuat",
        ]);
    }

    /**
     * Handle the Workspace "updated" event.
     *
     * @param  \App\Models\Workspace  $workspace
     * @return void
     */
    public function updated(Workspace $workspace)
    {
        $oldValues = [];
        $newValues = [];
        
        foreach ($workspace->getDirty() as $key => $value) {
            $oldValues[$key] = $workspace->getOriginal($key);
            $newValues[$key] = $value;
        }

        if (!empty($oldValues)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'workspace_id' => $workspace->id,
                'model_type' => get_class($workspace),
                'model_id' => $workspace->id,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => "Workspace '{$workspace->name}' telah diupdate",
            ]);
        }
    }

    /**
     * Handle the Workspace "deleted" event.
     *
     * @param  \App\Models\Workspace  $workspace
     * @return void
     */
    public function deleted(Workspace $workspace)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $workspace->id,
            'model_type' => get_class($workspace),
            'model_id' => $workspace->id,
            'action' => 'deleted',
            'old_values' => $workspace->getAttributes(),
            'description' => "Workspace '{$workspace->name}' telah dihapus",
        ]);
    }

    /**
     * Handle the Workspace "restored" event.
     *
     * @param  \App\Models\Workspace  $workspace
     * @return void
     */
    public function restored(Workspace $workspace)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'workspace_id' => $workspace->id,
            'model_type' => get_class($workspace),
            'model_id' => $workspace->id,
            'action' => 'restored',
            'new_values' => $workspace->getAttributes(),
            'description' => "Workspace '{$workspace->name}' telah dipulihkan",
        ]);
    }
}

