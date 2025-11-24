<?php

declare(strict_types=1);

namespace App\CRM\Contact\ValueObject;

use InvalidArgumentException;

class PhoneNumber
{
    private string $value;

    public function __construct(string $phoneNumber)
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (empty($cleaned) || strlen($cleaned) < 10) {
            throw new InvalidArgumentException(sprintf('Invalid phone number: "%s"', $phoneNumber));
        }

        $this->value = $cleaned;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(PhoneNumber $other): bool
    {
        return $this->value === $other->value;
    }

    public function getFormatted(): string
    {
        // Simple formatting for display
        $value = $this->value;

        if (11 === strlen($value) && str_starts_with($value, '7')) {
            // Russian format: +7 (XXX) XXX-XX-XX
            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($value, 1, 3),
                substr($value, 4, 3),
                substr($value, 7, 2),
                substr($value, 9, 2)
            );
        }

        return $value;
    }
}
