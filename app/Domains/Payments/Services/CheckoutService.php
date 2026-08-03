<?php

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Actions\CreateCheckoutAction;
use App\Domains\Payments\DTOs\CheckoutResult;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CheckoutService
{
    public function __construct(
        protected CreateCheckoutAction $createCheckout,
        protected PaymentRepository $repository,
    ) {}

    public function listPlans(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginateClientPlans($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function start(array $data): CheckoutResult
    {
        return $this->createCheckout->execute($data);
    }

    public function findByUuid(string $uuid): ?CheckoutSession
    {
        return $this->repository->findCheckoutByUuid($uuid);
    }
}
