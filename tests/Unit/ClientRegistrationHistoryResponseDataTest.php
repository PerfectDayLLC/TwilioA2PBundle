<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use Illuminate\Support\Str;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientRegistrationHistoryResponseData;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class ClientRegistrationHistoryResponseDataTest extends TestCase
{
    /**
     * @testWith [false]
     *           [true]
     */
    public function test_getters(bool $error): void
    {
        /** @var ClientRegistrationHistory $fakeData */
        $fakeData = factory(ClientRegistrationHistory::class)->make();

        $clientRegistrationHistoryResponseData = new ClientRegistrationHistoryResponseData(
            $id = ($this->faker()->randomDigit() + 1),
            $fakeData->request_type,
            $error,
            $fakeData->bundle_sid,
            $fakeData->object_sid,
            $fakeData->status,
            $response = [
                'id' => 123,
                'index1' => 'value456',
            ]
        );

        $this->assertSame($id, $clientRegistrationHistoryResponseData->getEntityId());
        $this->assertSame($fakeData->request_type, $clientRegistrationHistoryResponseData->getRequestType());
        $this->assertSame($error, $clientRegistrationHistoryResponseData->getError());
        $this->assertSame($fakeData->bundle_sid, $clientRegistrationHistoryResponseData->getBundleSid());
        $this->assertSame($fakeData->object_sid, $clientRegistrationHistoryResponseData->getObjectSid());
        $this->assertSame($fakeData->status, $clientRegistrationHistoryResponseData->getStatus());
        $this->assertSame($response, $clientRegistrationHistoryResponseData->getResponse());
    }

    /**
     * @depends test_getters
     * @dataProvider arrayDataSetterProvider
     */
    public function test_setters(string $field, $value, string $getter): void
    {
        $clientRegistrationHistoryResponseData = ClientRegistrationHistoryResponseData::createFromArray([
            $field => $value,
        ]);

        $this->assertSame(
            $value,
            $clientRegistrationHistoryResponseData->$getter(),
            "The getter '$getter' is not returning what is expected for field '$field'"
        );
    }

    public function test_status_is_case_insensitive(): void
    {
        $clientRegistrationHistoryResponseData = ClientRegistrationHistoryResponseData::createFromArray([
            'status' => $status = 'SomE_StaTUs-WORKiNg',
        ]);

        $this->assertSame(
            Str::lower($status),
            $clientRegistrationHistoryResponseData->getStatus(),
            'The status is not being lowercase.'
        );
    }

    public function arrayDataSetterProvider(): array
    {
        return [
            'Entity ID' => ['entity_id', 123, 'getEntityId'],
            'Request Type' => ['request_type', 'request_type-678', 'getRequestType'],
            'Has Error' => ['error', true, 'getError'],
            'Has no Error' => ['error', false, 'getError'],
            'Bundle SID' => ['bundle_sid', 'BUAP7DPUY09QMF9ADKH', 'getBundleSid'],
            'Object SID' => ['object_sid', 'BU102390023DW0ADW0D', 'getObjectSid'],
            'Status' => ['status', 'nice status', 'getStatus'],
            'Response' => ['response', ['id' => 123, 'index1' => 'value456'], 'getResponse'],
        ];
    }
}
