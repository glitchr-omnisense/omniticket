---
title: Attendees and checks
order: 32
---

# Attendees and checks

## Attendees

```php
$list = $registry->attendees('box_office');

// The first read: every ticket, cancelled and refunded ones included.
foreach ($list->attendees('1235') as $attendee) {
    $store->save($attendee->toArray());
}

// Then only what changed, where the provider allows it (Capabilities::$incremental).
$since = $store->lastRead('1235');
foreach ($list->attendees('1235', $since) as $attendee) {
    $store->save($attendee->toArray());
}
```

The providers ask for it - Billetweb and Weezevent in so many words: keep the list on the
application's side and read it incrementally.

| | Call | Incremental | Code | Status |
|---|---|---|---|---|
| Weezevent | `GET /participant/list` (`include_deleted`, `include_unpaid`), plus `/tickets` once per event for the rates' names | `last_update`, in Weezevent's zone | `barcode` | deleted: cancelled; refund: refunded; not paid: pending; `control_status.status` 1: checked in (its scan date) |
| Billetweb | `GET /api/event/{id}/attendees?disabled=1` | `last_update` (a timestamp) | `barcode` | disabled: cancelled when the order was paid, else refunded; `used` 1 or 2 (scanned, gone out): checked in (its date); 3 (expired): cancelled; order not paid: pending |
| HelloAsso | `GET .../forms/Event/{formSlug}/items?withDetails=true`, pages of 100 | `NotSupportedException`: the items have no such filter | `qrCode` | a refunded payment: refunded; `Processed`, `Registered`: valid; `Waiting`, `Unknown` or none: pending; anything else: cancelled. No scan state |
| Eventbrite | `GET /events/{id}/attendees/`, by continuation | `changed_since` (UTC) | the first barcode | refunded; cancelled or `Not Attending`: cancelled; checked in or barcode `used`: checked in (the barcode's date); `Unpaid`: pending |
| Ticketmaster | - (`NotSupportedException` from the registry) | | | |

## Checking a ticket at the door

```php
use Omniticket\Model\CheckResult;

$check = $registry->checker('box_office')->check('1235', $scannedCode);
echo match ($check->result) {
    CheckResult::Valid => 'OK '.$check->attendee?->name().' - '.$check->attendee?->rate,
    CheckResult::AlreadyUsed => 'Already scanned at '.$check->attendee?->checkedInAt?->format('H:i'),
    CheckResult::Void => 'Cancelled, refunded or not paid',
    CheckResult::Unknown => 'Not a ticket of this event',
};
```

A check **reads only**: the ticket is not marked as scanned at the provider (the provider's own
scanning application does that; Billetweb's `check` POST, which would, is not used). Two checks
of the same code both answer `Valid` until the provider's own application scans it.

| | How the code is found | Calls per check |
|---|---|---|
| Billetweb | a lookup by barcode (`attendees?barcode=`) | one |
| Weezevent | the event's participants read, the code looked for among them | two (the rates' names kept after the first) |
| Eventbrite | the event's attendees read, every page | one per page |
| HelloAsso | the event's items read; never `AlreadyUsed` (no scan state) | one per page, plus a token every 30 minutes |

An application that checks many tickets keeps the list itself - `attendees()` with
`$updatedSince`, then `Check::among($attendees, $code)` locally - rather than reading the whole
list at every scan: Billetweb's attendee calls are limited to 10 a minute, Eventbrite's to 2,000
an hour.
