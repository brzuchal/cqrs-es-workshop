<?php
namespace Application;

use Prooph\Common\Messaging\Command;

class WithdrawMoney extends Command
{
    private $id;

    private $amount;

    private $currency;

    public function __construct($id, $amount, $currency)
    {
        $this->init();

        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * @return mixed
     */
    public function id()
    {
        return $this->id;
    }

    public function amount()
    {
        return $this->amount;
    }

    public function currency()
    {
        return $this->currency;
    }

    public function payload()
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }

    protected function setPayload(array $payload)
    {
        $this->currency = $payload['currency'];
        $this->amount = $payload['amount'];
    }
}
