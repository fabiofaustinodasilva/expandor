<?php

namespace App\Domains\Integrity\DTOs;

/**
 * Relatório estruturado do scanner de integridade SaaS.
 */
class IntegrityReport
{
    /**
     * @param  list<array{email: string, user_ids: list<int>, company_ids: list<int>, companies: list<array{id: int, name: string}>}>  $duplicatedEmails
     * @param  list<array{document: string, company_ids: list<int>, companies: list<array{id: int, name: string}>}>  $duplicatedDocuments
     * @param  list<array{id: int, name: string, status: string, deleted_at: ?string}>  $softDeletedCompanies
     * @param  list<array{id: int, name: string, status: string}>  $cancelledCompanies
     * @param  array<string, int>  $orphanCounts
     * @param  list<array{id: int, name: string, reasons: list<string>}>  $companiesRecommendedForPurge
     * @param  list<string>  $notes
     */
    public function __construct(
        public array $duplicatedEmails = [],
        public array $duplicatedDocuments = [],
        public array $softDeletedCompanies = [],
        public array $cancelledCompanies = [],
        public array $orphanCounts = [],
        public array $companiesRecommendedForPurge = [],
        public array $notes = [],
        public bool $uniqueEmailIndexPresent = false,
        public bool $uniqueDocumentIndexPresent = false,
    ) {}

    public function hasIssues(): bool
    {
        if ($this->duplicatedEmails !== [] || $this->duplicatedDocuments !== []) {
            return true;
        }

        if ($this->softDeletedCompanies !== [] || $this->cancelledCompanies !== []) {
            return true;
        }

        foreach ($this->orphanCounts as $count) {
            if ($count > 0) {
                return true;
            }
        }

        return ! $this->uniqueEmailIndexPresent || ! $this->uniqueDocumentIndexPresent;
    }

    /**
     * @return list<int>
     */
    public function companyIdsInvolvedInDuplicates(): array
    {
        $ids = [];

        foreach ($this->duplicatedEmails as $group) {
            foreach ($group['company_ids'] as $id) {
                $ids[] = (int) $id;
            }
        }

        foreach ($this->duplicatedDocuments as $group) {
            foreach ($group['company_ids'] as $id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'duplicated_emails' => $this->duplicatedEmails,
            'duplicated_documents' => $this->duplicatedDocuments,
            'soft_deleted_companies' => $this->softDeletedCompanies,
            'cancelled_companies' => $this->cancelledCompanies,
            'orphan_counts' => $this->orphanCounts,
            'companies_recommended_for_purge' => $this->companiesRecommendedForPurge,
            'notes' => $this->notes,
            'unique_email_index_present' => $this->uniqueEmailIndexPresent,
            'unique_document_index_present' => $this->uniqueDocumentIndexPresent,
            'has_issues' => $this->hasIssues(),
        ];
    }
}
