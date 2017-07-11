<?php

namespace Infrastructure;

use Domain\Account;
use Domain\AccountRepository;
use Prooph\EventStore\Aggregate\AggregateRepository;
use Rhumsaa\Uuid\Uuid;

class EventSourcedAccountRepository implements AccountRepository
{
    /**
     * @var AggregateRepository
     */
    private $aggregateRepository;

    /**
     * EventSourcedAccountRepository constructor.
     * @param AggregateRepository $aggregateRepository
     */
    public function __construct(AggregateRepository $aggregateRepository)
    {
        $this->aggregateRepository = $aggregateRepository;
    }

    public function get(Uuid $id): Account
    {
        return $this->aggregateRepository->getAggregateRoot((string)$id);
    }

    public function save(Account $account)
    {
        $this->aggregateRepository->addAggregateRoot($account);
    }
}