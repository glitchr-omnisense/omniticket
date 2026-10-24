<?php

namespace Omniticket\Model;

/** One ticket and the person it is for. */
final readonly class Attendee
{
    /** @param ?string $code what the ticket's barcode or QR code holds: what the door checks */
    public function __construct(
        public string $id,
        public string $eventId,
        public ?string $code = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $rate = null,
        public AttendeeStatus $status = AttendeeStatus::Valid,
        public ?\DateTimeImmutable $checkedInAt = null,
        public ?string $orderId = null,
        public ?string $sessionId = null,
        public ?string $ticketUrl = null,
    ) {
    }

    public function name(): string
    {
        return trim(($this->firstName ?? '').' '.($this->lastName ?? ''));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'eventId' => $this->eventId,
            'code' => $this->code,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'email' => $this->email,
            'rate' => $this->rate,
            'status' => $this->status->value,
            'checkedInAt' => $this->checkedInAt?->format(\DATE_ATOM),
            'orderId' => $this->orderId,
            'sessionId' => $this->sessionId,
            'ticketUrl' => $this->ticketUrl,
        ];
    }
}
