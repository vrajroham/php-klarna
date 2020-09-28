<?php

namespace Vrajroham\PhpKlarna\Actions;

use Vrajroham\PhpKlarna\Resources\CustomerToken;
use Vrajroham\PhpKlarna\Resources\Session;

trait ManagePayments
{
    public function createSession(array $data)
    {
        $sessionAttributes = $this->post('payments/v1/sessions', $data);

        return new Session($sessionAttributes, $this);
    }

    public function createCustomerToken(string $authorizationToken, $data)
    {
        $customerToken = $this->post("payments/v1/authorizations/".$authorizationToken."/customer-token", $data);

        return new CustomerToken($customerToken, $this);
    }
}
