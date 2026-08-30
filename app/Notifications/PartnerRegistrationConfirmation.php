<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Versendet den einmaligen Link zur sicheren Partnerregistrierung.
 *
 * Der Klartext-Token lebt nur in dieser Notification und der daraus erzeugten URL. Er
 * darf weder in Auditdaten noch in Anwendungslogs als separates Feld geschrieben werden.
 */
final class PartnerRegistrationConfirmation extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $intentPublicId,
        public readonly string $confirmationToken,
        public readonly int $lifetimeMinutes,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Erstellt die lokalisierte Bestätigungsnachricht ohne vertrauliche Stammdaten.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Das Fragment wird von Browsern nicht an Webserver, Proxy, WAF oder Access-Log gesendet.
        $url = route('registration.confirm.show', ['intent' => $this->intentPublicId])
            .'#token='.rawurlencode($this->confirmationToken);

        $viewData = [
            'actionUrl' => $url,
            'lifetimeMinutes' => $this->lifetimeMinutes,
        ];

        return (new MailMessage)
            ->subject(__('registration.mail.subject'))
            ->action(__('registration.mail.action'), $url)
            ->view('emails.registration.confirmation', $viewData)
            ->text('emails.registration.confirmation-text', $viewData);
    }
}
