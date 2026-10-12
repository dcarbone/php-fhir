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
    /** @var array<string, \DCarbone\PHPFHIR\Profiles\Profile> canonical url => profile */
    private array $_byUrl = [];
    /** @var string[] urls of profiles that belong to the package itself, in package order */
    private array $_ownUrls = [];
    /** @var string[] urls of own profiles that pass the package's include / exclude filters */
    private array $_selectedUrls = [];
    /** @var array<string, string> profile id => base definition that could not be resolved */
    private array $_unresolved = [];

    private function __construct()
    {
    }

    /**
     * @param bool $strict If true, a profile whose base profile cannot be found raises an exception.  Otherwise the
     *                     profile is left without a parent and the problem is recorded, see {@see getUnresolved()}.
     * @throws \DCarbone\PHPFHIR\Profiles\ProfileDefinitionException
     */
    public static function fromPackage(ProfilePackage $package, bool $strict = true): self
    {
        $set = new self();

        // dependencies first so own profiles win a url collision.
        foreach (self::_collectDependencies($package) as $dependency) {
            foreach ($dependency->getStructureDefinitions() as $sd) {
                $profile = Profile::fromStructureDefinition($sd);
                $set->_byUrl[$profile->getUrl()] = $profile;
            }
        }

        $selectedIds = array_keys($package->getSelectedStructureDefinitions());
        foreach ($package->getStructureDefinitions() as $sd) {
            $profile = Profile::fromStructureDefinition($sd);
            $set->_byUrl[$profile->getUrl()] = $profile;
            $set->_ownUrls[] = $profile->getUrl();
            if (in_array($profile->getId(), $selectedIds, true)) {
                $set->_selectedUrls[] = $profile->getUrl();
            }
        }

        foreach ($set->_byUrl as $profile) {
            $set->_resolveParent($profile, $strict);
        }
        foreach ($set->_byUrl as $profile) {
            $set->_assertAcyclic($profile);
        }

        return $set;
    }

    /**
     * @return \DCarbone\PHPFHIR\Profiles\ProfilePackage[]
     */
    private static function _collectDependencies(ProfilePackage $package): array
    {
        $out = [];
        foreach ($package->getLoadedDependencies() as $dependency) {
            $out[] = $dependency;
            array_push($out, ...self::_collectDependencies($dependency));
        }
        return $out;
    }

    private function _resolveParent(Profile $profile, bool $strict): void
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
            throw new ProfileDefinitionException(sprintf(
                'Unable to locate base definition "%s" of profile "%s".  If it lives in another package, add that package to the "dependencies" of the profile package config.',
                $profile->getBaseDefinition(),
                $profile->getId(),
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
     * @return \DCarbone\PHPFHIR\Profiles\Profile[]
     */
    public function getProfiles(): array
    {
        return array_map(fn(string $url): Profile => $this->_byUrl[$url], $this->_ownUrls);
    }

    /**
     * Own profiles that pass the package's include / exclude filters, ordered so that every profile comes after its
     * parent profile (if that parent is also selected).
     *
     * @return \DCarbone\PHPFHIR\Profiles\Profile[]
     */
    public function getSelectedProfiles(): array
    {
        $profiles = array_map(fn(string $url): Profile => $this->_byUrl[$url], $this->_selectedUrls);
        usort($profiles, static fn(Profile $a, Profile $b): int => count($a->getAncestors()) <=> count($b->getAncestors()));
        return $profiles;
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
