<?php

declare(strict_types=1);

namespace Grazulex\LaravelSnapshot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $model_type
 * @property string $model_id
 * @property string $label
 * @property string $event_type
 * @property array $data
 * @property array $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ModelSnapshot extends Model
{
    protected $table = 'snapshots';

    protected $fillable = [
        'model_type',
        'model_id',
        'label',
        'event_type',
        'data',
        'metadata',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the owning snapshotable model.
     */
    public function snapshotable(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Get the model class name.
     */
    public function getModelTypeAttribute($value): string
    {
        return $value;
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeForEvent($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by label.
     */
    public function scopeWithLabel($query, string $label)
    {
        return $query->where('label', $label);
    }
}
