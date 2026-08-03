<?php

namespace Tests\Unit\Visits;

use App\Domains\Visits\Support\FollowUpSchedule;
use Carbon\Carbon;
use Tests\TestCase;

class FollowUpScheduleTest extends TestCase
{
    public function test_midnight_means_no_time(): void
    {
        $at = Carbon::parse('2026-08-04 00:00:00', config('app.timezone'));

        $this->assertFalse(FollowUpSchedule::hasTime($at));
        $this->assertSame('04/08/2026', FollowUpSchedule::label($at));
        $this->assertSame('Horário não definido', FollowUpSchedule::timeHint($at));
    }

    public function test_explicit_time_is_shown(): void
    {
        $at = Carbon::parse('2026-08-04 14:30:00', config('app.timezone'));

        $this->assertTrue(FollowUpSchedule::hasTime($at));
        $this->assertSame('04/08/2026 às 14:30', FollowUpSchedule::label($at));
        $this->assertNull(FollowUpSchedule::timeHint($at));
    }

    public function test_date_only_normalizes_to_start_of_day(): void
    {
        $normalized = FollowUpSchedule::normalize('2026-08-04');

        $this->assertNotNull($normalized);
        $this->assertSame('2026-08-04 00:00:00', $normalized->format('Y-m-d H:i:s'));
        $this->assertFalse(FollowUpSchedule::hasTime($normalized));
    }

    public function test_from_date_and_optional_time(): void
    {
        $dateOnly = FollowUpSchedule::fromDateAndTime('2026-08-04', null);
        $withTime = FollowUpSchedule::fromDateAndTime('2026-08-04', '14:30');

        $this->assertSame('04/08/2026', FollowUpSchedule::label($dateOnly));
        $this->assertSame('04/08/2026 às 14:30', FollowUpSchedule::label($withTime));
    }

    public function test_date_only_overdue_compares_by_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-04 18:00:00', config('app.timezone')));

        $today = Carbon::parse('2026-08-04 00:00:00', config('app.timezone'));
        $yesterday = Carbon::parse('2026-08-03 00:00:00', config('app.timezone'));

        $this->assertFalse(FollowUpSchedule::isOverdue($today));
        $this->assertTrue(FollowUpSchedule::isOverdue($yesterday));

        Carbon::setTestNow();
    }
}
