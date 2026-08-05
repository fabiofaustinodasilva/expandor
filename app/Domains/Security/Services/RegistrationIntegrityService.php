<?php

namespace App\Domains\Security\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Blindagem de unicidade SaaS — validar SEMPRE antes de checkout/pagamento/empresa/usuário.
 */
class RegistrationIntegrityService
{
    public const EMAIL_TAKEN_MESSAGE = 'Este e-mail já possui uma conta cadastrada no Expandor. Utilize outro e-mail administrativo ou faça login.';

    public const DUPLICATE_GENERIC_MESSAGE = 'Já existe um cadastro utilizando estas informações.';

    public const DOCUMENT_TAKEN_MESSAGE = 'Já existe uma empresa cadastrada com este CPF/CNPJ.';

    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function normalizeDocument(?string $document): ?string
    {
        if ($document === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $document);

        return $digits !== null && $digits !== '' ? $digits : null;
    }

    public function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        $email = $this->normalizeEmail($email);

        $query = User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email]);

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        return $query->exists();
    }

    /**
     * @throws ValidationException
     */
    public function assertEmailAvailable(string $email, string $field = 'buyer_email', ?int $ignoreUserId = null): void
    {
        if ($this->emailExists($email, $ignoreUserId)) {
            throw ValidationException::withMessages([
                $field => [self::EMAIL_TAKEN_MESSAGE],
            ]);
        }
    }

    public function companyDocumentExists(?string $document, ?int $ignoreCompanyId = null): bool
    {
        $normalized = $this->normalizeDocument($document);

        if ($normalized === null) {
            return false;
        }

        $query = Company::query()
            ->withoutGlobalScopes()
            ->where(function ($q) use ($normalized, $document) {
                $q->where('document', $document)
                    ->orWhere('document', $normalized)
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(document, ''), '.', ''), '/', ''), '-', ''), ' ', '') = ?",
                        [$normalized]
                    );
            });

        if ($ignoreCompanyId !== null) {
            $query->where('id', '!=', $ignoreCompanyId);
        }

        return $query->exists();
    }

    /**
     * @throws ValidationException
     */
    public function assertCompanyDocumentAvailable(?string $document, string $field = 'buyer_document', ?int $ignoreCompanyId = null): void
    {
        if ($document === null || trim($document) === '') {
            return;
        }

        if ($this->companyDocumentExists($document, $ignoreCompanyId)) {
            throw ValidationException::withMessages([
                $field => [self::DOCUMENT_TAKEN_MESSAGE],
            ]);
        }
    }

    /**
     * Ordem obrigatória: 1) e-mail 2) CPF/CNPJ.
     *
     * @throws ValidationException
     */
    public function assertRegistrationIdentityAvailable(
        string $email,
        ?string $document = null,
        string $emailField = 'buyer_email',
        string $documentField = 'buyer_document',
        ?int $ignoreUserId = null,
        ?int $ignoreCompanyId = null,
    ): void {
        $this->assertEmailAvailable($email, $emailField, $ignoreUserId);
        $this->assertCompanyDocumentAvailable($document, $documentField, $ignoreCompanyId);
    }

    /**
     * Alias de checkout público.
     *
     * @throws ValidationException
     */
    public function assertCheckoutIdentityAvailable(string $email, ?string $document = null): void
    {
        $this->assertRegistrationIdentityAvailable($email, $document, 'buyer_email', 'buyer_document');
    }
}
