<?php

namespace Omniticket\Model;

/** Where an event's sale stands, in the words every provider is mapped to. */
enum EventStatus: string
{
    /** Not published yet. */
    case Draft = 'draft';
    /** Published, sales not open yet. */
    case Upcoming = 'upcoming';
    case OnSale = 'on_sale';
    case SoldOut = 'sold_out';
    /** Sales over (closed, or the event under way) but the event not cancelled. */
    case SalesClosed = 'sales_closed';
    case Ended = 'ended';
    case Cancelled = 'cancelled';
    case Postponed = 'postponed';
    case Unknown = 'unknown';
}
