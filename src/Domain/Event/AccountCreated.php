<?php

namespace Domain\Event;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

class AccountCreated extends AggregateChanged
{
    public static function from(Uuid $id, string $currency)
    {
        return self::occur(
            (string)$id,
            [
                'currency' => $currency
            ]
        );
    }

    public function currency()
    {
        return $this->payload['currency'];
    }
}