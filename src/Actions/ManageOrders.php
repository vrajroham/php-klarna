<?php

namespace Vrajroham\PhpKlarna\Actions;

use Vrajroham\PhpKlarna\Resources\Order;
use Vrajroham\PhpKlarna\Resources\RespondSuccess;

trait ManageOrders
{
    public function order(string $orderId)
    {
        $token = $this->get("ordermanagement/v1/orders/{$orderId}");

        return new Order($token);
    }
    
    public function createCapture(string $orderId, array $data)
    {
        $response = $this->post("ordermanagement/v1/orders/{$orderId}/captures", $data);

        return new RespondSuccess($response);
    }

    public function createRefund(string $orderId, array $data)
    {
        $response = $this->post("ordermanagement/v1/orders/{$orderId}/refunds", $data);

        return new RespondSuccess($response);
    }

    public function acknowledgeOrder(string $orderId)
    {
        $response = $this->post("ordermanagement/v1/orders/{$orderId}/acknowledge");

        return new RespondSuccess($response);
    }

    public function cancelOrder(string $orderId)
    {
        $response = $this->post("ordermanagement/v1/orders/{$orderId}/acknowledge");

        return new RespondSuccess($response);
    }

    public function extendAuthorizationTime(string $orderId)
    {
        $response = $this->post("ordermanagement/v1/orders/{$orderId}/extend-authorization-time");

        return new RespondSuccess($response);
    }

    public function updateMerchantReferences(string $orderId, array $data)
    {
        $response = $this->patch("ordermanagement/v1/orders/{$orderId}/merchant-references", $data);

        return new RespondSuccess($response);
    }

    public function updateCustomerUpdate(string $orderId, array $data)
    {
        $response = $this->patch("ordermanagement/v1/orders/{$orderId}/customer-details", $data);

        return new RespondSuccess($response);
    }

    public function releaseRemainingAuthorization(string $orderId)
    {
        $response = $this->post("ordermanagement/v1/orders/{$orderId}/release-remaining-authorization");

        return new RespondSuccess($response);
    }
}
