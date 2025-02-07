<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);
$ruleResult = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_RESULT);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $validatorClass,
    $ruleResult,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?>

{
    /**
     * Must return the name of this rule.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Should return a human-readable description of the purpose of this validator
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Perform assertion for this rule.
     *
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $field
     * @param mixed $constraint
     * @param mixed $value
     * @return <?php echo $ruleResult->getFullyQualifiedName(true); ?>

     */
    public function assert(<?php echo $typeInterface; ?> $type, string $field, mixed $constraint, mixed $value): <?php echo $ruleResult; ?>;
}
<?php return ob_get_clean();
