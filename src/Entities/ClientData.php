<?php

namespace PerfectDayLlc\TwilioA2PBundle\Entities;

use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;

class ClientData
{
    /**
     * @var string|int
     */
    private $id;

    private string $companyName;

    private string $address;

    private string $city;

    private string $state;

    private string $zip;

    private string $country;

    private string $phone;

    private string $phoneSid;

    private string $website;

    private string $contactFirstname;

    private string $contactLastname;

    private string $contactEmail;

    private string $contactPhone;

    private string $webhookUrl;

    private string $fallbackWebhookUrl;

    private ClientOwnerData $clientOwnerData;

    private ?ClientRegistrationHistory $clientRegistrationHistoryModel;

    /**
     * @param  string|int  $id
     */
    public function __construct(
        $id,
        string $companyName,
        string $address,
        string $city,
        string $state,
        string $zip,
        string $country,
        string $phone,
        string $phoneSid,
        string $website,
        string $contactFirstname,
        string $contactLastname,
        string $contactEmail,
        string $contactPhone,
        string $webhookUrl,
        string $fallbackWebhookUrl,
        ClientOwnerData $clientOwnerData,
        ?ClientRegistrationHistory $clientRegistrationHistoryModel
    ) {
        $this->id = $id;
        $this->companyName = $companyName;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
        $this->country = $country;
        $this->phone = $phone;
        $this->phoneSid = $phoneSid;
        $this->website = $website;
        $this->contactFirstname = $contactFirstname;
        $this->contactLastname = $contactLastname;
        $this->contactEmail = $contactEmail;
        $this->contactPhone = $contactPhone;
        $this->webhookUrl = $webhookUrl;
        $this->fallbackWebhookUrl = $fallbackWebhookUrl;
        $this->clientOwnerData = $clientOwnerData;
        $this->clientRegistrationHistoryModel = $clientRegistrationHistoryModel;
    }

    /**
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getPhoneSid(): string
    {
        return $this->phoneSid;
    }

    public function getWebsite(): string
    {
        return $this->website;
    }

    public function getContactFirstname(): string
    {
        return $this->contactFirstname;
    }

    public function getContactLastname(): string
    {
        return $this->contactLastname;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function getContactPhone(): string
    {
        return $this->contactPhone;
    }

    public function getWebhookUrl(): string
    {
        return $this->webhookUrl;
    }

    public function getFallbackWebhookUrl(): string
    {
        return $this->fallbackWebhookUrl;
    }

    public function getClientOwnerData(): ClientOwnerData
    {
        return $this->clientOwnerData;
    }

    public function getClientRegistrationHistoryModel(): ?ClientRegistrationHistory
    {
        return $this->clientRegistrationHistoryModel;
    }
}
