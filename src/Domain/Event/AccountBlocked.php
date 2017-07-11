<?php
namespace Domain\Event;

use Prooph\EventSourcing\AggregateChanged;
use Rhumsaa\Uuid\Uuid;

/** @noinspection LongInheritanceChainInspection */
class AccountBlocked extends AggregateChanged
{
    public static function from(Uuid $id, string $cause)
    {
        return self::occur(
            (string)$id,
            [
                'cause' => $cause
            ]
        );
    }

    public function cause()
    {
        return $this->payload['cause'];
    }
}
