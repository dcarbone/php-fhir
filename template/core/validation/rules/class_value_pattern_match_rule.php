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
$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);
$ruleResultClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_RESULT);

$imports->addCoreFileImports(
    $typeInterface,
    $primitiveTypeInterface,
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
    public const NAME = 'value_pattern_match';
    public const DESCRIPTION = 'Asserts that a given string value matches the specified pattern';

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
        if ('' === $constraint || null === $value) {
            return $res;
        }
        if ($value instanceof <?php echo $primitiveTypeInterface; ?>) {
            $value = (string)$value;
        }
        $match = preg_match($constraint, $value);
        if (PREG_NO_ERROR !== preg_last_error()) {
            $res->error = sprintf(
                'Rule %s failed to verify type "%s" field "%s" value of size %d with pattern "%s": %s',
                self::NAME,
                $type->_getFHIRTypeName(),
                $field,
                strlen((string)$value),
                $constraint,
                preg_last_error_msg(),
            );
        } else if (!$match) {
            $res->error = sprintf('Field "%s" on type "%s" value of "%s" does not match pattern: %s', $field, $type->_getFHIRTypeName(), $value, $constraint);
        }
        return $res;
    }
}
<?php return ob_get_clean();
