<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Notifications\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use NawrasBukhari\Updater\Events\UpdateFailed as UpdateFailedEvent;
use NawrasBukhari\Updater\Notifications\BaseNotification;

final class UpdateFailed extends BaseNotification
{
    protected UpdateFailedEvent $event;

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->from(config('self-update.notifications.mail.from.address', config('mail.from.address')), config('self-update.notifications.mail.from.name', config('mail.from.name')))
            ->subject(config('app.name').': Update failed');
    }

    public function setEvent(UpdateFailedEvent $event): self
    {
        $this->event = $event;

        return $this;
    }
}
