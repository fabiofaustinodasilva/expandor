<?php

namespace App\Domains\Communication\Repositories;

use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Communication\Models\WhatsAppConnection;
use App\Domains\Sales\Residents\Models\Resident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MessageRepository
{
    public function paginate(?int $residentId = null, int $perPage = 20): LengthAwarePaginator
    {
        return Message::query()
            ->with(['resident:id,name,phone', 'user:id,name'])
            ->when($residentId, fn ($q) => $q->where('resident_id', $residentId))
            ->latest('id')
            ->paginate($perPage);
    }

    public function historyForResident(Resident $resident, int $perPage = 50): LengthAwarePaginator
    {
        return Message::query()
            ->where('resident_id', $resident->id)
            ->with('user:id,name')
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateTemplates(int $perPage = 15): LengthAwarePaginator
    {
        return MessageTemplate::query()
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, MessageTemplate>
     */
    public function activeTemplates(): Collection
    {
        return MessageTemplate::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    public function connectionForCompany(): ?WhatsAppConnection
    {
        return WhatsAppConnection::query()->first();
    }
}
