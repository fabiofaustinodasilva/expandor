<?php

namespace App\Domains\Sales\Residents\Services;

use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Residents\Enums\ResidentHistoryEvent;
use App\Domains\Sales\Residents\Enums\ResidentStatus;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Models\ResidentHistory;
use Illuminate\Support\Facades\DB;

class ResidentService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Property $property, array $data): Resident
    {
        return DB::transaction(function () use ($property, $data) {
            if (! empty($data['is_primary_contact'])) {
                $this->clearPrimaryContact($property);
            }

            $resident = Resident::query()->create([
                'property_id' => $property->id,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'email' => $data['email'] ?? null,
                'document' => $data['document'] ?? null,
                'is_primary_contact' => (bool) ($data['is_primary_contact'] ?? false),
                'status' => ResidentStatus::from($data['status'] ?? ResidentStatus::ACTIVE->value),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->recordHistory(
                $resident,
                ResidentHistoryEvent::CREATED,
                'Morador cadastrado no imóvel.'
            );

            return $resident;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Resident $resident, array $data): Resident
    {
        return DB::transaction(function () use ($resident, $data) {
            if (! empty($data['is_primary_contact'])) {
                $this->clearPrimaryContact($resident->property, $resident->id);
            }

            $resident->update([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'email' => $data['email'] ?? null,
                'document' => $data['document'] ?? null,
                'is_primary_contact' => (bool) ($data['is_primary_contact'] ?? false),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->recordHistory(
                $resident->refresh(),
                ResidentHistoryEvent::UPDATED,
                $data['history_description'] ?? 'Dados do morador atualizados.'
            );

            return $resident;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function changeStatus(Resident $resident, array $data): Resident
    {
        return DB::transaction(function () use ($resident, $data) {
            $newStatus = ResidentStatus::from($data['status']);
            $oldStatus = $resident->status;

            if ($oldStatus === $newStatus) {
                return $resident;
            }

            $resident->update([
                'status' => $newStatus,
                'notes' => $data['notes'] ?? $resident->notes,
            ]);

            $description = $data['description']
                ?? sprintf('Status alterado de %s para %s.', $oldStatus->label(), $newStatus->label());

            $this->recordHistory(
                $resident->refresh(),
                ResidentHistoryEvent::STATUS_CHANGED,
                $description
            );

            return $resident;
        });
    }

    /**
     * Upsert do contato principal a partir dos dados da finalização de venda.
     *
     * @param  array{
     *     name?: string|null,
     *     phone?: string|null,
     *     whatsapp?: string|null,
     *     email?: string|null,
     *     document?: string|null
     * }  $data
     */
    public function upsertPrimaryContact(Property $property, array $data): ?Resident
    {
        $name = isset($data['name']) ? trim((string) $data['name']) : '';
        $phone = isset($data['phone']) ? trim((string) $data['phone']) : '';
        $whatsapp = isset($data['whatsapp']) ? trim((string) $data['whatsapp']) : '';
        $email = isset($data['email']) ? trim((string) $data['email']) : '';
        $document = isset($data['document']) ? trim((string) $data['document']) : '';

        $hasAny = $name !== '' || $phone !== '' || $whatsapp !== '' || $email !== '' || $document !== '';
        if (! $hasAny) {
            return Resident::query()
                ->where('property_id', $property->id)
                ->where('is_primary_contact', true)
                ->first();
        }

        /** @var Resident|null $primary */
        $primary = Resident::query()
            ->where('property_id', $property->id)
            ->where('is_primary_contact', true)
            ->first();

        if ($primary === null) {
            $primary = Resident::query()
                ->where('property_id', $property->id)
                ->orderByDesc('id')
                ->first();
        }

        $payload = [
            'name' => $name !== '' ? $name : ($primary?->name ?? 'Cliente'),
            'phone' => $phone !== '' ? $phone : $primary?->phone,
            'whatsapp' => $whatsapp !== '' ? $whatsapp : $primary?->whatsapp,
            'email' => $email !== '' ? $email : $primary?->email,
            'document' => $document !== '' ? $document : $primary?->document,
            'is_primary_contact' => true,
            'status' => ResidentStatus::ACTIVE->value,
        ];

        if ($primary !== null) {
            return $this->update($primary, $payload);
        }

        return $this->create($property, $payload);
    }

    protected function clearPrimaryContact(Property $property, ?int $exceptResidentId = null): void
    {
        Resident::query()
            ->where('property_id', $property->id)
            ->when($exceptResidentId, fn ($q) => $q->where('id', '!=', $exceptResidentId))
            ->update(['is_primary_contact' => false]);
    }

    protected function recordHistory(
        Resident $resident,
        ResidentHistoryEvent $event,
        ?string $description = null
    ): ResidentHistory {
        return ResidentHistory::query()->create([
            'resident_id' => $resident->id,
            'property_id' => $resident->property_id,
            'event' => $event->value,
            'description' => $description,
            'created_at' => now(),
        ]);
    }
}
