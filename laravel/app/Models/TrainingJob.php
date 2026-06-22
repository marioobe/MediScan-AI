<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingJob extends Model
{
    protected $fillable = [
        'job_id', 'dataset_path', 'status', 'current_epoch', 'total_epoch',
        'progress_percent', 'accuracy_result', 'loss_result',
        'error_message', 'epoch_history', 'log', 'started_at', 'finished_at', 'created_by'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'epoch_history' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function aiModel(): HasOne
    {
        return $this->hasOne(AiModel::class, 'training_job_id');
    }
}
