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
 * The profiles of a package, and of its loaded dependencies, with parent profiles resolved.
 */
class ProfileSet
{
    private const _CORE_CANONICAL_PREFIX = 'http://hl7.org/fhir/StructureDefinition/';
    private const _CORE_PACKAGE_PATTERN = '{^hl7\.fhir\.r\d+[a-z]?\.core$}';

    /** @var array<string, Profile> canonical url => profile */
    private array $_byUrl = [];
    /** @var string[] urls of profiles that belong to the package itself, in package order */
    private array $_ownUrls = [];
    /** @var array<string, string> profile id => base definition that could not be resolved */
    private array $_unresolved = [];
    /** @var array<string, string> name, version and, if the manifest has them, license and author of the package */
    private array $_packageInfo = [];

    private function __construct()
    {
    }

    /**
     * @param bool $strict If true, a profile whose base profile cannot be found raises an exception.  Otherwise the
     *                     profile is left without a parent and the problem is recorded, see {@see getUnresolved()}.
     * @throws ProfileDefinitionException
     */
    public static function fromPackage(ProfilePackage $package, bool $strict = true): self
    {
        $set = new self();
        $manifest = $package->getManifest();
        $set->_packageInfo = ['name' => $package->getName(), 'version' => $package->getVersion()];
        foreach (['license', 'author'] as $key) {
            if (isset($manifest[$key]) && is_string($manifest[$key]) && '' !== trim($manifest[$key])) {
                $set->_packageInfo[$key] = trim($manifest[$key]);
            }
        }

        $dependencies = self::_collectDependencies($package);

        // dependencies first so own profiles win a url collision.
        foreach ($dependencies as $dependency) {
            foreach ($dependency->getStructureDefinitions() as $sd) {
                $profile = Profile::fromStructureDefinition($sd);
                $set->_byUrl[$profile->getUrl()] = $profile;
            }
        }

        foreach ($package->getStructureDefinitions() as $sd) {
            $profile = Profile::fromStructureDefinition($sd);
            $set->_byUrl[$profile->getUrl()] = $profile;
            $set->_ownUrls[] = $profile->getUrl();
        }

        $own = [];
        foreach ($set->_ownUrls as $url) {
            $own[$url] = true;
        }
        $unconfigured = self::_collectUnconfiguredDependencies($package, $dependencies);
        foreach ($set->_byUrl as $url => $profile) {
            // a dependency's profiles were checked, and their problems reported, when the dependency itself was built.
            if (isset($own[$url])) {
                $set->_resolveParent($profile, $strict, $unconfigured);
            } else {
                $set->_linkParent($profile);
            }
        }
        foreach ($set->_byUrl as $profile) {
            $set->_assertAcyclic($profile);
        }

        return $set;
    }

    /** @return array<string, string> */
    public function getPackageInfo(): array
    {
        return $this->_packageInfo;
    }

    /**
     * @param array<string, ProfilePackage> $dependencies Every package the package builds on.
     * @return array<string, string> Declared dependencies, as name => version, that were not loaded.
     */
    private static function _collectUnconfiguredDependencies(ProfilePackage $package, array $dependencies): array
    {
        $out = [];
        foreach ([$package, ...array_values($dependencies)] as $p) {
            foreach ($p->getDeclaredDependencies() as $name => $version) {
                if ($name !== $package->getName() && !isset($dependencies[$name])) {
                    $out[$name] = (string)$version;
                }
            }
        }
        return $out;
    }

    /**
     * Every package the package builds on, directly or not, each once.
     *
     * @return array<string, ProfilePackage> name => package
     */
    private static function _collectDependencies(ProfilePackage $package, array &$out = []): array
    {
        foreach ($package->getLoadedDependencies() as $name => $dependency) {
            if (!isset($out[$name])) {
                $out[$name] = $dependency;
                self::_collectDependencies($dependency, $out);
            }
        }
        return $out;
    }

    private function _linkParent(Profile $profile): void
    {
        if (!$profile->isBasedOnBaseType() && null !== ($parent = $this->getProfileByUrl($profile->getBaseDefinition()))) {
            $profile->setParent($parent);
        }
    }

    /**
     * @param array<string, string> $unconfigured Declared package dependencies that were not provided.
     */
    private function _resolveParent(Profile $profile, bool $strict, array $unconfigured = []): void
    {
        if ($profile->isBasedOnBaseType()) {
            return;
        }
        $parent = $this->getProfileByUrl($profile->getBaseDefinition());
        if (null !== $parent) {
            $profile->setParent($parent);
            return;
        }
        if ($strict) {
            $hint = '';
            // the base types cover the core specification's resources and data types, but not the profiles it defines,
            // e.g. vitalsigns, so the core package is only suggested when the base is one of those.
            $coreBase = str_starts_with($profile->getBaseDefinition(), self::_CORE_CANONICAL_PREFIX);
            $names = [];
            foreach ($unconfigured as $name => $version) {
                if ($coreBase || 1 !== preg_match(self::_CORE_PACKAGE_PATTERN, (string)$name)) {
                    $names[] = '' === $version ? $name : sprintf('%s (%s)', $name, $version);
                }
            }
            if ([] !== $names) {
                $hint = sprintf(
                    '  The package declares dependencies that were not configured: %s.  Configure them as profile packages and list their names under "dependencies", or set "strict" to false to have such profiles extend their base type directly.',
                    implode(', ', $names),
                );
            }
            throw new ProfileDefinitionException(sprintf(
                'Unable to locate base definition "%s" of profile "%s".  If it lives in another package, configure that package and add its name to the "dependencies" of the profile package config.%s',
                $profile->getBaseDefinition(),
                $profile->getId(),
                $hint,
            ));
        }
        $this->_unresolved[$profile->getId()] = $profile->getBaseDefinition();
    }

    private function _assertAcyclic(Profile $profile): void
    {
        $seen = [$profile->getUrl() => true];
        for ($p = $profile->getParent(); null !== $p; $p = $p->getParent()) {
            if (isset($seen[$p->getUrl()])) {
                throw new ProfileDefinitionException(sprintf(
                    'Profile "%s" has a circular base definition chain through "%s".',
                    $profile->getId(),
                    $p->getId(),
                ));
            }
            $seen[$p->getUrl()] = true;
        }
    }

    /**
     * @param string $canonical Canonical url, optionally with a "|version" suffix.
     */
    public function getProfileByUrl(string $canonical): null|Profile
    {
        return $this->_byUrl[ProfilePackage::stripCanonicalVersion($canonical)] ?? null;
    }

    public function hasProfile(string $canonical): bool
    {
        return null !== $this->getProfileByUrl($canonical);
    }

    /**
     * Profiles that belong to the package itself, excluding those from dependencies.
     *
     * @return Profile[]
     */
    public function getProfiles(): array
    {
        $out = [];
        foreach ($this->_ownUrls as $url) {
            $out[] = $this->_byUrl[$url];
        }
        return $out;
    }

    /**
     * Profile id => base definition for profiles whose base profile could not be found.  Only populated when not strict.
     *
     * @return array<string, string>
     */
    public function getUnresolved(): array
    {
        return $this->_unresolved;
    }
}
