<?php

namespace App\Observers;

use App\Models\Position;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class PositionObserver
{
    /**
     * Handle the Position "created" event.
     *
     * @param  \App\Models\Position  $position
     * @return void
     */
    public function created(Position $position)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($position),
            'model_id' => $position->id,
            'action' => 'created',
            'new_values' => $position->getAttributes(),
            'description' => "Jabatan '{$position->name}' telah ditambahkan",
        ]);
    }

    /**
     * Handle the Position "updated" event.
     *
     * @param  \App\Models\Position  $position
     * @return void
     */
    public function updated(Position $position)
    {
        $oldValues = [];
        $newValues = [];
        
        foreach ($position->getDirty() as $key => $value) {
            $oldValues[$key] = $position->getOriginal($key);
            $newValues[$key] = $value;
        }

        if (!empty($oldValues)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'model_type' => get_class($position),
                'model_id' => $position->id,
                'action' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'description' => "Jabatan '{$position->name}' telah diupdate",
            ]);
        }
    }

    /**
     * Handle the Position "deleted" event.
     *
     * @param  \App\Models\Position  $position
     * @return void
     */
    public function deleted(Position $position)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($position),
            'model_id' => $position->id,
            'action' => 'deleted',
            'old_values' => $position->getAttributes(),
            'description' => "Jabatan '{$position->name}' telah dihapus",
        ]);
    }

    /**
     * Handle the Position "restored" event.
     *
     * @param  \App\Models\Position  $position
     * @return void
     */
    public function restored(Position $position)
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($position),
            'model_id' => $position->id,
            'action' => 'restored',
            'new_values' => $position->getAttributes(),
            'description' => "Jabatan '{$position->name}' telah dipulihkan",
        ]);
    }
}

