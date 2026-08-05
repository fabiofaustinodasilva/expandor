<?php

namespace App\Domains\Security\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Regras de unicidade para cadastro SaaS multiempresa.
 */
class RegistrationIntegrityService
{
    public const EMAIL_TAKEN_MESSAGE = 'Este e-mail já possui uma conta cadastrada no Expandor. Utilize outro e-mail administrativo ou acesse sua conta existente.';

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

    public function emailExists(string $email): bool
    {
        $email = $this->normalizeEmail($email);

        return User::query()
            ->withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();
    }

    /**
     * @throws ValidationException
     */
    public function assertEmailAvailable(string $email, string $field = 'buyer_email'): void
    {
        if ($this->emailExists($email)) {
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
    public function assertCompanyDocumentAvailable(?string $document, string $field = 'buyer_document'): void
    {
        if ($document === null || trim($document) === '') {
            return;
        }

        if ($this->companyDocumentExists($document)) {
            throw ValidationException::withMessages([
                $field => [self::DOCUMENT_TAKEN_MESSAGE],
            ]);
        }
    }

    /**
     * Valida pré-checkout: e-mail e documento.
     *
     * @throws ValidationException
     */
    public function assertCheckoutIdentityAvailable(string $email, ?string $document = null): void
    {
        $this->assertEmailAvailable($email, 'buyer_email');
        $this->assertCompanyDocumentAvailable($document, 'buyer_document');
    }
}
