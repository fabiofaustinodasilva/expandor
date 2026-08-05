<?php

namespace App\Domains\Integrity\Services;

use App\Domains\AI\Models\AIConversation;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Billing\Models\UsageRecord;
use App\Domains\Branding\Models\Brand;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Communication\Models\WhatsAppConnection;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\Team;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Models\CommissionEntry;
use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Growth\Models\MarketplaceTrialMilestone;
use App\Domains\Marketplace\Models\MarketplaceEvent;
use App\Domains\Marketplace\Revenue\Models\MarketplaceLeadScore;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;
use App\Domains\Onboarding\Models\OnboardingProgress;
use App\Domains\Onboarding\Models\OnboardingRun;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Payments\Models\PaymentMethod;
use App\Domains\Platform\Models\CompanyFeatureFlag;
use App\Domains\Platform\Models\CompanyHealthScore;
use App\Domains\Platform\Models\ImpersonationSession;
use App\Domains\Platform\Models\SubscriptionEvent;
use App\Domains\SaasGrowth\Models\CompanyUsageMetric;
use App\Domains\SaasGrowth\Models\TrialMilestoneRecord;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Models\StockMovement;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Models\PropertyHistory;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Models\ResidentHistory;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Security\Models\AnonymizationRequest;
use App\Domains\Security\Models\Consent;
use App\Domains\Security\Models\DataExportRequest;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Remove completamente uma empresa respeitando a ordem de domínio (sem SQL bruto).
 *
 * Ordem:
 * Marketplace → Onboarding → Invoices → Subscriptions → Payments →
 * Payment Gateway → Checkout → Usuários → Empresa
 * (+ CRM, Visits e demais módulos tenant antes da empresa).
 */
class CompanyPurgeService
{
    public function __construct(
        protected TenantContext $tenant,
        protected SecurityService $security,
    ) {}

