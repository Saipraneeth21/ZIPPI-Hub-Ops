<?php

namespace App\Integrations\Contracts;

interface PaymentGateway
{
    /** Create a gateway order. Returns ['order_id' => string]. Amount in paise. */
    public function createOrder(int $amountPaise, string $currency, string $receipt): array;

    /** Verify the checkout signature returned by the gateway. */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool;

    /** Verify an inbound webhook payload signature. */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /** Issue a refund. Returns ['refund_id' => string]. Amount in paise. */
    public function refund(string $paymentId, int $amountPaise): array;
}
