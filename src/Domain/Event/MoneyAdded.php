<?php

namespace Domain\Event;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

class MoneyAdded extends AggregateChanged
{
    public static function from(Uuid $id, $amount, string $currency)
    {
        return self::occur(
            (string)$id,
            [
                'amount' => $amount,
                'currency' => $currency
            ]
        );
    }

    public function amount()
    {
        return $this->payload['amount'];
    }

    public function currency()
    {
        return $this->payload['currency'];
    }
}