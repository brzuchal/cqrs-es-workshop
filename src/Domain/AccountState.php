<?php

namespace Domain;

use Esky\Enum\Enum;

class AccountState extends Enum
{
    const ACTIVE = 1;
    const BLOCKED = 2;

}