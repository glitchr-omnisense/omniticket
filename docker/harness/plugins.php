<?php

/*
 * The omniticket/* providers the harness installs beside the core: their
 * tests' namespace, and what their tests need besides (require-dev).
 */
return [
    'billetweb' => ['Omniticket\\Billetweb\\Tests\\'],
    'eventbrite' => ['Omniticket\\Eventbrite\\Tests\\'],
    'helloasso' => ['Omniticket\\HelloAsso\\Tests\\'],
    'ticketmaster' => ['Omniticket\\Ticketmaster\\Tests\\'],
    'weezevent' => ['Omniticket\\Weezevent\\Tests\\'],
];
