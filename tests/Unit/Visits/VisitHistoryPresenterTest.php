<?php

namespace Tests\Unit\Visits;

use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Visits\Support\VisitHistoryPresenter;
use Tests\TestCase;

class VisitHistoryPresenterTest extends TestCase
{
    public function test_local_gps_becomes_friendly_label(): void
    {
        $address = new Address(['street' => 'Local GPS', 'number' => null, 'neighborhood' => null]);

        $this->assertSame(
            VisitHistoryPresenter::GPS_FALLBACK_LABEL,
            VisitHistoryPresenter::displayAddress($address)
        );
    }

    public function test_real_street_keeps_label(): void
    {
        $address = new Address([
            'street' => 'Rua A',
            'number' => '10',
            'neighborhood' => 'Centro',
        ]);

        $this->assertSame('Rua A, 10, Centro', VisitHistoryPresenter::displayAddress($address));
    }
}
