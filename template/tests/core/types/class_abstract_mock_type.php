<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$coreFiles = $config->getCoreFiles();
$testCoreFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);

$imports->addCoreFileImports(
    $typeInterface,
    $fhirVersion,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

abstract class <?php echo $coreFile; ?> implements <?php echo $typeInterface; ?>

{
    protected string $_name;
    protected <?php echo $fhirVersion; ?> $_fhirVersion;

    public function __construct(string $name,
                                string $versionName = 'mock',
                                string $semanticVersion = 'v99.99.99')
    {
        $this->_name = $name;

        $shortVersion = ltrim($semanticVersion, 'v');
        $shortVersion = match (substr_count($shortVersion, '.')) {
            1 => $shortVersion,
            2 => substr($shortVersion, 0, strrpos($shortVersion, '.')),
            default => implode('.', array_chunk(explode('.', $shortVersion), 2)[0])
        };

        $this->_fhirVersion = new <?php echo $fhirVersion; ?>(
            $versionName,
            $semanticVersion,
            $shortVersion,
            intval(sprintf("%'.-08s", str_replace(['v', '.'], '', $semanticVersion))),
        );
    }

    public function _getFHIRTypeName(): string
    {
        return $this->_name;
    }

    public function _getFHIRVersion(): <?php echo $fhirVersion; ?>
    {
        return $this->_fhirVersion;
    }
}
<?php return ob_get_clean();
