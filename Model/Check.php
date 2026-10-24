<?php

namespace Omniticket\Model;

/**
 * A ticket code checked against an event: valid, already used, void or
 * unknown, with the attendee when one was found. A check reads; it never
 * marks the ticket as scanned at the provider.
 */
final readonly class Check
{
    public function __construct(public string $code, public CheckResult $result, public ?Attendee $attendee = null)
    {
    }

    public static function of(string $code, ?Attendee $attendee): self
    {
        return new self($code, match ($attendee?->status) {
            null => CheckResult::Unknown,
            AttendeeStatus::Valid => CheckResult::Valid,
            AttendeeStatus::CheckedIn => CheckResult::AlreadyUsed,
            AttendeeStatus::Cancelled, AttendeeStatus::Refunded, AttendeeStatus::Pending => CheckResult::Void,
        }, $attendee);
    }

    /**
     * The check by reading the attendees: for providers with no lookup by
     * code. The first attendee holding the code decides.
     *
     * @param iterable<Attendee> $attendees
     */
    public static function among(iterable $attendees, string $code): self
    {
        foreach ($attendees as $attendee) {
            if (null !== $attendee->code && hash_equals($attendee->code, $code)) {
                return self::of($code, $attendee);
            }
        }

        return self::of($code, null);
    }

    public function valid(): bool
    {
        return CheckResult::Valid === $this->result;
    }

    /** @return array{code: string, result: string, attendee: ?array<string, mixed>} */
    public function toArray(): array
    {
        return ['code' => $this->code, 'result' => $this->result->value, 'attendee' => $this->attendee?->toArray()];
    }
}
