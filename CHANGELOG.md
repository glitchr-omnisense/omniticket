# Changelog

## [Unreleased]

### Fixed

- No exception `JsonApi::request()` throws keeps its trace's arguments: an error answered (a refused key,
  nothing there, a server error, a body that is not JSON) kept the request's options - the query,
  headers and token with the key - in its trace (`zend.exception_ignore_args` off), as the
  `UnreachableException` did before its fix.
- An `UnreachableException` no longer carries the request's secrets. `Http\JsonApi::request()` put
  the HTTP client's message, the whole URL with a key in its query (Ticketmaster's `apikey=`), in
  its message, wrapped the client's own exception as its previous, and every trace of the chain
  kept the arguments of the calls under it (the query and headers with the key, when
  `zend.exception_ignore_args` is off). The message and the previous - now a copy of the client's
  exception, the same class and code - have the key's parameters, the URL's user part and the
  request's secrets masked as `***` (`Http\Redaction`), and no trace of the chain keeps its
  arguments.
- `FixedTicketing::attendees()` with `$updatedSince` is a `NotSupportedException`, as the contract asks
  of a provider that is not incremental (its `capabilities()->incremental` is false), where it
  ignored the date and returned every attendee.
