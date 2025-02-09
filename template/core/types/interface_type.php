<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$imports = $coreFile->getImports();

$ruleResultListClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_DEBUG_RESULT_LIST);

$imports->addCoreFileImports(
    $ruleResultListClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?> extends \JsonSerializable
{
    /**
     * Returns the FHIR name represented by this Type
     *
     * @return string
     */
    public function _getFHIRTypeName(): string;

    /**
     * Execute any and all validation rules present on this type and all nested field types.
     *
     * @param bool $debug If true, returns a <?php echo $ruleResultListClass; ?> instance with more detailed information.
     * @return array|<?php echo $ruleResultListClass->getFullyQualifiedName(true); ?>

     */
    public function _getValidationErrors(bool $debug = false): array|<?php echo $ruleResultListClass; ?>;

    /**
     * @return string
     */
    public function __toString(): string;
}
<?php return ob_get_clean();