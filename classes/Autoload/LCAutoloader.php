<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

if (class_exists(\LCAutoloader::class)) {
    return;
}


class LCAutoloader
{
    /** @var array<string, list<string>> */
    protected array $prefixes = [];

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function addNamespace(string $prefix, string $base_dir, bool $prepend = false): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = [];
        }
        if ($prepend === true) {
            array_unshift($this->prefixes[$prefix], $base_dir);
        } else {
            $this->prefixes[$prefix][] = $base_dir;
        }
    }

    public function reqFile(string $file): bool
    {
        if (file_exists($file)) {
            require_once($file);
            return true;
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function loadMappedFile(string $prefix, string $class)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $base_dir) {
            $file = $base_dir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            if ($this->reqFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function loadClass(string $class)
    {
        $prefix = $class;
        while (false !== ($pos = strrpos($prefix, '\\'))) {
            $prefix = substr($class, 0, $pos + 1);
            $relative = substr($class, $pos + 1);
            $mapped = $this->loadMappedFile($prefix, $relative);
            if ($mapped) {
                return $mapped;
            }
            $prefix = rtrim($prefix, '\\');
        }
        return false;
    }
}
