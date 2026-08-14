<?php

namespace App\Domains\Sales\Properties\Services;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PropertyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createAddress(array $data): Address
    {
        $this->assertTerritoryOwnership((int) $data['city_id'], $data['sector_id'] ?? null);

        return Address::query()->create([
            'city_id' => $data['city_id'],
            'sector_id' => $data['sector_id'] ?? null,
            'street' => $data['street'],
            'number' => $data['number'] ?? null,
            'complement' => $data['complement'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'zipcode' => $data['zipcode'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAddress(Address $address, array $data): Address
    {
        $this->assertTerritoryOwnership((int) $data['city_id'], $data['sector_id'] ?? null);

        $address->update([
            'city_id' => $data['city_id'],
            'sector_id' => $data['sector_id'] ?? null,
            'street' => $data['street'],
            'number' => $data['number'] ?? null,
            'complement' => $data['complement'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'reference' => array_key_exists('reference', $data) ? $data['reference'] : $address->reference,
            'zipcode' => $data['zipcode'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return $address->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createProperty(array $data, ?User $user = null): Property
    {
        return DB::transaction(function () use ($data, $user) {
            /** @var Address $address */
            $address = Address::query()->findOrFail($data['address_id']);

            $status = PropertyStatus::from($data['status'] ?? PropertyStatus::NEW->value);

            $property = Property::query()->create([
                'address_id' => $address->id,
                'type' => $data['type'] ?? 'house',
                'status' => $status,
                'latitude' => $data['latitude'] ?? $address->latitude,
                'longitude' => $data['longitude'] ?? $address->longitude,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->recordHistory(
                property: $property,
                user: $user,
                oldStatus: null,
                newStatus: $status,
                description: $data['history_description'] ?? 'Imóvel cadastrado.',
            );

            return $property;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function changeStatus(Property $property, array $data, User $user): Property
    {
        return DB::transaction(function () use ($property, $data, $user) {
            $newStatus = PropertyStatus::from($data['status']);
            $oldStatus = $property->status;

            if ($oldStatus === $newStatus) {
                return $property;
            }

            $property->update([
                'status' => $newStatus,
                'latitude' => $data['latitude'] ?? $property->latitude,
                'longitude' => $data['longitude'] ?? $property->longitude,
                'notes' => $data['notes'] ?? $property->notes,
            ]);

            $this->recordHistory(
                property: $property->refresh(),
                user: $user,
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                description: $data['description'] ?? null,
                latitude: $data['latitude'] ?? null,
                longitude: $data['longitude'] ?? null,
            );

            return $property->refresh();
        });
    }

    /**
     * Apply a door-to-door visit outcome to the property timeline.
     */
    public function applyVisitOutcome(
        Property $property,
        User $user,
        ?PropertyStatus $newStatus,
        string $description,
        mixed $latitude = null,
        mixed $longitude = null,
    ): Property {
        return DB::transaction(function () use ($property, $user, $newStatus, $description, $latitude, $longitude) {
            $oldStatus = $property->status;
            $resolvedStatus = $newStatus ?? $oldStatus;

            if ($newStatus !== null && $oldStatus !== $newStatus) {
                $property->update([
                    'status' => $newStatus,
                    'latitude' => $latitude ?? $property->latitude,
                    'longitude' => $longitude ?? $property->longitude,
                ]);
                $property->refresh();
            }

            $this->recordHistory(
                property: $property,
                user: $user,
                oldStatus: $oldStatus,
                newStatus: $resolvedStatus,
                description: $description,
                latitude: $latitude,
                longitude: $longitude,
            );

            return $property->refresh();
        });
    }

    protected function recordHistory(
        Property $property,
        ?User $user,
        ?PropertyStatus $oldStatus,
        PropertyStatus $newStatus,
        ?string $description = null,
        mixed $latitude = null,
        mixed $longitude = null,
    ): PropertyHistory {
        return PropertyHistory::query()->create([
            'property_id' => $property->id,
            'user_id' => $user?->id,
            'old_status' => $oldStatus?->value,
            'new_status' => $newStatus->value,
            'description' => $description,
            'latitude' => $latitude ?? $property->latitude,
            'longitude' => $longitude ?? $property->longitude,
            'created_at' => now(),
        ]);
    }

    protected function assertTerritoryOwnership(int $cityId, mixed $sectorId): void
    {
        City::query()->findOrFail($cityId);

        if ($sectorId === null || $sectorId === '') {
            return;
        }

        $sector = Sector::query()->findOrFail($sectorId);

        if ((int) $sector->city_id !== $cityId) {
            throw new InvalidArgumentException('O setor informado não pertence à cidade selecionada.');
        }
    }
}
