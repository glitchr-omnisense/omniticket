<?php

namespace Omniticket\Http;

/**
 * What a failed call may carry of its credentials, taken out before the
 * failure leaves the package: omnisense/contracts' Redaction, under
 * omniticket's name (secrets(), sensitive(), text(), copy(),
 * withoutArguments()).
 */
final class Redaction extends \Omnisense\Contracts\Http\Redaction
{
}
