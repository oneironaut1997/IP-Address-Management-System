<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class IPHistory
 *
 * Tracks changes to IP addresses over time.
 * Stores old and new values as JSON for complete audit trail.
 *
 * @package App\Models
 *
 * @property string $id
 * @property string $ip_address_id
 * @property string $modified_by
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string $action
 * @property \Carbon\Carbon $created_at
 */
class IPHistory extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ip_history';

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at for history records.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address_id',
        'modified_by',
        'old_values',
        'new_values',
        'action',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the IP address that this history record belongs to.
     *
     * @return BelongsTo
     */
    public function ipAddress(): BelongsTo
    {
        return $this->belongsTo(IPAddress::class, 'ip_address_id');
    }
}
