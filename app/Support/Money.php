<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Immutable money value object stored as integer paise (1 INR = 100 paise).
 * All monetary arithmetic in the rental domain flows through this class to
 * avoid floating-point rounding errors. See Database/01-Database-Schema.md.
 */
final class Money
{
    private function __construct(public readonly int $paise)
    {
        if ($paise < 0) {
            throw new InvalidArgumentException('Money cannot be negative.');
        }
    }

    public static function ofPaise(int $paise): self
    {
        return new self($paise);
    }

    public static function ofRupees(int|float $rupees): self
    {
        return new self((int) round($rupees * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function add(Money $other): self
    {
        return new self($this->paise + $other->paise);
    }

    public function subtract(Money $other): self
    {
        return new self(max(0, $this->paise - $other->paise));
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->paise * $factor));
    }

    /** Percentage of this amount, e.g. percent(18) for 18% GST. */
    public function percent(float $percent): self
    {
        return new self((int) round($this->paise * $percent / 100));
    }

    public function isZero(): bool
    {
        return $this->paise === 0;
    }

    public function greaterThan(Money $other): bool
    {
        return $this->paise > $other->paise;
    }

    public function toRupees(): float
    {
        return round($this->paise / 100, 2);
    }

    public function __toString(): string
    {
        return (string) $this->paise;
    }
}
