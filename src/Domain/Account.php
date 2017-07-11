<?php

namespace Domain;

use Domain\Event\AccountCreated;
use Domain\Event\MoneyAdded;
use Money\Currency;
use Money\Money;
use Prooph\EventSourcing\AggregateRoot;
use Rhumsaa\Uuid\Uuid;

class Account extends AggregateRoot
{
    /**
     * @var Uuid
     */
    private $id;

    /**
     * @var Money
     */
    private $balance;

    /**
     * @var AccountState
     */
    private $state;

    public static function new(Uuid $id, string $currency)
    {
        $self = new self();

        $self->recordThat(AccountCreated::from($id, $currency));

        return $self;
    }

    public function add(Money $money)
    {
        // ?
        $this->recordThat(MoneyAdded::from($this->id, $money->getAmount(), $money->getCurrency()));
    }

    protected function aggregateId()
    {
        return (string)$this->id;
    }

    public function id()
    {
        return $this->aggregateId();
    }

    protected function whenAccountCreated(AccountCreated $event)
    {
        $this->id = Uuid::fromString($event->aggregateId());
        $this->state = AccountState::ACTIVE();
        $this->balance = new Money(0, new Currency($event->currency()));
    }

    protected function whenMoneyAdded(MoneyAdded $event)
    {
        $this->balance = $this->balance->add(new Money($event->amount(), new Currency($event->currency())));
    }
}