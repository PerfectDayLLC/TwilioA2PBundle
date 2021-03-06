# TwilioA2PBundle
Initial Usage For A2P 10-DLC Integration

## Setup
### Needed files
To be able to have the needed files for this package to work, please run the following command
`php artisan vendor:publish --tag={TAGS}` (you can use multiple values like
[this example](https://laravel.com/docs/8.x/artisan#option-arrays)) and replace `{TAGS}` with the following tags:
* `twilio-a2p-bundle-config` to publish the config file.
* `twilio-a2p-bundle-factories` (optional) to get the `ClientRegistrationHistoryFactory` if you need to use it in your
own tests.

### What to implement
You have to implement `PerfectDayLlc\TwilioA2P\Contracts\ClientRegistrationHistory` interface on your main model
(referenced in this package as `entity`), and implement the needed contracts:
* `getClientData` will be used to return a `PerfectDayLlc\TwilioA2PBundle\Entities\ClientData` instance with desired
information already set.
* `twilioA2PClientRegistrationHistories` is the relation's name we will use to go back and forth with the history data.
* `customTwilioA2PFiltering` is used to give the user the ability to add custom filtering when getting the entity model.

You will also need to edit the `config\perfectdayllc\twilioa2pbundle.php` config file:
* `entity_model` will be used to point to your main System's entity holder (use namespace).
For example: `App\Models\Company`.

#### Queue
If you are using [`Horizon`](https://github.com/laravel/horizon) or any other way of running queues, these are the queues you must be listening to:
https://github.com/PerfectDayLLC/TwilioA2PBundle/blob/87c3248deef802b0b26587c6ee3162cacd387f90/src/Domain/EntityRegistrator.php#L31-L39

### Configuration
You only need to add these key/value pairs to `service.twilio`:
* `sid`: Twilio SID.
* `token`: Twilio Token.
* `primary_customer_profile_sid`: https://www.twilio.com/docs/trust-hub/trusthub-rest-api/console-create-a-primary-customer-profile.
* `customer_profile_policy_sid`: This is a hardcoded value already in place.
