<?php

namespace App\Services\WhatsApp\DTOs;

class WhatsAppGroupParticipant
{
    public function __construct(
        public string $phoneNumber,
        public bool $added,
        public ?string $inviteCode = null,
        public ?string $inviteExpiration = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $added = ($data['Error'] ?? 0) === 0;
        $inviteCode = $data['AddRequest']['Code'] ?? null;
        $inviteExpiration = $data['AddRequest']['Expiration'] ?? null;
        $phoneNumber = str_replace('@c.us', '', $data['PhoneNumber'] ?? '');

        return new self($phoneNumber, $added, $inviteCode, $inviteExpiration);
    }
}
