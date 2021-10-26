<?php

namespace PerfectDayLlc\TwilioA2PBundle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;

class ClientRegistrationHistory extends Model
{
    use SoftDeletes;

    protected $table = 'twilio_a2p_registration_history';

    protected $fillable = [
        'entity_id',
        'request_type',
        'bundle_sid',
        'object_sid',
        'status',
        'response',
        'error',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function scopeAllowedStatuses(Builder $query, array $types = []): Builder
    {
        if (empty($types)) {
            $types = [
                Status::BUNDLES_PENDING_REVIEW,
                Status::BUNDLES_IN_REVIEW,
                Status::BUNDLES_TWILIO_APPROVED,
                Status::BRAND_PENDING,
                Status::BRAND_APPROVED,
                Status::EXCEPTION_ERROR,
            ];
        }

        return $query->whereIn('status', $types);
    }

    /**
     * @param  string|int|null  $entityId
     */
    public static function getBundleSidForAllowedStatuses(string $requestType, $entityId = null): string
    {
        return optional(self::allowedStatuses()
            ->whereRequestType($requestType)
            ->when($entityId, fn (Builder $query) => $query->where('entity_id', $entityId))
            ->latest()
            ->first())
            ->bundle_sid
        ?? '';
    }
}
