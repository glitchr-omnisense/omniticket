<?php

namespace Omniticket\Model;

/** Where a ticket stands. */
enum AttendeeStatus: string
{
    /** Paid (or free) and not scanned yet. */
    case Valid = 'valid';
    /** Scanned at the door. */
    case CheckedIn = 'checked_in';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    /** Not paid yet, waiting for a validation. */
    case Pending = 'pending';
}
