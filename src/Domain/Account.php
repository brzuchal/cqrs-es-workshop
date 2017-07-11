<?php

namespace Domain;

use Domain\Event\AccountBlocked;
use Domain\Event\AccountCreated;
use Domain\Event\MoneyAdded;
use Domain\Event\MoneyWithdrawn;
use Money\Currency;
use Money\Money;
use Prooph\EventSourcing\AggregateRoot;
use Rhumsaa\Uuid\Uuid;

class Account extends AggregateRoot
{
    /** @var Uuid */
    private $id;
    /** @var Money */
    private $balance;
    /** @var AccountState */
    private $state;
    /** @var Money */
    private $debtLimit;

    public static function new(Uuid $id, string $currency)
    {
        $self = new self();

        $self->recordThat(AccountCreated::from($id, $currency));

        return $self;
    }

    public function add(Money $money)
    {
        if (!$this->state->isEqual(AccountState::ACTIVE())) {
            throw new Exception\BlockedException('Nie można przeprowadzać operacji na zablokowanym koncie');
        }
        $this->recordThat(MoneyAdded::from($this->id, $money->getAmount(), $money->getCurrency()));
    }

    public function withdraw(Money $money)
    {
        if (!$this->state->isEqual(AccountState::ACTIVE())) {
            throw new Exception\BlockedException('Nie można przeprowadzać operacji na zablokowanym koncie');
        }
        if ($this->balance->add($this->debtLimit)->lessThan($money)) {
            throw new Exception\NegativeBalanceException(
                "Nie można wypłacić {$money->getAmount()}{$money->getCurrency()} " .
                "przy saldzie {$this->balance->getAmount()}{$this->balance->getCurrency()}"
            );
        }
        $this->recordThat(MoneyWithdrawn::from($this->id, $money->getAmount(), $money->getCurrency()));
        if ($this->balance->add($this->debtLimit)->greaterThan($money)) {
            $this->block(
                "Wypłacono więcej niż saldo ale mieści się w limicie {$this->debtLimit->getAmount()}{$this->debtLimit->getCurrency()}"
            );
        }
    }

    public function block(string $cause)
    {
        $this->recordThat(AccountBlocked::from($this->id, $cause));
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
        $this->debtLimit = new Money(500, new Currency($event->currency()));
    }

    protected function whenMoneyAdded(MoneyAdded $event)
    {
        $this->balance = $this->balance->add(new Money($event->amount(), new Currency($event->currency())));
    }

    protected function whenMoneyWithdrawn(MoneyWithdrawn $event)
    {
        $this->balance = $this->balance->subtract(new Money($event->amount(), new Currency($event->currency())));
    }

    protected function whenAccountBlocked(AccountBlocked $event)
    {
        $this->state = AccountState::BLOCKED();
    }
}