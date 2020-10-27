<?php

namespace Vrajroham\PhpKlarna\Actions;

use Vrajroham\PhpKlarna\Resources\CustomerTokenFromAuthorization;
use Vrajroham\PhpKlarna\Resources\OrderCreated;
use Vrajroham\PhpKlarna\Resources\RespondSuccess;
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
        $customerToken = $this->post('payments/v1/authorizations/'.$authorizationToken.'/customer-token', $data);

        return new CustomerTokenFromAuthorization($customerToken, $this);
    }

    public function createOrderFromAuthorizationToken(string $authorizationToken, $data)
    {
        $order = $this->post('payments/v1/authorizations/'.$authorizationToken.'/order', $data);

        return new OrderCreated($order, $this);
    }

    public function cancelAuthorization(string $authorizationToken, $data)
    {
        $response = $this->delete('payments/v1/authorizations/'.$authorizationToken, $data);

        return new RespondSuccess($response, $this);
    }
}
