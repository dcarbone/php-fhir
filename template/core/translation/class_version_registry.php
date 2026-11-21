<?php declare(strict_types=1);

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */
/** @var string[] $versionNames */

$coreFiles = $config->getCoreFiles();
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($typeInterface, $versionInterface);

$versionClasses = [];
foreach ($versionNames as $versionName) {
    $version = $config->getVersion($versionName);
    $versionClass = $version->getVersionCoreFiles()->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
    $versionClasses[$version->getFullyQualifiedName(false)] = $versionClass->getFullyQualifiedName(false);
}
// Version namespaces may be nested (e.g. "R4" and "R4\Custom"), so the most specific namespace must be matched first.
uksort($versionClasses, static fn(string $a, string $b): int => strlen($b) <=> strlen($a) ?: strcmp($a, $b));

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?>

{
    private const _VERSION_CLASSES = <?php echo pretty_var_export($versionClasses, 1); ?>;

    /** @var null|VersionInterface[] */
    private static null|array $_versions = null;

    /**
     * @return VersionInterface[]
     */
    public static function getVersions(): array
    {
        if (null === self::$_versions) {
            self::$_versions = [];
            foreach (self::_VERSION_CLASSES as $className) {
                self::$_versions[] = new $className();
            }
            usort(
                self::$_versions,
                static fn(VersionInterface $a, VersionInterface $b): int => $a->getFHIRVersionInteger() <=> $b->getFHIRVersionInteger(),
            );
        }
        return self::$_versions;
    }

    public static function getVersionForType(TypeInterface $type): VersionInterface
    {
        $typeClass = $type::class;
        foreach (self::_VERSION_CLASSES as $namespace => $className) {
            if (!str_starts_with($typeClass, $namespace . '\\')) {
                continue;
            }

            $version = new $className();
            if ($type->_getFHIRVersion() !== $version::getFHIRVersion()) {
                throw new \UnexpectedValueException(sprintf(
                    'FHIR version mismatch for generated type "%s": namespace resolves to %s, type reports %s.',
                    $typeClass,
                    (string)$version::getFHIRVersion(),
                    (string)$type->_getFHIRVersion(),
                ));
            }
            return $version;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unable to resolve a generated FHIR version for type "%s".',
            $typeClass,
        ));
    }
}
<?php return ob_get_clean();
