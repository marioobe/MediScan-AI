<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    protected $fillable = [
        'prediction_id', 'ai_model_id', 'image_path', 'original_name',
        'predicted_class', 'confidence', 'probabilities', 'model_label',
        'patient_name', 'patient_age', 'grad_cam_path',
    ];

    protected $casts = [
        'probabilities' => 'array',
    ];

    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path && file_exists(storage_path('app/public/' . $this->image_path))) {
            return asset('storage/' . $this->image_path);
        }
        return '';
    }
}
