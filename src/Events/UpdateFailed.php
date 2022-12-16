<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Events;

use NawrasBukhari\Updater\Models\Release;

class UpdateFailed
{
    protected Release $release;

    public function __construct(Release $release)
    {
        $this->release = $release;
    }
}
