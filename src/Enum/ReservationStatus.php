<?php

namespace App\Enum;

enum ReservationStatus: string
{
    /** Inquiry from the public form, waiting for the owner to confirm. */
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    /** Owner declined the inquiry. */
    case Rejected = 'rejected';
    /** Confirmed stay that was called off later. */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ke schválení',
            self::Confirmed => 'Potvrzená',
            self::Rejected => 'Zamítnutá',
            self::Cancelled => 'Zrušená',
        };
    }

    /** Whether the reservation holds the room (shows in the timeline, counts for overlaps). */
    public function blocksRoom(): bool
    {
        return self::Pending === $this || self::Confirmed === $this;
    }

    /** @return list<self> */
    public static function blocking(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $s) => $s->blocksRoom()));
    }
}
