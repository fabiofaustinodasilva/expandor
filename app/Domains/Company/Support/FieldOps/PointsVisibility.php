<?php

namespace App\Domains\Company\Support\FieldOps;

/**
 * Política de visibilidade de pontos no mapa (nível empresa; futuro: campanha).
 */
enum PointsVisibility: string
{
    case Company = 'company';
    case Team = 'team';
    case Own = 'own';

    public function label(): string
    {
        return match ($this) {
            self::Company => 'Todos os pontos da empresa',
            self::Team => 'Apenas pontos da minha equipe',
            self::Own => 'Apenas meus pontos',
        };
    }

    /**
     * @return list<self>
     */
    public static function casesOrdered(): array
    {
        return [self::Company, self::Team, self::Own];
    }
}
