<?php

namespace App\Domains\Payments\Mail;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeCredentialsMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Company $company,
        public User $administrator,
        public string $plainPassword,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua conta Expandor está pronta',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    protected function buildHtml(): string
    {
        $name = e($this->administrator->name);
        $email = e($this->administrator->email);
        $password = e($this->plainPassword);
        $company = e($this->company->name);
        $url = e($this->loginUrl);

        return <<<HTML
        <h1>Bem-vindo ao Expandor</h1>
        <p>Olá {$name}, a empresa <strong>{$company}</strong> foi provisionada com sucesso.</p>
        <p><strong>Login:</strong> {$email}<br><strong>Senha temporária:</strong> {$password}</p>
        <p><a href="{$url}">Acessar o sistema</a></p>
        <p>Recomendamos alterar a senha após o primeiro acesso.</p>
        HTML;
    }
}
