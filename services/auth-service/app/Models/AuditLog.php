<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AuditLog
 *
 * Immutable audit trail for user authentication events.
 * Tracks login/logout activities for compliance and security analysis.
 *
 * @package App\Models
 * @property string $id
 * @property string|null $user_id
 * @property string $event_type
 * @property string $entity_type
 * @property string|null $entity_id
 * @property array|null $metadata
 * @property string|null $session_id
 * @property \Carbon\Carbon $created_at
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory, HasUuid;

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at for audit logs.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'metadata',
        'session_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user associated with this audit log entry.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'email' => 'system',
        ]);
    }

    /**
     * Boot the model.
     *
     * Ensures audit logs are immutable by preventing updates.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Prevent updates to audit logs - they are immutable
        static::updating(function () {
            return false;
        });

        // Prevent deletion of audit logs - they are permanent
        static::deleting(function () {
            return false;
        });
    }
}
