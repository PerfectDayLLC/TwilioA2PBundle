<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientOwnerData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientRegistrationHistoryResponseData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class EntitiesTest extends TestCase
{
    public function test_client_data(): void
    {
        /** @var ClientRegistrationHistory $clientRegistrationHistory */
        $clientRegistrationHistory = ClientRegistrationHistory::factory()->create();

        $clientData = new ClientData(
            $id = ($this->faker()->randomDigit() + 1),
            $companyName = 'John Doe Dealer',
            $address = '1234 Main St',
            $city = 'Orlando',
            $state = 'FL',
            $zipcode = '32827',
            $country = 'USA',
            $phone = '+12345678901',
            $phoneSid = 'PNXXXXXXXXXXXXXXXXXXXX',
            $website = 'https://johndoedealer.unitifi.com',
            $contactName = 'John',
            $contactSurname = 'Doe',
            $contactEmail = 'johndoe@gmail.com',
            $contactPhone = '+12233445566',
            $id,
            $clientOwnerData = new ClientOwnerData(
                $contactName,
                $contactSurname,
                $contactEmail
            ),
            $clientRegistrationHistory->id,
            $clientRegistrationHistory->status
        );

        $this->assertSame($id, $clientData->getId());
        $this->assertSame($companyName, $clientData->getCompanyName());
        $this->assertSame($address, $clientData->getAddress());
        $this->assertSame($city, $clientData->getCity());
        $this->assertSame($state, $clientData->getState());
        $this->assertSame($zipcode, $clientData->getZip());
        $this->assertSame($country, $clientData->getCountry());
        $this->assertSame($phone, $clientData->getPhone());
        $this->assertSame($phoneSid, $clientData->getPhoneSid());
        $this->assertSame($website, $clientData->getWebsite());
        $this->assertSame($contactName, $clientData->getContactFirstname());
        $this->assertSame($contactSurname, $clientData->getContactLastname());
        $this->assertSame($contactEmail, $clientData->getContactEmail());
        $this->assertSame($contactPhone, $clientData->getContactPhone());
        $this->assertSame($id, $clientData->getUserId());

        $this->assertSame($clientOwnerData->getFirstname(), $contactName);
        $this->assertSame($clientOwnerData->getLastname(), $contactSurname);
        $this->assertSame($clientOwnerData->getEmail(), $contactEmail);

        $this->assertSame($clientRegistrationHistory->id, $clientData->getRegistrationHistoryId());
        $this->assertSame($clientRegistrationHistory->status, $clientData->getCustomerRegistrationHistoryStatus());
    }

    /**
     * @testWith [false]
     *           [true]
     */
    public function test_client_registration_history_response_data_getters(bool $error): void
    {
        /** @var ClientRegistrationHistory $fakeData */
        $fakeData = ClientRegistrationHistory::factory()->make();

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
     * @depends test_client_registration_history_response_data_getters
     * @dataProvider arrayDataSetterProvider
     */
    public function test_client_registration_history_response_data_setters(string $field, $value, string $getter): void
    {
        $clientRegistrationHistoryResponseData = ClientRegistrationHistoryResponseData::createFromArray([
            $field => $value
        ]);

        $this->assertSame(
            $value,
            $clientRegistrationHistoryResponseData->$getter(),
            "The getter '$getter' is not returning what is expected for field '$field'"
        );
    }

    public function test_statuses_constants_are_the_expected_ones(): void
    {
        $this->assertEquals('compliant', Status::BUNDLES_COMPLIANT);
        $this->assertEquals('noncompliant', Status::BUNDLES_NONCOMPLIANT);
        $this->assertEquals('draft', Status::BUNDLES_DRAFT);
        $this->assertEquals('pending-review', Status::BUNDLES_PENDING_REVIEW);
        $this->assertEquals('in-review', Status::BUNDLES_IN_REVIEW);
        $this->assertEquals('twilio-rejected', Status::BUNDLES_TWILIO_REJECTED);
        $this->assertEquals('twilio-approved', Status::BUNDLES_TWILIO_APPROVED);

        $this->assertEquals('pending', Status::BRAND_PENDING);
        $this->assertEquals('approved', Status::BRAND_APPROVED);
        $this->assertEquals('failed', Status::BRAND_FAILED);
        $this->assertEquals('in_progress', Status::BRAND_IN_PROGRESS);

        $this->assertEquals('exception-error', Status::EXCEPTION_ERROR);
        $this->assertEquals('executed', Status::EXECUTED);
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
