<?php

namespace Flat3\Lodata\Interfaces;

use Flat3\Lodata\EntityType;

interface EntityTypeInterface
{
    public function getType(): ?EntityType;

    public function setType(EntityType $type);
}