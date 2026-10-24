<?php

namespace Omniticket\Tests;

use Omniticket\Model\Attendee;
use Omniticket\Model\AttendeeStatus;
use Omniticket\Model\Availability;
use Omniticket\Model\Check;
use Omniticket\Model\CheckResult;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;
use Omniticket\Model\EventStatus;
use Omniticket\Model\Money;
use Omniticket\Model\Rate;
use Omniticket\Support\Data;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testMoneyKeepsMinorUnits(): void
    {
        self::assertSame(['amount' => 999, 'currency' => 'EUR', 'major' => '9.99'], Money::fromMajor(9.99, 'eur')->toArray());
        self::assertSame(['amount' => 3000, 'currency' => 'JPY', 'major' => '3000'], Money::fromMajor('3000', 'JPY')->toArray());
        self::assertSame('12.50', (new Money(1250, 'EUR'))->major());
        self::assertTrue((new Money(0, 'EUR'))->isFree());
    }

    public function testAvailability(): void
    {
        self::assertSame(['remaining' => 80, 'capacity' => 200, 'sold' => 120, 'soldOut' => false, 'unlimited' => false], Availability::of(null, 200, 120)->toArray());
        self::assertTrue(Availability::of(0, 100)->soldOut);
        self::assertFalse((new Availability())->known());
        self::assertTrue(Availability::unlimited(12)->known());
        self::assertSame(0, Availability::of(null, 10, 13)->remaining, 'oversold: never negative');
    }

    public function testAnEventsLowestPriceAndSale(): void
    {
        $event = new Event('1', 'Show', status: EventStatus::OnSale, rates: [new Rate('a', 'Full', Money::fromMajor(25, 'EUR')), new Rate('b', 'Free choice', null, minimum: Money::fromMajor(5, 'EUR')), new Rate('c', 'Reduced', Money::fromMajor(18, 'EUR'))]);

        self::assertSame('18.00', $event->lowestPrice()?->major());
        self::assertTrue($event->onSale());
        self::assertFalse((new Event('2', 'Full house', status: EventStatus::OnSale, availability: Availability::of(0, 10)))->onSale());
        self::assertTrue((new Event('2', 'Full house', availability: Availability::of(0, 10)))->soldOut());
        self::assertSame('5.00', (new Event('3', 'Range', priceFrom: Money::fromMajor(5, 'EUR')))->lowestPrice()?->major());
        self::assertSame('on_sale', $event->toArray()['status']);
    }

    public function testChecksFollowTheTicketsStatus(): void
    {
        $attendees = [new Attendee('1', 'e', 'A'), new Attendee('2', 'e', 'B', status: AttendeeStatus::CheckedIn), new Attendee('3', 'e', 'C', status: AttendeeStatus::Cancelled), new Attendee('4', 'e', 'D', status: AttendeeStatus::Pending), new Attendee('5', 'e')];

        self::assertSame(CheckResult::Valid, Check::among($attendees, 'A')->result);
        self::assertTrue(Check::among($attendees, 'A')->valid());
        self::assertSame('1', Check::among($attendees, 'A')->attendee?->id);
        self::assertSame(CheckResult::AlreadyUsed, Check::among($attendees, 'B')->result);
        self::assertSame(CheckResult::Void, Check::among($attendees, 'C')->result);
        self::assertSame(CheckResult::Void, Check::among($attendees, 'D')->result);
        self::assertSame(['code' => 'Z', 'result' => 'unknown', 'attendee' => null], Check::among($attendees, 'Z')->toArray());
        self::assertSame('Jeanne Dupont', (new Attendee('1', 'e', firstName: 'Jeanne', lastName: 'Dupont'))->name());
    }

    public function testAQueryFiltersTheSameWayForEveryProvider(): void
    {
        $now = new \DateTimeImmutable('2026-11-01');
        $events = [
            new Event('past', 'Before', new \DateTimeImmutable('2026-10-01')),
            new Event('running', 'Festival', new \DateTimeImmutable('2026-10-30'), new \DateTimeImmutable('2026-11-03'), venue: 'Strasbourg'),
            new Event('dec', 'Concert', new \DateTimeImmutable('2026-12-10')),
            new Event('jan', 'Concert du nouvel an', new \DateTimeImmutable('2027-01-01')),
            new Event('undated', 'Someday'),
        ];
        $ids = static fn (EventQuery $q): array => array_map(static fn (Event $e) => $e->id, [...$q->filter($events)]);

        self::assertSame(['running', 'dec', 'jan', 'undated'], $ids(new EventQuery(now: $now)), 'an event under way is upcoming until it ends');
        self::assertSame(['past', 'running', 'dec', 'jan', 'undated'], $ids(new EventQuery(past: true, now: $now)));
        self::assertSame(['dec', 'jan'], $ids(new EventQuery(keyword: 'CONCERT', now: $now)));
        self::assertSame(['running'], $ids(new EventQuery(keyword: 'strasbourg', now: $now)), 'the venue too');
        self::assertSame(['running', 'dec', 'undated'], $ids(new EventQuery(to: new \DateTimeImmutable('2026-12-31'), now: $now)));
        self::assertSame(['dec', 'jan', 'undated'], $ids(new EventQuery(from: new \DateTimeImmutable('2026-12-01'), now: $now)));
        self::assertSame(['running', 'dec'], $ids(new EventQuery(limit: 2, now: $now)));
    }

    public function testDataReadsWithTypes(): void
    {
        $data = new Data(['a' => ['b' => ' x '], 'n' => '12', 'f' => '1.5', 't' => 'true', 'z' => '0000-00-00 00:00:00', 'ts' => 1767207600, 'd' => '2026-12-31 20:00:00', 'l' => [['id' => 1], ['id' => 2]], 'empty' => '', 'yes' => true]);

        self::assertSame('x', $data->string('a.b'));
        self::assertNull($data->string('empty'));
        self::assertNull($data->string('a.b.c'));
        self::assertSame('1', $data->string('yes'));
        self::assertSame([12, 1.5, true, false], [$data->int('n'), $data->float('f'), $data->bool('t'), $data->bool('n')]);
        self::assertNull($data->date('z'));
        self::assertSame('2025-12-31T19:00:00+00:00', $data->date('ts')?->format(\DATE_ATOM));
        self::assertSame('2026-12-31T20:00:00+01:00', $data->date('d', new \DateTimeZone('Europe/Paris'))?->format(\DATE_ATOM));
        self::assertSame([1, 2], array_map(static fn (Data $d) => $d->int('id'), $data->list('l')));
        self::assertSame([], $data->list('n'));
        self::assertSame(2, $data->data('l.1')->int('id'));
    }
}
