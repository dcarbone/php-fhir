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

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * The set of profile classes generated for one profile package of one FHIR version.
 */
class ProfileClasses implements Countable, IteratorAggregate
{
    /** @var array<string, ProfileClass> profile url => class */
    private array $_byUrl = [];

    /**
     * @param string $profilesNamespace Namespace shared by everything profile related for the version, without leading slash.
     * @param string $packageNamespace Namespace, relative to $profilesNamespace, the package's classes are generated into.
     * @param callable(string): (null|string) $baseTypeResolver Maps a FHIR type name to the fully qualified name, without
     *                                                        leading slash, of the generated class for it.
     * @param array<string, ProfileClass> $dependencyClasses Already generated classes of
     *        dependency packages, by profile url, that may be used as parents.
     * @param null|ProfileTypeNavigator $navigator Shape of the generated types.  Without it, the
     *        classes are generated without any validation code.
     * @throws ProfileDefinitionException
     */
    public static function build(
        ProfileSet        $set,
        string            $profilesNamespace,
        string            $packageNamespace,
        callable          $baseTypeResolver,
        null|ValueSetExpander $expander = null,
        array             $dependencyClasses = [],
        null|ProfileTypeNavigator $navigator = null,
    ): self
    {
        $wanted = [];
        foreach ($set->getProfiles() as $profile) {
            // extension definitions are used to resolve extension slices, not as classes of their own.
            if ('Extension' !== $profile->getType()) {
                $wanted[$profile->getUrl()] = $profile;
            }
        }
        uasort($wanted, static fn(Profile $a, Profile $b): int => count($a->getAncestors()) <=> count($b->getAncestors()));

        // profiles sharing a name (it is not unique within a package) are named after their ids instead.
        $nameCounts = [];
        foreach ($wanted as $profile) {
            $key = strtolower(ProfileClass::sanitizeClassName($profile->getName()));
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
        }

        $classes = new self();
        $names = [];
        foreach ($wanted as $url => $profile) {
            $className = ProfileClass::sanitizeClassName($profile->getName());
            if ($nameCounts[strtolower($className)] > 1) {
                $className = ProfileClass::sanitizeClassName($profile->getId());
            }
            $key = strtolower($className);
            if (isset($names[$key])) {
                throw new ProfileDefinitionException(sprintf(
                    'Profiles "%s" and "%s" both produce class name "%s"',
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
                $parent = $classes->_byUrl[$ancestor->getUrl()] ?? $dependencyClasses[$ancestor->getUrl()] ?? null;
                break;
            }

            $classes->_byUrl[$url] = new ProfileClass(
                $profile,
                $profilesNamespace . '\\' . $packageNamespace,
                $className,
                ltrim($baseClass, '\\'),
                $parent,
                $profilesNamespace,
                ProfileRules::compile($profile, $expander),
                $navigator,
                $set->getPackageInfo(),
            );
        }
        return $classes;
    }

    public function getByUrl(string $url): null|ProfileClass
    {
        return $this->_byUrl[$url] ?? null;
    }

    /** @return Traversable<string, ProfileClass> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->_byUrl);
    }

    public function count(): int
    {
        return count($this->_byUrl);
    }
}
