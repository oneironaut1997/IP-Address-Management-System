<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class IPAddress
 *
 * Represents an IP address (IPv4 or IPv6) in the system.
 * Uses UUID primary keys and soft deletes for data recovery.
 *
 *
 * @property string $id
 * @property string $user_id
 * @property string $ip_address
 * @property string $label
 * @property string|null $comment
 * @property string $type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class IPAddress extends Model
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ip_addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'label',
        'comment',
        'type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the history records for this IP address.
     */
    public function history(): HasMany
    {
        return $this->hasMany(IPHistory::class, 'ip_address_id');
    }

    /**
     * Get the activity logs for this IP address.
     *
     * Uses Spatie Activity Log for comprehensive audit tracking.
     *
     * @return \Spatie\Activitylog\Models\Activity
     */
    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }
}
