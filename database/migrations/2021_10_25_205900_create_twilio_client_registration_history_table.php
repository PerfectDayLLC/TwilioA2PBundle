<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTwilioClientRegistrationHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('twilio_a2p_registration_history', function (Blueprint $table) {
            /** @var \Illuminate\Database\Eloquent\Model $entityModel */
            $entityModel = config('twilioa2pbundle.entity_model');

            if (in_array((new $entityModel)->getKeyType(), ['int', 'integer'])) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('entity_id')->nullable()->index();
            } else {
                $table->uuid('id')->primary();
                $table->uuid('entity_id')->nullable()->index();
            }

            $table->string('request_type')->nullable();
            $table->boolean('error')->default(false);
            $table->string('bundle_sid')->nullable()->index();
            $table->string('object_sid')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->json('response')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('twilio_a2p_registration_history');
    }
}
