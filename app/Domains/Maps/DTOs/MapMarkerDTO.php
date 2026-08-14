<?php

namespace App\Domains\Maps\DTOs;

readonly class MapMarkerDTO
{
    public function __construct(
        public int $id,
        public float $latitude,
        public float $longitude,
        public string $status,
        public string $status_label,
        public string $color,
        public int $property_id,
        public string $address,
        public ?string $resident_name = null,
        public ?string $resident_phone = null,
        public ?string $resident_whatsapp = null,
        public ?string $resident_document = null,
        public ?string $resident_birth_date = null,
        public ?string $street = null,
        public ?string $number = null,
        public ?string $neighborhood = null,
        public ?string $reference = null,
        public ?string $city_name = null,
        public ?string $updated_at = null,
        public string $location_kind = 'gps',
        public string $commercial_group = 'new',
        public ?int $owner_user_id = null,
        public ?string $sold_product = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'color' => $this->color,
            'property_id' => $this->property_id,
            'address' => $this->address,
            'resident_name' => $this->resident_name,
            'resident_phone' => $this->resident_phone,
            'resident_whatsapp' => $this->resident_whatsapp,
            'resident_document' => $this->resident_document,
            'resident_birth_date' => $this->resident_birth_date,
            'street' => $this->street,
            'number' => $this->number,
            'neighborhood' => $this->neighborhood,
            'reference' => $this->reference,
            'city_name' => $this->city_name,
            'updated_at' => $this->updated_at,
            'location_kind' => $this->location_kind,
            'commercial_group' => $this->commercial_group,
            'owner_user_id' => $this->owner_user_id,
            'sold_product' => $this->sold_product,
        ];
    }
}
