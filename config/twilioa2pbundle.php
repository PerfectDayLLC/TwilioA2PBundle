<?php

use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;

return [

    /**
     * This is used to tell the package what is the model it should be using as the relational entity.
     *
     * Example: App\Models\User
     */
    'entity_model' => env('PERFECTDAYLLC_TWILIO_A2P_BUNDLE_ENTITY_MODEL'),

    /**
     * This should be changed if you need to extend the ClientRegistrationHistory model, so you let the package know
     * it should use your implementation (an extension from the original one).
     */
    'client_registration_history_model' => env('PERFECTDAYLLC_TWILIO_A2P_BUNDLE_CRH_MODEL',
        ClientRegistrationHistory::class
    ),

];
