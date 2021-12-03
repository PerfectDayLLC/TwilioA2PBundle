<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientOwnerData;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class ClientDataTest extends TestCase
{
    /**
     * @testWith [true]
     *           [false]
     */
    public function test_correct_data_is_returned(bool $withClientRegistrationHistoryModel): void
    {
        $clientRegistrationHistory = $withClientRegistrationHistoryModel
            ? factory(ClientRegistrationHistory::class)->create()
            : null;

        $clientData = new ClientData(
            $id = ($this->faker()->randomDigit() + 1),
            $companyName = 'John Doe Dealer',
            $address = '1234 Main St',
            $city = 'Orlando',
            $state = 'FL',
            $zipcode = '32827',
            $country = 'US',
            $phone = '+12345678901',
            $phoneSid = 'PNXXXXXXXXXXXXXXXXXXXX',
            $website = 'https://johndoedealer.unitifi.com',
            $contactName = 'John',
            $contactSurname = 'Doe',
            $contactEmail = 'johndoe@gmail.com',
            $contactPhone = '+12233445566',
            $webhookUrl = 'https://webhook.url/123/abc',
            $fallbackWebhookUrl = 'https://fallbackwebhook.url/abc/123',
            $clientOwnerData = new ClientOwnerData(
                $contactName,
                $contactSurname,
                $contactEmail
            ),
            $clientRegistrationHistory,
        );

        $this->assertSame($id, $clientData->getId());
        $this->assertSame($companyName, $clientData->getCompanyName());
        $this->assertSame($address, $clientData->getAddress());
        $this->assertSame($city, $clientData->getCity());
        $this->assertSame($state, $clientData->getRegion());
        $this->assertSame($zipcode, $clientData->getZip());
        $this->assertSame($country, $clientData->getIsoCountry());
        $this->assertSame($phone, $clientData->getPhone());
        $this->assertSame($phoneSid, $clientData->getPhoneSid());
        $this->assertSame($website, $clientData->getWebsite());
        $this->assertSame($contactName, $clientData->getContactFirstname());
        $this->assertSame($contactSurname, $clientData->getContactLastname());
        $this->assertSame($contactEmail, $clientData->getContactEmail());
        $this->assertSame($contactPhone, $clientData->getContactPhone());

        $this->assertSame($webhookUrl, $clientData->getWebhookUrl());
        $this->assertSame($fallbackWebhookUrl, $clientData->getFallbackWebhookUrl());

        $this->assertSame($clientOwnerData, $clientData->getClientOwnerData());

        $this->assertSame($clientRegistrationHistory, $clientData->getClientRegistrationHistoryModel());
    }

    /**
     * @depends test_correct_data_is_returned
     * @dataProvider sanitizedContactPhoneNumbersProvider
     */
    public function test_contact_phone_is_sanitized(string $contactPhone, string $sanitizedContactPhone): void
    {
        $clientData = new ClientData(
            $this->faker()->randomDigit() + 1,
            $this->faker()->company(),
            $this->faker()->streetAddress(),
            $this->faker()->city(),
            $this->faker()->stateAbbr(),
            $this->faker()->postcode(),
            $this->faker()->countryCode(),
            $this->faker()->e164PhoneNumber(),
            $this->faker()->numerify('PN####################'),
            $this->faker()->url(),
            $contactFirstName = $this->faker()->firstName(),
            $contactLastName = $this->faker()->lastName(),
            $contactEmail = $this->faker()->companyEmail(),
            $contactPhone,
            $this->faker()->url().'/abc/123',
            $this->faker()->url().'/abc/456',
            new ClientOwnerData(
                $contactFirstName,
                $contactLastName,
                $contactEmail
            ),
            null,
        );

        $this->assertSame(
            $sanitizedContactPhone,
            $clientData->getContactPhone(),
            'The sanitized contact phone number does not match.'
        );
    }

    public function sanitizedContactPhoneNumbersProvider(): array
    {
        return [
            'International number with some spaces' => ['+1 333888 5555', '+13338885555'],
            'International number with spaces' => ['+1 333 888 5555', '+13338885555'],
            'International number with dashes' => ['+1-333-888-5555', '+13338885555'],
            'International number with spaces and dashes' => ['+1 333-888 5555', '+13338885555'],
            'International number with parenthesis, spaces and dashes' => ['+1 (333) 888-5555', '+13338885555'],
            'International number with spaces and extension' => ['+1 333 888 5555 ext. 123', '+13338885555'],
            'International number with dashes and extension' => ['+1-333-888-5555 ext. 123', '+13338885555'],
            'International number with dashes, spaces and extension' => ['+1-333 888-5555 ext. 123', '+13338885555'],
            'International number ending with one extra number' => ['+1 333 888 5555 4', '+13338885555'],
            'Local number with spaces' => ['333 888 5555', '+13338885555'],
            'Local number with dashes' => ['333-888-5555', '+13338885555'],
            'Local number with spaces and dashes' => ['333 888-5555', '+13338885555'],
            'Local number with parenthesis, spaces and dashes' => ['(333) 888-5555', '+13338885555'],
            'Local number with spaces and extension' => ['333 888 5555 ext. 123', '+13338885555'],
            'Local number with spaces, dashes and extension' => ['333 888-5555 ext. 123', '+13338885555'],
            'Local number ending with one extra number' => ['333 888 5555 4', '+13338885555'],
        ];
    }
}
