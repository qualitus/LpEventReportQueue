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

namespace QU\LERQ\Model;

class ProviderModel
{
    private string $name = '';
    private string $namespace = '';
    private string $path = '';
    private bool $hasOverrides = false;
    private ?RoutinesModel $activeOverrides = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getHasOverrides(): bool
    {
        return $this->hasOverrides;
    }

    public function setHasOverrides(bool $hasOverrides): self
    {
        $this->hasOverrides = $hasOverrides;
        return $this;
    }

    public function getActiveOverrides(): RoutinesModel
    {
        return $this->activeOverrides ?? new RoutinesModel();
    }

    public function setActiveOverrides(RoutinesModel $activeOverrides): self
    {
        $this->activeOverrides = $activeOverrides;
        return $this;
    }
}
