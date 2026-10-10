<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A checkout problem the customer can understand and fix (item gone,
 * restaurant closed, bad coupon...). Rendered as HTTP 422 by the API
 * controllers: {"message": "...", "code": "...", ...extra}.
 *
 * `code` lets the Flutter app react (e.g. open the address sheet for
 * "address_required") without parsing the message text.
 */
class CheckoutException extends RuntimeException
{
    /** @param array<string, mixed> $extra */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'checkout_failed',
        public readonly array $extra = [],
    ) {
        parent::__construct($message);
    }

    /** @return array<string, mixed> */
    public function toPayload(): array
    {
        return ['message' => $this->getMessage(), 'code' => $this->errorCode] + $this->extra;
    }
}
