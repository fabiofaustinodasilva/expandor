<?php

namespace App\Domains\Visits\Support;

use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Collection;

/**
 * Apresentação comercial do histórico de visitas (Sprint 4.3).
 * Não altera dados persistidos.
 */
final class VisitHistoryPresenter
{
    public const GPS_FALLBACK_LABEL = 'Residência cadastrada pelo GPS';

    public const NO_CLIENT_LABEL = 'Ponto sem cliente';

    public const NO_NEXT_STEP = 'Sem próximo passo definido';

    public static function primaryResident(Visit $visit): ?Resident
    {
        $residents = $visit->property?->residents;
        if ($residents === null || $residents->isEmpty()) {
            return null;
        }

        return $residents->sortByDesc('is_primary_contact')->first()
            ?? $residents->first();
    }

    public static function displayAddress(?Address $address): string
    {
        if ($address === null) {
            return self::GPS_FALLBACK_LABEL;
        }

        $street = trim((string) $address->street);
        if ($street === '' || strcasecmp($street, 'Local GPS') === 0) {
            return self::GPS_FALLBACK_LABEL;
        }

        $label = trim($address->label());

        return $label !== '' ? $label : self::GPS_FALLBACK_LABEL;
    }

    public static function clientTitle(Visit $visit): string
    {
        $resident = self::primaryResident($visit);
        $name = trim((string) ($resident?->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return self::displayAddress($visit->property?->address);
    }

    /**
     * Label for commissions list: real resident name, else "Ponto sem cliente".
     * Does not use GPS street placeholders as the client identity.
     */
    public static function commissionClientLabel(Visit $visit): string
    {
        $resident = self::primaryResident($visit);
        $name = trim((string) ($resident?->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $saleName = trim((string) ($visit->sale?->resident?->name ?? ''));
        if ($saleName !== '') {
            return $saleName;
        }

        return self::NO_CLIENT_LABEL;
    }

    public static function phoneDigits(?Resident $resident): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($resident?->phone ?? '')) ?? '';

        if ($digits === '') {
            return '';
        }

        if (! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return $digits;
    }

    public static function phoneDisplay(?Resident $resident): ?string
    {
        $phone = trim((string) ($resident?->phone ?? ''));

        return $phone !== '' ? $phone : null;
    }

    /**
     * 1) FollowUp pending da Visit
     * 2) FollowUp pending da mesma Property + mesmo vendedor
     * 3) null
     *
     * @param  Collection<int, Collection<int, FollowUp>>  $propertyFollowUps  keyed by property_id
     */
    public static function resolveNextFollowUp(Visit $visit, Collection $propertyFollowUps): ?FollowUp
    {
        $onVisit = $visit->followUps
            ->where('status', FollowUpStatus::PENDING)
            ->sortBy('scheduled_at')
            ->first();

        if ($onVisit instanceof FollowUp) {
            return $onVisit;
        }

        $propertyId = $visit->property_id;
        if ($propertyId === null) {
            return null;
        }

        $candidates = $propertyFollowUps->get((int) $propertyId, collect());

        return $candidates->sortBy('scheduled_at')->first();
    }

    public static function nextActionLabel(?FollowUp $followUp): string
    {
        if ($followUp === null) {
            return self::NO_NEXT_STEP;
        }

        $label = FollowUpSchedule::label($followUp->scheduled_at);
        $hint = FollowUpSchedule::timeHint($followUp->scheduled_at);

        if ($hint !== null) {
            return $label.' · '.$hint;
        }

        return 'Retornar '.$label;
    }

    public static function nextActionShort(?FollowUp $followUp): string
    {
        if ($followUp === null) {
            return self::NO_NEXT_STEP;
        }

        return FollowUpSchedule::label($followUp->scheduled_at);
    }
}
