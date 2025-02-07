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
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);
$ruleResultClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_RESULT);

$imports->addCoreFileImports(
    $typeInterface,
    $validationRuleInterface,
    $ruleResultClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $validationRuleInterface; ?>

{
    public const NAME = 'value_min_length';
    public const DESCRIPTION = 'Asserts that a given string value is at least x characters long';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): string
    {
        return self::DESCRIPTION;
    }

    public function assert(<?php echo $typeInterface; ?> $type, string $field, mixed $constraint, mixed $value): <?php echo $ruleResultClass; ?>

    {
        $res = new <?php echo $ruleResultClass; ?>(self::NAME, $type->_getFHIRTypeName(), $field, $constraint, $value);
        if (0 >= $constraint) {
            return $res;
        }
        if (null === $value || '' === $value) {
            $res->error = sprintf('Field "%s" on type "%s" must be at least %d characters long, but it is empty', $field, $type->_getFHIRTypeName(), $constraint);
        } else if ($constraint > ($len = strlen($value))) {
            $res->error = sprintf('Field "%s" on type "%s" must be at least %d characters long, %d seen.', $field, $type->_getFHIRTypeName(), $constraint, $len);
        }
        return $res;
    }
}
<?php return ob_get_clean();
