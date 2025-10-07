<?php

namespace App\Contracts\Payments;

use App\Models\User;
use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Charge a payment source
     * @param array $data
     * @param User $user
     * @return array {success, message, transaction_id?, gateway_response?, code?}
     */
    public function charge(array $data, User $user): array;

    /**
     * Refund a specific transaction
     * @param PaymentTransaction $transaction
     * @param float $amount
     * @return array
     */
    public function refund(PaymentTransaction $transaction, float $amount): array;

    /**
     * Create a customer profile/token in the gateway (if applicable)
     * @param User $user
     * @param array $options
     * @return array
     */
    public function createCustomerProfile(User $user, array $options = []): array;

    /**
     * Verify webhook signature for incoming events
     * @param string $gateway
     * @param array $payload
     * @return bool
     */
    public function verifyWebhookSignature(string $gateway, array $payload): bool;
}

