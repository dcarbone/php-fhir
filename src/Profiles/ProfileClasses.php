<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Profiles;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * The set of profile classes generated for one profile package of one FHIR version.
 */
class ProfileClasses implements \Countable, \IteratorAggregate
{
    /** @var array<string, \DCarbone\PHPFHIR\Profiles\ProfileClass> profile url => class */
    private array $_byUrl = [];

    /**
     * @param string $namespace Namespace the classes are generated into, without leading slash.
     * @param callable(string): (null|string) $baseTypeResolver Maps a FHIR type name to the fully qualified name, without
     *                                                        leading slash, of the generated class for it.
     * @throws \DCarbone\PHPFHIR\Profiles\ProfileDefinitionException
     */
    public static function build(ProfileSet $set, string $namespace, callable $baseTypeResolver): self
    {
        $own = [];
        foreach ($set->getProfiles() as $profile) {
            $own[$profile->getUrl()] = $profile;
        }

        // selected profiles, plus any own ancestors they need in order to extend them.
        $wanted = [];
        foreach ($set->getSelectedProfiles() as $profile) {
            $wanted[$profile->getUrl()] = $profile;
            foreach ($profile->getAncestors() as $ancestor) {
                if (isset($own[$ancestor->getUrl()])) {
                    $wanted[$ancestor->getUrl()] = $ancestor;
                }
            }
        }
        uasort($wanted, static fn(Profile $a, Profile $b): int => count($a->getAncestors()) <=> count($b->getAncestors()));

        $classes = new self();
        $names = [];
        foreach ($wanted as $url => $profile) {
            $className = ProfileClass::sanitizeClassName($profile->getName());
            $key = strtolower($className);
            if (isset($names[$key])) {
                throw new ProfileDefinitionException(sprintf(
                    'Profiles "%s" and "%s" both produce class name "%s"; narrow the package include / exclude filters',
                    $names[$key],
                    $profile->getId(),
                    $className,
                ));
            }
            $names[$key] = $profile->getId();

            $baseClass = $baseTypeResolver($profile->getType());
            if (null === $baseClass) {
                throw new ProfileDefinitionException(sprintf(
                    'Profile "%s" constrains type "%s", which was not generated for this FHIR version',
                    $profile->getId(),
                    $profile->getType(),
                ));
            }

            $parent = null;
            foreach ($profile->getAncestors() as $ancestor) {
                if (isset($classes->_byUrl[$ancestor->getUrl()])) {
                    $parent = $classes->_byUrl[$ancestor->getUrl()];
                }
                break;
            }

            $classes->_byUrl[$url] = new ProfileClass($profile, $namespace, $className, ltrim($baseClass, '\\'), $parent);
        }
        return $classes;
    }

    public function getByUrl(string $url): null|ProfileClass
    {
        return $this->_byUrl[$url] ?? null;
    }

    /** @return \Traversable<string, \DCarbone\PHPFHIR\Profiles\ProfileClass> */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->_byUrl);
    }

    public function count(): int
    {
        return count($this->_byUrl);
    }

    /** @return array<string, string> profile url => fully qualified class name without leading slash */
    public function toClassMap(): array
    {
        return array_map(static fn(ProfileClass $c): string => $c->getFullyQualifiedClassName(false), $this->_byUrl);
    }
}
