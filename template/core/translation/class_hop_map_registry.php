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
/** @var array<string, class-string> $hopMapClasses */

$versionInterface = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$hopMapInterface = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_HOP_MAP);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($versionInterface, $hopMapInterface);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?>

{
    /** @var array<string, class-string<<?php echo $hopMapInterface->getFullyQualifiedName(true); ?>>> */
    private const _MAP_CLASSES = <?php echo pretty_var_export($hopMapClasses, 1); ?>;

    /** @var array<string, <?php echo $hopMapInterface->getFullyQualifiedName(true); ?>> */
    private static array $_maps = [];

    public static function getMap(VersionInterface $source, VersionInterface $target): null|HopMapInterface
    {
        $key = $source->getName() . "\0" . $target->getName();
        if (isset(self::$_maps[$key])) {
            return self::$_maps[$key];
        }

        $className = self::_MAP_CLASSES[$key] ?? null;
        if (null === $className) {
            return null;
        }

        $map = new $className();
        if (!$map instanceof HopMapInterface) {
            throw new \UnexpectedValueException(sprintf(
                'Generated hop map "%s" must implement %s.',
                $className,
                HopMapInterface::class,
            ));
        }
        return self::$_maps[$key] = $map;
    }
}
<?php return ob_get_clean();