    /**
     * @return array{company_id: int, company_name: string, deleted: array<string, int>}
     */
    public function purge(int $companyId, ?User $actor = null): array
    {
        $company = Company::query()->withTrashed()->find($companyId);

        if ($company === null) {
            throw ValidationException::withMessages([
                'company_id' => ["Empresa #{$companyId} não encontrada."],
            ]);
        }

        if ($company->isSystem()) {
            throw ValidationException::withMessages([
                'company_id' => ['Não é permitido excluir a empresa sistema.'],
            ]);
        }

        $previousBypass = $this->tenant->isBypassed();
        $this->tenant->bypass(true);

        $counts = [];

        try {
            $result = DB::transaction(function () use ($company, $actor, &$counts) {
                $id = (int) $company->id;
                $name = (string) $company->name;

                // 1) Marketplace
                $counts['marketplace_trial_milestones'] = $this->deleteByCompany(MarketplaceTrialMilestone::class, $id);
                $counts['marketplace_events'] = $this->deleteByCompany(MarketplaceEvent::class, $id);
                $counts['marketplace_leads'] = $this->nullifyOrDeleteMarketplaceLeads($id);
                $counts['marketplace_lead_scores'] = $this->deleteByCompany(MarketplaceLeadScore::class, $id);
                $counts['marketplace_sales_pipeline'] = $this->deleteByCompany(MarketplaceSalesPipeline::class, $id);
                $counts['trial_milestone_records'] = $this->deleteByCompany(TrialMilestoneRecord::class, $id);
                $counts['company_usage_metrics'] = $this->deleteByCompany(CompanyUsageMetric::class, $id);

                // 2) Onboarding
                $counts['onboarding_progress'] = $this->deleteByCompany(OnboardingProgress::class, $id);
                $counts['onboarding_runs'] = $this->deleteByCompany(OnboardingRun::class, $id);

                // CRM / Visits / Sales (antes de billing para FKs internas)
                $counts['commission_entries'] = $this->deleteByCompany(CommissionEntry::class, $id);
                $counts['opportunities'] = $this->deleteByCompany(Opportunity::class, $id);
                $counts['leads'] = $this->deleteByCompany(Lead::class, $id);
                $counts['pipeline_stages'] = $this->deleteByCompany(PipelineStage::class, $id);
                $counts['sales_goals'] = $this->deleteByCompany(SalesGoal::class, $id);
                $counts['commission_rules'] = $this->deleteByCompany(CommissionRule::class, $id);
                $counts['follow_ups'] = $this->deleteByCompany(FollowUp::class, $id);
                $counts['visits'] = $this->deleteByCompany(Visit::class, $id);
                $counts['campaigns'] = $this->deleteByCompany(Campaign::class, $id);

                $counts['sale_items'] = $this->deleteSaleItems($id);
                $counts['sales_commissions'] = $this->deleteByCompany(SalesCommission::class, $id);
                $counts['sales'] = $this->deleteByCompany(Sale::class, $id);
                $counts['stock_movements'] = $this->deleteByCompany(StockMovement::class, $id);
                $counts['products'] = $this->deleteByCompany(Product::class, $id);
                $counts['resident_histories'] = $this->deleteByCompany(ResidentHistory::class, $id);
                $counts['residents'] = $this->deleteByCompany(Resident::class, $id);
                $counts['property_histories'] = $this->deleteByCompany(PropertyHistory::class, $id);
                $counts['properties'] = $this->deleteByCompany(Property::class, $id);
                $counts['addresses'] = $this->deleteByCompany(Address::class, $id);
                $counts['sectors'] = $this->deleteByCompany(Sector::class, $id);
                $counts['cities'] = $this->deleteByCompany(City::class, $id);

                $counts['training_progress'] = $this->deleteByCompany(TrainingProgress::class, $id);
                $counts['training_contents'] = $this->deleteTrainingContents($id);
                $counts['training_categories'] = $this->deleteByCompany(TrainingCategory::class, $id);

                $counts['messages'] = $this->deleteByCompany(Message::class, $id);
                $counts['message_templates'] = $this->deleteByCompany(MessageTemplate::class, $id);
                $counts['whatsapp_connections'] = $this->deleteByCompany(WhatsAppConnection::class, $id);
                $counts['ai_conversations'] = $this->deleteByCompany(AIConversation::class, $id);

                $counts['consents'] = $this->deleteByCompany(Consent::class, $id);
                $counts['data_export_requests'] = $this->deleteByCompany(DataExportRequest::class, $id);
                $counts['anonymization_requests'] = $this->deleteByCompany(AnonymizationRequest::class, $id);

                $counts['company_feature_flags'] = $this->deleteByCompany(CompanyFeatureFlag::class, $id);
                $counts['company_health_scores'] = $this->deleteByCompany(CompanyHealthScore::class, $id);
                $counts['subscription_events'] = $this->deleteByCompany(SubscriptionEvent::class, $id);
                $counts['impersonation_sessions'] = $this->deleteByCompany(ImpersonationSession::class, $id, 'target_company_id');
                $counts['usage_records'] = $this->deleteByCompany(UsageRecord::class, $id);
                $counts['brands'] = $this->deleteByCompany(Brand::class, $id);
                $counts['company_settings'] = $this->deleteByCompany(CompanySetting::class, $id);

                // 3) Invoices
                $counts['invoices'] = $this->deleteByCompany(Invoice::class, $id);

                // 4) Subscriptions
                $counts['subscriptions'] = $this->deleteByCompany(Subscription::class, $id);

                // 5) Payments (+ métodos / customers)
                $paymentIds = Payment::query()->withoutGlobalScopes()
                    ->where('company_id', $id)
                    ->pluck('id')
                    ->all();

                $counts['payment_gateway_transactions_by_payment'] = $this->deleteGatewayTransactionsForPayments($paymentIds);
                $counts['payments'] = $this->deleteByCompany(Payment::class, $id);
                $counts['payment_methods'] = $this->deleteByCompany(PaymentMethod::class, $id);
                $counts['customers'] = $this->deleteByCompany(Customer::class, $id);

                // 6) Payment Gateway (restante ligado a checkout da empresa)
                $checkoutIds = CheckoutSession::query()
                    ->where('company_id', $id)
                    ->pluck('id')
                    ->all();

                $counts['payment_gateway_transactions'] = $this->deleteGatewayTransactionsForCheckouts($checkoutIds);

                // 7) Checkout
                $counts['checkout_sessions'] = CheckoutSession::query()
                    ->where('company_id', $id)
                    ->delete();

                // Teams / pivots / users
                $counts['team_user'] = $this->detachTeamUsers($id);
                $counts['teams'] = $this->deleteByCompany(Team::class, $id);

                // 8) Usuários (force delete — libera UNIQUE email)
                $counts['users'] = $this->forceDeleteUsers($id);

                // Auditoria (nullable FK)
                $counts['audit_logs'] = AuditLog::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $id)
                    ->delete();

                // 9) Empresa
                $company->forceDelete();
                $counts['companies'] = 1;

                $this->security->recordAudit(
                    action: 'integrity.company.purged',
                    user: $actor,
                    newValues: [
                        'company_id' => $id,
                        'company_name' => $name,
                        'deleted' => $counts,
                    ],
                    companyId: null,
                );

                return [
                    'company_id' => $id,
                    'company_name' => $name,
                    'deleted' => $counts,
                ];
            });
        } finally {
            $this->tenant->bypass($previousBypass);
        }

