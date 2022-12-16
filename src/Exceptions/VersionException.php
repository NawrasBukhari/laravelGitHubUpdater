<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Exceptions;

final class VersionException extends \Exception
{
    public static function versionInstalledNotFound(): self
    {
        return new self('Version installed not found.');
    }
}
