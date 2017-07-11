<?php

namespace Domain;

use Rhumsaa\Uuid\Uuid;

interface AccountRepository
{
    public function get(Uuid $id): Account;

    public function save(Account $account);
}