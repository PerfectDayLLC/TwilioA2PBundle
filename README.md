# TwilioA2PBundle
Initial Usage For 10-DLC Integrations

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

You will also need to edit the `config\perfectdayllc\twilioa2pbundle.php` config file:
* `entity_model` will be used to point to your main System's entity holder. For example: `\App\Models\Company::class`.
