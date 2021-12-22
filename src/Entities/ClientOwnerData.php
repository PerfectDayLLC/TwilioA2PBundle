<?php

namespace PerfectDayLlc\TwilioA2PBundle\Entities;

class ClientOwnerData
{
    private string $firstname;

    private string $lastname;

    private string $email;

    public function __construct(string $firstname, string $lastname, string $email)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
