<?php

namespace App\Domains\Mobile\Support;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Collection;

class MobileApiTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function user(User $user): array
    {
        $user->loadMissing(['company', 'role.permissions']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'company' => [
                'id' => $user->company?->id,
                'name' => $user->company?->name,
                'status' => $user->company?->status,
            ],
            'role' => $user->role?->slug,
            'permissions' => $user->role?->permissions->pluck('slug')->values()->all() ?? [],
        ];
    }

    /**
     * @param  Collection<int, Campaign>|iterable<Campaign>  $campaigns
     * @return list<array<string, mixed>>
     */
    public static function campaigns(iterable $campaigns): array
    {
        return collect($campaigns)->map(fn (Campaign $campaign) => self::campaign($campaign))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function campaign(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'status' => $campaign->status?->value ?? $campaign->status,
            'start_date' => $campaign->start_date?->toDateString(),
            'end_date' => $campaign->end_date?->toDateString(),
            'city' => $campaign->city ? [
                'id' => $campaign->city->id,
                'name' => $campaign->city->name,
                'state' => $campaign->city->state,
            ] : null,
            'visits_count' => $campaign->visits_count ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function property(Property $property): array
    {
        $resident = $property->residents->first();

        return [
            'id' => $property->id,
            'type' => $property->type?->value ?? $property->type,
            'status' => $property->status?->value ?? $property->status,
            'status_label' => method_exists($property->status, 'label')
                ? $property->status->label()
                : (string) $property->status,
            'latitude' => (float) $property->latitude,
            'longitude' => (float) $property->longitude,
            'notes' => $property->notes,
            'address' => [
                'street' => $property->address?->street,
                'number' => $property->address?->number,
                'neighborhood' => $property->address?->neighborhood,
                'sector_id' => $property->address?->sector_id,
                'city_id' => $property->address?->city_id,
            ],
            'resident' => $resident ? [
                'id' => $resident->id,
                'name' => $resident->name,
                'phone' => $resident->phone,
            ] : null,
            'campaign_visits_count' => $property->campaign_visits_count ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function visit(Visit $visit): array
    {
        return [
            'id' => $visit->id,
            'campaign_id' => $visit->campaign_id,
            'property_id' => $visit->property_id,
            'user_id' => $visit->user_id,
            'status' => $visit->status?->value ?? $visit->status,
            'notes' => $visit->notes,
            'latitude' => $visit->latitude,
            'longitude' => $visit->longitude,
            'visited_at' => $visit->visited_at?->toIso8601String(),
        ];
    }
}
