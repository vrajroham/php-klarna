<?php

namespace Vrajroham\PhpKlarna\Actions;

use Vrajroham\PhpKlarna\Resources\CustomerToken;
use Vrajroham\PhpKlarna\Resources\Order;

trait ManageCustomerTokens
{
    public function customerToken(string $customerToken)
    {
        $token = $this->get("customer-token/v1/tokens/{$customerToken}");

        return new CustomerToken($token);
    }
    public function createOrderFromCustomerToken(string $customerToken, $data)
    {
        $order = $this->post("customer-token/v1/tokens/{$customerToken}/order", $data);

        return new Order($order, $this);
    }
}
