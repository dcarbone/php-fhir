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
use DCarbone\PHPFHIR\Utilities\NameUtils;
use DCarbone\PHPFHIR\Version\Definition\Type;
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
     * @param callable(string): (null|Type) $baseTypeResolver Maps a FHIR type name to its generated type.  A profile
     *        class is generated beneath its package's namespace at the same relative path its base type's class has
     *        beneath the types namespace.
     * @param array<string, ProfileClass> $dependencyClasses Already generated classes of
     *        dependency packages, by profile url, that may be used as parents.
     * @param null|ProfileTypeNavigator $navigator Shape of the generated types.  Without it, the
     *        classes are generated without any validation code.
     * @param null|ProfileSupportClasses $support Core classes the generated classes use.
     * @throws ProfileDefinitionException
     * @throws \DomainException If no valid class name can be derived for a profile.
     */
    public static function build(
        ProfileSet        $set,
        string            $profilesNamespace,
        string            $packageNamespace,
        callable          $baseTypeResolver,
        null|ValueSetExpander $expander = null,
        array             $dependencyClasses = [],
        null|ProfileTypeNavigator $navigator = null,
        null|ProfileSupportClasses $support = null,
    ): self
    {
        $wanted = [];
        $depths = [];
        foreach ($set->getProfiles() as $profile) {
            // extension definitions are used to resolve extension slices, not as classes of their own.
            if ('Extension' !== $profile->getType()) {
                $wanted[$profile->getUrl()] = $profile;
                $depths[$profile->getUrl()] = count($profile->getAncestors());
            }
        }
        // parents must be built before their children.
        uksort($wanted, static fn(string $a, string $b): int => $depths[$a] <=> $depths[$b]);

        // profiles sharing a name (it is not unique within a package) are named after their ids instead.
        $nameCounts = [];
        foreach ($wanted as $profile) {
            $key = strtolower(NameUtils::sanitizeClassName($profile->getName(), 'Profile'));
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
        }

        $classes = new self();
        $names = [];
        foreach ($wanted as $url => $profile) {
            $className = NameUtils::sanitizeClassName($profile->getName(), 'Profile');
            if ($nameCounts[strtolower($className)] > 1) {
                $className = NameUtils::sanitizeClassName($profile->getId(), 'Profile');
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

            $baseType = $baseTypeResolver($profile->getType());
            if (null === $baseType) {
                throw new ProfileDefinitionException(sprintf(
                    'Profile "%s" constrains type "%s", which was not generated for this FHIR version',
                    $profile->getId(),
                    $profile->getType(),
                ));
            }

            // nearest ancestor that has a class; a profile may derive from one that is not generated.
            $parent = null;
            foreach ($profile->getAncestors() as $ancestor) {
                $parent = $classes->_byUrl[$ancestor->getUrl()] ?? $dependencyClasses[$ancestor->getUrl()] ?? null;
                if (null !== $parent) {
                    break;
                }
            }

            $packageFQN = $profilesNamespace . PHPFHIR_NAMESPACE_SEPARATOR . $packageNamespace;
            $typeNamespace = $baseType->getNamespace();
            $classes->_byUrl[$url] = new ProfileClass(
                $profile,
                '' === $typeNamespace ? $packageFQN : $packageFQN . PHPFHIR_NAMESPACE_SEPARATOR . $typeNamespace,
                $className,
                $baseType->getFullyQualifiedClassName(false),
                $parent,
                $profilesNamespace,
                ProfileRulesExtractor::extract($profile, $expander),
                $navigator,
                $set->getPackageMetadata(),
                $support,
                $packageFQN,
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
