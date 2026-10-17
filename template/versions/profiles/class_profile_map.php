<?php declare(strict_types=1);

/*
 * Copyright 2018-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Builder\Imports;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var string $copyright */
/** @var string $namespace */
/** @var string $className */
/** @var string[] $packageMaps fully qualified names, without leading slash, of the per package maps */
/** @var array<string, string> $coreTypes fully qualified names, without leading slash, of the core enums and interfaces referred to, by entity name */

$imports = new Imports(null, $namespace, $className);
$imports->addImport('', 'UnexpectedValueException');
foreach ([PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE, PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE, PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH, PHPFHIR_TYPES_INTERFACE_PROFILE_TYPE] as $entity) {
    $imports->addImport(substr($coreTypes[$entity], 0, strrpos($coreTypes[$entity], '\\')), $entity);
}

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $copyright; ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Entry point for looking up the generated profile classes.  The classes themselves are indexed per package, see the
 * package maps, which are only loaded when a lookup reaches them.
 */
abstract class <?php echo $className; ?>

{
    /** @var string[] Loaded one at a time, and only when a lookup gets to them. */
    private const _PACKAGE_MAPS = [
<?php foreach ($packageMaps as $packageMap) : ?>
        <?php echo var_export($packageMap, true); ?>,
<?php endforeach; ?>
    ];

    /**
     * @return array<string, class-string<<?php echo PHPFHIR_TYPES_INTERFACE_PROFILE_TYPE; ?>>>
     */
    public static function getProfileClasses(): array
    {
        $out = [];
        foreach (self::_PACKAGE_MAPS as $map) {
            /** @var class-string<PackageMapInterface> $map */
            foreach ($map::getProfileClasses() as $url => $class) {
                $out[$url] = $class;
            }
        }
        return $out;
    }

    /**
     * @param string $canonical Canonical profile url, with or without a "|version" suffix.
     * @return null|class-string<<?php echo PHPFHIR_TYPES_INTERFACE_PROFILE_TYPE; ?>>
     */
    public static function getProfileClassname(string $canonical): null|string
    {
        $pos = strpos($canonical, '|');
        if (false !== $pos) {
            $canonical = substr($canonical, 0, $pos);
        }
        foreach (self::_PACKAGE_MAPS as $map) {
            /** @var class-string<PackageMapInterface> $map */
            $class = $map::getProfileClassname($canonical);
            if (null !== $class) {
                return $class;
            }
        }
        return null;
    }

    /**
     * @param string $canonical Canonical profile url, with or without a "|version" suffix.
     * @return null|string FHIR name of the type the profile constrains.
     */
    public static function getProfileBaseType(string $canonical): null|string
    {
        $pos = strpos($canonical, '|');
        if (false !== $pos) {
            $canonical = substr($canonical, 0, $pos);
        }
        foreach (self::_PACKAGE_MAPS as $map) {
            /** @var class-string<PackageMapInterface> $map */
            $type = $map::getProfileBaseType($canonical);
            if (null !== $type) {
                return $type;
            }
        }
        return null;
    }

    /**
     * Selects the profile class to unserialize a resource into, given the values of its "meta.profile".
     *
     * @param class-string $baseClassName Class of the resource's declared FHIR type.
     * @param string[] $declared Canonical profile urls, in declaration order, optionally with a "|version" suffix.
     * @param <?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?> $onUnknown
     * @param <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?> $onAmbiguous
     * @param <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?> $onVersionMismatch
     * @return null|class-string<<?php echo PHPFHIR_TYPES_INTERFACE_PROFILE_TYPE; ?>> Null when the base class should be used.
     * @throws UnexpectedValueException
     */
    public static function resolve(string $baseClassName,
                                   array $declared,
                                   <?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?> $onUnknown,
                                   <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?> $onAmbiguous,
                                   <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?> $onVersionMismatch): null|string
    {
        $candidates = [];
        foreach ($declared as $canonical) {
            if (!is_string($canonical) || '' === $canonical) {
                continue;
            }
            /** @var null|class-string<<?php echo PHPFHIR_TYPES_INTERFACE_PROFILE_TYPE; ?>> $class */
            $class = self::getProfileClassname($canonical);
            if (null === $class || !is_a($class, $baseClassName, true)) {
                if (<?php echo PHPFHIR_ENCODING_ENUM_ON_UNKNOWN_PROFILE; ?>::EXCEPTION === $onUnknown) {
                    throw new UnexpectedValueException(sprintf(
                        'Profile "%s" is not a known profile of "%s"',
                        $canonical,
                        $baseClassName
                    ));
                }
                continue;
            }
            $pos = strpos($canonical, '|');
            if (false !== $pos && <?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?>::IGNORE_VERSION !== $onVersionMismatch) {
                $declaredVersion = substr($canonical, $pos + 1);
                $generatedVersion = $class::_getProfileVersion();
                if (null !== $generatedVersion && $declaredVersion !== $generatedVersion) {
                    if (<?php echo PHPFHIR_ENCODING_ENUM_ON_PROFILE_VERSION_MISMATCH; ?>::EXCEPTION === $onVersionMismatch) {
                        throw new UnexpectedValueException(sprintf(
                            'Profile "%s" was generated from version "%s"',
                            $canonical,
                            $generatedVersion
                        ));
                    }
                    continue;
                }
            }
            if (!in_array($class, $candidates, true)) {
                $candidates[] = $class;
            }
        }

        if ([] === $candidates) {
            return null;
        }
        if (1 === count($candidates)) {
            return $candidates[0];
        }

        // the most specific profile is the one that refines all of the others
        foreach ($candidates as $candidate) {
            $refinesAll = true;
            foreach ($candidates as $other) {
                if ($candidate !== $other && !is_a($candidate, $other, true)) {
                    $refinesAll = false;
                    break;
                }
            }
            if ($refinesAll) {
                return $candidate;
            }
        }

        return match ($onAmbiguous) {
            <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?>::FIRST_DECLARED => $candidates[0],
            <?php echo PHPFHIR_ENCODING_ENUM_ON_AMBIGUOUS_PROFILE; ?>::FALLBACK => null,
            default => throw new UnexpectedValueException(sprintf(
                'Ambiguous profiles declared for "%s": ["%s"]',
                $baseClassName,
                implode('","', $candidates)
            )),
        };
    }
}
<?php return ob_get_clean();
