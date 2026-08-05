<?php

namespace App\Console\Commands;

use App\Domains\Payments\Actions\ProcessMercadoPagoPaymentAction;
use Illuminate\Console\Command;

class MercadoPagoTestPaymentCommand extends Command
{
    protected $signature = 'mercadopago:test-payment {payment_id : ID do pagamento no Mercado Pago}';

    protected $description = 'Consulta um pagamento no Mercado Pago e atualiza/provisiona o checkout correspondente';

    public function handle(ProcessMercadoPagoPaymentAction $action): int
    {
        $paymentId = (string) $this->argument('payment_id');

        $this->info("Consultando pagamento {$paymentId}…");

        try {
            $result = $action->execute($paymentId);
        } catch (\Throwable $e) {
            $this->error('Falha: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Campo', 'Valor'],
            [
                ['status', $result['status']],
                ['provisioned', $result['provisioned'] ? 'yes' : 'no'],
                ['company_id', $result['company_id'] ?? '—'],
                ['checkout_uuid', $result['checkout_uuid'] ?? '—'],
                ['message', $result['message']],
            ]
        );

        return self::SUCCESS;
    }
}
