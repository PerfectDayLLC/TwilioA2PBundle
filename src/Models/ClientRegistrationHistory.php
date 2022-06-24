<?php

namespace PerfectDayLlc\TwilioA2PBundle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;

/**
 * @property int|string $id
 * @property int|string $entity_id
 * @property string|null $request_type
 * @property bool $error
 * @property string|null $bundle_sid
 * @property string|null $object_sid
 * @property string|null $status
 * @property array $response
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory|null $entity
 * @method static \Illuminate\Database\Eloquent\Builder|static allowedStatuses($types = [])
 * @method static \Illuminate\Database\Query\Builder|static onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @method static \Illuminate\Database\Eloquent\Builder|static whereBundleSid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereObjectSid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereRequestType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereResponse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|static whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|static withTrashed()
 * @method static \Illuminate\Database\Query\Builder|static withoutTrashed()
 */
class ClientRegistrationHistory extends Model
{
    use SoftDeletes;

    public const ALLOWED_STATUSES_TYPES = [
        Status::BUNDLES_PENDING_REVIEW,
        Status::BUNDLES_IN_REVIEW,
        Status::BUNDLES_TWILIO_APPROVED,
        Status::BRAND_PENDING,
        Status::BRAND_APPROVED,
        Status::EXCEPTION_ERROR,
        Status::EXECUTED,
    ];

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
        'error' => 'boolean',
        'response' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        if (self::isEntityModelUsingUuid()) {
            $this->keyType = 'string';
            $this->incrementing = false;
        }

        parent::__construct($attributes);
    }

    protected static function booted()
    {
        parent::booted();

        static::creating(function (self $model): void {
            // Automatically generate a UUID if using them, and not provided.
            if (self::isEntityModelUsingUuid() && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid();
            }
        });
    }

    private static function isEntityModelUsingUuid(): bool
    {
        /** @var class-string $entityModelString */
        $entityModelString = config('twilioa2pbundle.entity_model');

        /** @var Model $entityModel */
        $entityModel = new $entityModelString;

        return !in_array($entityModel->getKeyType(), ['int', 'integer']);
    }

    public function entity(): BelongsTo
    {
        /** @var string $entityModelString */
        $entityModelString = config('twilioa2pbundle.entity_model');

        /** @var Model $entityModel */
        $entityModel = new $entityModelString;

        return $this->belongsTo($entityModelString, 'entity_id', $entityModel->getKeyName());
    }

    public function scopeAllowedStatuses(Builder $query, array $types = []): Builder
    {
        return $query->whereIn('status', empty($types) ? self::ALLOWED_STATUSES_TYPES : $types);
    }

    /**
     * @param  string|int|null  $entityId
     */
    public static function getSidForAllowedStatuses(string $requestType, $entityId = null, bool $isBundle = true): ?string
    {
        /** @var static $self */
        $self = static::allowedStatuses()
            ->whereRequestType($requestType)
            ->when($entityId, fn (Builder $query) => $query->where('entity_id', $entityId))
            ->latest()
            ->first();

        return $self ? ($isBundle ? $self->bundle_sid : $self->object_sid) : null;
    }
}
