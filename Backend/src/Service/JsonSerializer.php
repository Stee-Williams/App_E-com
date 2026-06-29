<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Doctrine\Common\Collections\Collection;
use App\Attribute\Groups;

class JsonSerializer
{
    public function serialize(mixed $data, array $groups): string
    {
        return json_encode($this->normalize($data, $groups), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function normalize(mixed $data, array $groups): mixed
    {
        if ($data === null) {
            return null;
        }

        if (is_array($data)) {
            return array_map(fn ($item) => $this->normalize($item, $groups), $data);
        }

        if ($data instanceof Collection) {
            return $this->normalize($data->toArray(), $groups);
        }

        if ($data instanceof \DateTimeInterface) {
            return $data->format(\DateTimeInterface::ATOM);
        }

        if (is_scalar($data)) {
            return $data;
        }

        if (!is_object($data)) {
            return $data;
        }

        $reflection = new \ReflectionClass($data);
        $result = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (
                $method->getNumberOfRequiredParameters() > 0
                || $method->isStatic()
                || (!str_starts_with($name, 'get') && !str_starts_with($name, 'is'))
                || $name === 'getPassword'
                || $name === 'getMotDePasse'
                || $name === 'getUserIdentifier'
                || $name === 'getJetonApi'
                || $name === 'getJetonReinitialisation'
                || $name === 'getExpirationJetonReinitialisation'
            ) {
                continue;
            }

            $methodGroups = $this->extractGroups($method);

            $propertyName = str_starts_with($name, 'is')
                ? lcfirst(substr($name, 2))
                : lcfirst(substr($name, 3));

            $propertyGroups = [];
            if ($reflection->hasProperty($propertyName)) {
                $propertyGroups = $this->extractGroups($reflection->getProperty($propertyName));
            }

            if ($groups !== []) {
                $memberGroups = array_unique(array_merge($methodGroups, $propertyGroups));
                if ($memberGroups === [] || !$this->hasMatchingGroup($memberGroups, $groups)) {
                    continue;
                }
            } elseif ($methodGroups === [] && $propertyGroups === []) {
                continue;
            }

            $value = $method->invoke($data);
            $key = $propertyName;

            if (str_starts_with($name, 'is')) {
                $key = lcfirst(substr($name, 2));
            }

            $result[$key] = $this->normalize($value, $groups);
        }

        return $result;
    }

    /** @return string[] */
    private function extractGroups(\ReflectionMethod|\ReflectionProperty $member): array
    {
        $attributes = $member->getAttributes(Groups::class);
        if ($attributes === []) {
            return [];
        }

        return $attributes[0]->newInstance()->getGroups();
    }

  /** @param string[] $memberGroups */
    private function hasMatchingGroup(array $memberGroups, array $requestedGroups): bool
    {
        return array_intersect($memberGroups, $requestedGroups) !== [];
    }
}