        return $result;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function deleteByCompany(string $modelClass, int $companyId, string $column = 'company_id'): int
    {
        if (! class_exists($modelClass)) {
            return 0;
        }

        /** @var Model $model */
        $model = new $modelClass;
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $query = $modelClass::query()->withoutGlobalScopes()->where($column, $companyId);

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $count = 0;
        $query->orderBy($model->getKeyName())->chunkById(100, function ($rows) use (&$count) {
            foreach ($rows as $row) {
                try {
                    if (method_exists($row, 'forceDelete')) {
                        $row->forceDelete();
                    } else {
                        $row->delete();
                    }
                    $count++;
                } catch (Throwable) {
                    // fallback seguro via query do model
                    $row->newQueryWithoutScopes()->whereKey($row->getKey())->forceDelete();
                    $count++;
                }
            }
        });

        return $count;
    }

    protected function nullifyOrDeleteMarketplaceLeads(int $companyId): int
    {
        if (! Schema::hasTable('marketplace_leads') || ! Schema::hasColumn('marketplace_leads', 'company_id')) {
            return 0;
        }

        return MarketplaceLead::query()
            ->withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->update(['company_id' => null]);
    }

    protected function deleteSaleItems(int $companyId): int
    {
        if (! Schema::hasTable('sale_items') || ! Schema::hasTable('sales')) {
            return 0;
        }

        $saleIds = Sale::query()->withoutGlobalScopes()->where('company_id', $companyId)->pluck('id');

        if ($saleIds->isEmpty()) {
            return 0;
        }

        return SaleItem::query()->withoutGlobalScopes()->whereIn('sale_id', $saleIds)->delete();
    }

    protected function deleteTrainingContents(int $companyId): int
    {
        if (! Schema::hasTable('training_contents')) {
            return 0;
        }

        if (Schema::hasColumn('training_contents', 'company_id')) {
            return $this->deleteByCompany(TrainingContent::class, $companyId);
        }

        if (! Schema::hasTable('training_categories')) {
            return 0;
        }

        $categoryIds = TrainingCategory::query()->withoutGlobalScopes()->where('company_id', $companyId)->pluck('id');

        return TrainingContent::query()->withoutGlobalScopes()->whereIn('training_category_id', $categoryIds)->delete();
    }

    /**
     * @param  list<int|string>  $paymentIds
     */
    protected function deleteGatewayTransactionsForPayments(array $paymentIds): int
    {
        if ($paymentIds === [] || ! Schema::hasTable('payment_gateway_transactions')) {
            return 0;
        }

        return PaymentGatewayTransaction::query()
            ->whereIn('payment_record_id', $paymentIds)
            ->delete();
    }

    /**
     * @param  list<int|string>  $checkoutIds
     */
    protected function deleteGatewayTransactionsForCheckouts(array $checkoutIds): int
    {
        if ($checkoutIds === [] || ! Schema::hasTable('payment_gateway_transactions')) {
            return 0;
        }

        return PaymentGatewayTransaction::query()
            ->whereIn('checkout_session_id', $checkoutIds)
            ->delete();
    }

    protected function detachTeamUsers(int $companyId): int
    {
        if (! Schema::hasTable('team_user') || ! Schema::hasTable('teams')) {
            return 0;
        }

        $teamIds = Team::query()->withoutGlobalScopes()->where('company_id', $companyId)->pluck('id');

        if ($teamIds->isEmpty()) {
            return 0;
        }

        return DB::table('team_user')->whereIn('team_id', $teamIds)->delete();
    }

    protected function forceDeleteUsers(int $companyId): int
    {
        $count = 0;

        User::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->chunkById(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    $user->forceDelete();
                    $count++;
                }
            });

        return $count;
    }
}
