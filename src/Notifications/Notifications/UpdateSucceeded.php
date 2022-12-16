<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Notifications\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use NawrasBukhari\Updater\Events\UpdateSucceeded as UpdateSucceededEvent;
use NawrasBukhari\Updater\Notifications\BaseNotification;

final class UpdateSucceeded extends BaseNotification
{
    protected UpdateSucceededEvent $event;

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->from(config('self-update.notifications.mail.from.address', config('mail.from.address')), config('self-update.notifications.mail.from.name', config('mail.from.name')))
            ->subject(config('app.name').': Update succeeded');
    }

    public function setEvent(UpdateSucceededEvent $event): self
    {
        $this->event = $event;

        return $this;
    }
}
