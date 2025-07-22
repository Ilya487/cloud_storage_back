<?php

namespace App\Models;

use DateTime;

class RememberMeToken
{
    public function __construct(
        public readonly string $selector,
        public readonly string $validatorHash,
        public readonly int $userId,
        private DateTime $expiresAt
    ) {}

    public static function createFromArr(array $data): RememberMeToken
    {
        return new RememberMeToken(
            $data['selector'],
            $data['validator_hash'],
            $data['user_id'],
            new DateTime($data['expires'])
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTime();
    }

    public function verifyValidator(string $hash): bool
    {
        return hash_equals($this->validatorHash, $hash);
    }

    public function getExpiresTimeInTimestampFormat(): string
    {
        return $this->expiresAt->format('Y-m-d H:i:s');
    }
}
