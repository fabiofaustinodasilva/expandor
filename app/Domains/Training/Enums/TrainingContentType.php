<?php

namespace App\Domains\Training\Enums;

enum TrainingContentType: string
{
    case VIDEO = 'video';
    case DOCUMENT = 'document';
    case TEXT = 'text';

    public function label(): string
    {
        return match ($this) {
            self::VIDEO => 'Vídeo',
            self::DOCUMENT => 'Documento',
            self::TEXT => 'Texto',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
