<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientOwnerData;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;

/**
 * @property string $id
 * @property string|null $company_name
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property string|null $phone_number
 * @property string|null $twilio_phone_number_sid
 * @property string|null $website
 * @property string|null $contact_first_name
 * @property string|null $contact_last_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $webhook_url
 * @property string|null $fallback_webhook_url
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Entity extends Model implements ClientRegistrationHistoryContract
{
    public static $customQuery = null;

    public static function customTwilioA2PFiltering(Builder $query): Builder
    {
        return self::$customQuery ? (self::$customQuery)($query) : $query;
    }

    public function twilioA2PClientRegistrationHistories(): HasMany
    {
        return $this->hasMany(ClientRegistrationHistory::class);
    }

    public function getClientData(): ClientData
    {
        $clientOwnerData = new ClientOwnerData(
            $this->contact_first_name,
            $this->contact_last_name,
            $this->contact_email
        );

        return new ClientData(
            $this->id,
            $this->company_name,
            $this->address,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
            $this->phone_number,
            $this->twilio_phone_number_sid,
            $this->website,
            $clientOwnerData->getFirstname(),
            $clientOwnerData->getLastname(),
            $clientOwnerData->getEmail(),
            $this->contact_phone,
            $this->webhook_url,
            $this->fallback_webhook_url,
            $clientOwnerData
        );
    }
}
