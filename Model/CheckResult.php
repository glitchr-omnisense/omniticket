<?php

namespace Omniticket\Model;

/** What the door is told about a ticket code. */
enum CheckResult: string
{
    case Valid = 'valid';
    /** Already scanned. */
    case AlreadyUsed = 'already_used';
    /** Cancelled, refunded or not paid. */
    case Void = 'void';
    /** No ticket of this event holds that code. */
    case Unknown = 'unknown';
}
