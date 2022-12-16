<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Tests;

use NawrasBukhari\Updater\UpdaterFacade;
use NawrasBukhari\Updater\UpdaterManager;

final class UpdaterFacadeTest extends TestCase
{
    /** @test */
    public function it_can_use_the_facade(): void
    {
        $this->assertInstanceOf(
            UpdaterManager::class,
            UpdaterFacade::getFacadeRoot()
        );
    }
}
