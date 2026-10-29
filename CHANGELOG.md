# Changelog

## [Unreleased]

### Fixed

- `FixedTicketing::attendees()` with `$updatedSince` is a `NotSupportedException`, as the contract asks
  of a provider that is not incremental (its `capabilities()->incremental` is false), where it
  ignored the date and returned every attendee.
