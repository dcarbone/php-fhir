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
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);

$ruleResult = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_RESULT);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $validatorClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?> extends \Countable, \JsonSerializable

{
    /**
     * Must return the name of the rule that produced this result
     *
     * @return string
     */
    public function getRule(): string;

    /**
     * Must return the name of the FHIR type the rule was run on.
     *
     * @return string
     */
    public function getFHIRType(): string;

    /**
     * Must return the name of the field the rule was run on.
     *
     * @return string
     */
    public function getField(): string;

    /**
     * Must return the constraint passed to the rule.
     *
     * @return mixed
     */
    public function getConstraint(): mixed;

    /**
     * Must return the error message produced by the rule, if there was one.  An empty value indicates no error.
     *
     * @return string
     */
    public function getError(): string;

    /**
     * Must return whether this rule must be the last rule run in the set for the type's field.
     *
     * @return bool
     */
    public function mustHalt(): bool;

    /**
     * Must return true if this rule and all (if any) subrules passed.
     *
     * @return bool
     */
    public function ok(): bool;

    /**
     * Must return the total count of all errored rules, including self and sub-rules
     *
     * @return int
     */
    public function getErroCount(): int;

    /**
     * Must return an iterator that enables recursion through self and all sub-rules
     *
     * @return <?php echo $coreFile->getFullyQualifiedName(true); ?>[]
     */
    public function getIterator(): iterable;
}
<?php return ob_get_clean();
