<?php

namespace PerfectDayLlc\TwilioA2PBundle\Entities;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ClientRegistrationHistoryResponseData
{
    /**
     * @return string|int
     */
    private $entityId;

    private ?string $bundleSid;

    private bool $error;

    private ?string $objectSid;

    private ?string $requestType;

    private ?array $response;

    private ?string $status;

    /**
     * @param  string|int  $entityId
     */
    public function __construct(
        $entityId = null,
        ?string $requestType = null,
        bool $error = false,
        ?string $bundleSid = null,
        ?string $objectSid = null,
        ?string $status = null,
        ?array $response = null
    ) {
        $this->entityId = $entityId;
        $this->requestType = $requestType;
        $this->error = $error;
        $this->bundleSid = $bundleSid;
        $this->objectSid = $objectSid;
        $this->status = Str::lower($status);
        $this->response = $response;
    }

    public static function createFromArray(array $historyData): self
    {
        return new self(
            Arr::get($historyData, 'entity_id'),
            Arr::get($historyData, 'request_type'),
            $historyData['error'] ?? false,
            Arr::get($historyData, 'bundle_sid'),
            Arr::get($historyData, 'object_sid'),
            Arr::get($historyData, 'status'),
            Arr::get($historyData, 'response')
        );
    }

    public function getBundleSid(): ?string
    {
        return $this->bundleSid;
    }

    /**
     * @return string|int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    public function getError(): bool
    {
        return $this->error;
    }

    public function getObjectSid(): string
    {
        return $this->objectSid;
    }

    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
