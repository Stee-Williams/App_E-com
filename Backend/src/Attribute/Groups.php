<?php

namespace App\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY)]
class Groups
{
    /** @param string[] $groups */
    public function __construct(private array $groups)
    {
    }

    /** @return string[] */
    public function getGroups(): array
    {
        return $this->groups;
    }
}
