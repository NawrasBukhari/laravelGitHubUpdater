<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Contracts;

interface UpdaterContract
{
    /**
     * Get a source type instance.
     */
    public function source(string $name = ''): SourceRepositoryTypeContract;
}
