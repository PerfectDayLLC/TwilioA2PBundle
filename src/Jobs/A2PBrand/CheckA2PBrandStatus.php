<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand;

use Illuminate\Support\Str;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class CheckA2PBrandStatus extends AbstractMainJob
{
    public ?string $brandObjectSid;

    public ?string $a2PProfileBundleSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->brandObjectSid = ClientRegistrationHistory::getSid(
            'createA2PBrand',
            $this->client->getId(),
            false,
            'pending'
        );

        $this->a2PProfileBundleSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'submitA2PProfileBundle',
            $this->client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $brandInstance = RegistratorFacade::checkA2PBrandStatus(
            $this->client,
            $this->brandObjectSid,
            $this->a2PProfileBundleSid
        );

        if (Str::lower($brandInstance->status) === Status::BRAND_APPROVED) {
            ClientRegistrationHistory::whereRequestType('createA2PBrand')
                ->whereStatus(Status::BRAND_PENDING)
                ->whereObjectSid($this->brandObjectSid)
                ->update(['status' => Status::BRAND_APPROVED]);
        }
    }
}
