<?php

namespace PerfectDayLlc\TwilioA2PBundle\Entities;

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

    /**
     * @return string|int
     */
    private $userId;

    private ClientOwnerData $clientOwnerData;

    /**
     * @return string|int|null
     */
    private $registrationHistoryId;

    private ?string $customerRegistrationStatus;

    /**
     * @param  string|int  $id
     * @param  string|int  $userId
     * @param  string|int|null  $registrationHistoryId
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
        $userId,
        ClientOwnerData $clientOwnerData,
        $registrationHistoryId,
        ?string $customerRegistrationStatus
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
        $this->userId = $userId;
        $this->clientOwnerData = $clientOwnerData;
        $this->registrationHistoryId = $registrationHistoryId;
        $this->customerRegistrationStatus = $customerRegistrationStatus;
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

    /**
     * @return string|int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    public function getClientOwnerData(): ClientOwnerData
    {
        return $this->clientOwnerData;
    }

    /**
     * @return string|int|null
     */
    public function getRegistrationHistoryId()
    {
        return $this->registrationHistoryId;
    }

    public function getCustomerRegistrationHistoryStatus(): ?string
    {
        return $this->customerRegistrationStatus;
    }
}
