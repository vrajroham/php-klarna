<?php

namespace Vrajroham\PhpKlarna\Actions;

use Vrajroham\PhpKlarna\Resources\Order;
use Vrajroham\PhpKlarna\Resources\RespondSuccess;

trait ManageCheckouts
{
    public function createCheckoutOrder(array $data)
    {
        $response = $this->post('checkout/v3/orders', $data);

        return new RespondSuccess($response);
    }

    public function getCheckoutOrder(string $orderId)
    {
        $response = $this->get("checkout/v3/orders/{$orderId}");

        return new RespondSuccess($response);
    }

    public function updateCheckoutOrder(string $orderId, array $data)
    {
        $response = $this->post("checkout/v3/orders/{$orderId}", $data);

        return new RespondSuccess($response);
    }
}
