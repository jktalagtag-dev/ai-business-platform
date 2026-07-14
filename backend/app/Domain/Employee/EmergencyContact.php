<?php

declare(strict_types=1);

namespace App\Domain\Employee;

final class EmergencyContact
{
    public function __construct(
        public readonly string $name,
        public readonly string $relationship,
        public readonly string $phone,
        public readonly ?string $email = null,
    ) {}

    /**
     * @return array{name: string, relationship: string, phone: string, email: ?string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'relationship' => $this->relationship,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            relationship: $data['relationship'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
        );
    }
}
