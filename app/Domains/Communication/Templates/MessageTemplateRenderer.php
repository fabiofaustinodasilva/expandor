<?php

namespace App\Domains\Communication\Templates;

use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Sales\Residents\Models\Resident;

class MessageTemplateRenderer
{
    /**
     * @param  array<string, string|null>  $extra
     */
    public function render(MessageTemplate $template, ?Resident $resident = null, array $extra = []): string
    {
        $replacements = array_merge([
            'nome' => $resident?->name ?? '',
            'telefone' => $resident?->phone ?? '',
            'email' => $resident?->email ?? '',
        ], $extra);

        $content = $template->content;

        foreach ($replacements as $key => $value) {
            $content = str_replace(
                ['{{'.$key.'}}', '{{ '.$key.' }}'],
                (string) $value,
                $content
            );
        }

        return $content;
    }
}
