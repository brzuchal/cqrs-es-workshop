<?php

namespace Application;

use Prooph\Common\Messaging\Command;

class CreateAccount extends Command
{
    private $id;

    private $currency;

    /**
     * CreateAccount constructor.
     * @param $id
     * @param $currency
     */
    public function __construct($id, $currency)
    {
        $this->init();

        $this->id = $id;
        $this->currency = $currency;
    }

    public function id()
    {
        return $this->id;
    }

    public function currency()
    {
        return $this->currency;
    }

    public function payload()
    {
        return [
            'currency' => $this->currency,
        ];
    }

    protected function setPayload(array $payload)
    {
        $this->currency = $payload['currency'];
    }
}