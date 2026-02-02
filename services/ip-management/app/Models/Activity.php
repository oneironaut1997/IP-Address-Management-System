<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Class Activity
 *
 * Custom Activity model for Spatie Activity Log with UUID support.
 *
 * @package App\Models
 */
class Activity extends SpatieActivity
{
    use HasUuid;

    protected $table = 'activity_logs';

    protected $fillable = [
        'log_name',
        'description',
        'subject_id',
        'subject_type',
        'causer_id',
        'causer_type',
        'properties',
        'event',
        'batch_uuid',
    ];
}
