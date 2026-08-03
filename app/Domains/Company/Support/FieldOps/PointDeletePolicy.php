<?php

namespace App\Domains\Company\Support\FieldOps;

/**
 * Quem pode excluir pontos no mapa.
 */
enum PointDeletePolicy: string
{
    case Nobody = 'nobody';
    case Creator = 'creator';
    case Supervisor = 'supervisor';
    case Manager = 'manager';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Nobody => 'Ninguém',
            self::Creator => 'Apenas o criador',
            self::Supervisor => 'Supervisor',
            self::Manager => 'Gerente',
            self::Administrator => 'Administrador',
        };
    }

    /**
     * @return list<self>
     */
    public static function casesOrdered(): array
    {
        return [
            self::Nobody,
            self::Creator,
            self::Supervisor,
            self::Manager,
            self::Administrator,
        ];
    }
}
