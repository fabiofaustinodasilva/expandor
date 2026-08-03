<?php

namespace App\Domains\Sales\SaleFields;

/**
 * Catálogo de campos configuráveis na finalização de venda.
 * Produto = ao menos 1 item no carrinho. Total é calculado (não digitado).
 */
final class SaleFieldKeys
{
    public const NAME = 'name';

    public const PHONE = 'phone';

    public const WHATSAPP = 'whatsapp';

    public const DOCUMENT = 'document';

    public const RG = 'rg';

    public const EMAIL = 'email';

    public const PRODUCT = 'product';

    public const NOTES = 'notes';

    public const SETTINGS_KEY = 'sale.required_fields';

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        return [
            self::NAME,
            self::PHONE,
            self::PRODUCT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::NAME,
            self::PHONE,
            self::WHATSAPP,
            self::DOCUMENT,
            self::RG,
            self::EMAIL,
            self::PRODUCT,
            self::NOTES,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::NAME => 'Nome',
            self::PHONE => 'Telefone',
            self::WHATSAPP => 'WhatsApp',
            self::DOCUMENT => 'CPF',
            self::RG => 'RG',
            self::EMAIL => 'E-mail',
            self::PRODUCT => 'Produto(s)',
            self::NOTES => 'Observações',
        ];
    }
}
