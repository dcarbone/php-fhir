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

/** @var string $copyright */
/** @var string $namespace */
/** @var string $className */
/** @var array<string, string> $classMap profile url => fully qualified class name, without leading slash */
/** @var array<string, string> $baseTypeMap profile url => FHIR type name */

$imports = new \DCarbone\PHPFHIR\Builder\Imports(null, $namespace, $className);
$imports->addImport('', 'UnexpectedValueException');
$classNames = [];
foreach ($classMap as $url => $fqcn) {
    $pos = strrpos($fqcn, '\\');
    $import = $imports->addImport(substr($fqcn, 0, $pos), substr($fqcn, $pos + 1));
    $classNames[$url] = $import->getImportedName();
}

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $copyright; ?>


<?php echo \DCarbone\PHPFHIR\Utilities\ImportUtils::compileImportStatements($imports); ?>

/**
 * Index of the generated profile classes, keyed by canonical profile url.
 */
abstract class <?php echo $className; ?>

{
    /** @var array<string, class-string> */
    private const _PROFILES = [
<?php foreach ($classMap as $url => $fqcn) : ?>
        <?php echo var_export($url, true); ?> => <?php echo $classNames[$url]; ?>::class,
<?php endforeach; ?>
    ];

    /** @var array<string, string> */
    private const _BASE_TYPES = [
<?php foreach ($baseTypeMap as $url => $type) : ?>
        <?php echo var_export($url, true); ?> => <?php echo var_export($type, true); ?>,
<?php endforeach; ?>
    ];

    /**
     * @return array<string, class-string>
     */
    public static function getProfileClasses(): array
    {
        return self::_PROFILES;
    }

    /**
     * @param string $canonical Canonical profile url, with or without a "|version" suffix.
     * @return null|class-string
     */
    public static function getProfileClassname(string $canonical): null|string
    {
        $pos = strpos($canonical, '|');
        if (false !== $pos) {
            $canonical = substr($canonical, 0, $pos);
        }
        return self::_PROFILES[$canonical] ?? null;
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
        return self::_BASE_TYPES[$canonical] ?? null;
    }

    /**
     * Selects the profile class to unserialize a resource into, given the values of its "meta.profile".
     *
     * @param class-string $baseClassName Class of the resource's declared FHIR type.
     * @param string[] $declared Canonical profile urls, in declaration order, optionally with a "|version" suffix.
     * @param string $onUnknown "fallback" or "exception"
     * @param string $onAmbiguous "firstDeclared", "fallback" or "exception"
     * @param string $onVersionMismatch "ignoreVersion", "fallback" or "exception"
     * @return null|class-string Null when the base class should be used.
     * @throws UnexpectedValueException
     */
    public static function resolve(string $baseClassName,
                                   array $declared,
                                   string $onUnknown,
                                   string $onAmbiguous,
                                   string $onVersionMismatch): null|string
    {
        $candidates = [];
        foreach ($declared as $canonical) {
            if (!is_string($canonical) || '' === $canonical) {
                continue;
            }
            $class = self::getProfileClassname($canonical);
            if (null === $class || !is_a($class, $baseClassName, true)) {
                if ('exception' === $onUnknown) {
                    throw new UnexpectedValueException(sprintf(
                        'Profile "%s" is not a known profile of "%s"',
                        $canonical,
                        $baseClassName
                    ));
                }
                continue;
            }
            $pos = strpos($canonical, '|');
            if (false !== $pos && 'ignoreVersion' !== $onVersionMismatch) {
                $declaredVersion = substr($canonical, $pos + 1);
                $generatedVersion = $class::PROFILE_VERSION;
                if (null !== $generatedVersion && $declaredVersion !== $generatedVersion) {
                    if ('exception' === $onVersionMismatch) {
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
            'firstDeclared' => $candidates[0],
            'fallback' => null,
            default => throw new UnexpectedValueException(sprintf(
                'Ambiguous profiles declared for "%s": ["%s"]',
                $baseClassName,
                implode('","', $candidates)
            )),
        };
    }
}
<?php return ob_get_clean();
