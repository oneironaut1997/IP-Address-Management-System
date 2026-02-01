<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait HasUuid
 *
 * Provides UUID primary key support for Eloquent models.
 * Automatically generates a UUID v4 when creating a new model instance.
 *
 * @package App\Models\Concerns
 */
trait HasUuid
{
    /**
     * Boot the UUID trait for the model.
     *
     * Automatically generates a UUID v4 for the primary key
     * when a new model instance is being created.
     *
     * @return void
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the incrementing status of the model.
     *
     * UUIDs are not auto-incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the key type of the model.
     *
     * UUIDs are stored as strings.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
