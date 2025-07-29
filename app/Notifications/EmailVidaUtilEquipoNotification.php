<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVidaUtilEquipoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $equipo;
    public $vida_restante;

    public function __construct($equipo, $vida_restante)
    {
        $this->equipo = $equipo;
        $this->vida_restante = $vida_restante;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Alerta: Vida útil baja en equipo')
            ->markdown('emails.equipo_vida_util_baja', [
                'equipo' => $this->equipo,
                'vida_restante' => $this->vida_restante,
                'usuario' => $notifiable
            ]);
    }
}
