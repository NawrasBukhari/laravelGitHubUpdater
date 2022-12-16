<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Tests\Notifications;

use Illuminate\Support\Facades\Notification;
use NawrasBukhari\Updater\Events\UpdateFailed;
use NawrasBukhari\Updater\Models\Release;
use NawrasBukhari\Updater\Notifications\Notifiable;
use NawrasBukhari\Updater\Notifications\Notifications\UpdateFailed as UpdateFailedNotification;
use NawrasBukhari\Updater\Tests\TestCase;

class EventHandlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    /** @test */
    public function it_will_send_a_notification_by_default_when_update_failed(): void
    {
        $this->fireUpdateFailedEvent();

        Notification::assertSentTo(new Notifiable(), UpdateFailedNotification::class);
    }

    /**
     * @test
     *
     * @dataProvider channelProvider
     *
     * @param  array<array<string>>  $expectedChannels
     */
    public function it_will_send_a_notification_via_the_configured_notification_channels(array $expectedChannels): void
    {
        config()->set('self-update.notifications.notifications.'.UpdateFailedNotification::class, $expectedChannels);

        $this->fireUpdateFailedEvent();

        Notification::assertSentTo(new Notifiable(), UpdateFailedNotification::class, function ($notification, $usedChannels) use ($expectedChannels) {
            return $expectedChannels == $usedChannels;
        });
    }

    /**
     * @return array<array<string[]>>
     */
    public function channelProvider(): array
    {
        return [
            [[]],
            [['mail']],
        ];
    }

    protected function fireUpdateFailedEvent(): void
    {
        $release = resolve(Release::class);

        event(new UpdateFailed($release));
    }
}
