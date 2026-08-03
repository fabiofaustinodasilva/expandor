<?php

namespace App\Domains\Company\Support\FieldOps;

/**
 * Quem pode editar pontos criados por outro vendedor.
 */
enum PointEditOthersPolicy: string
{
    case Nobody = 'nobody';
    case Supervisor = 'supervisor';
    case Manager = 'manager';
    case Administrator = 'administrator';

    public function label(): string
    {
        return match ($this) {
            self::Nobody => 'Ninguém (somente o criador)',
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
        return [self::Nobody, self::Supervisor, self::Manager, self::Administrator];
    }
}
