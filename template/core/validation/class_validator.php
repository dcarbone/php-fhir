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
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);
$ruleResultClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_RULE_RESULT);

$valueOneOfRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_ONE_OF);
$minLengthRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MIN_LENGTH);
$maxLengthRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MAX_LENGTH);
$patternRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);
$minOccursRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MIN_OCCURS);
$maxOccursRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MAX_OCCURS);

$imports->addCoreFileImports(
    $typeInterface,
    $validationRuleInterface,
    $ruleResultClass,

    $valueOneOfRule,
    $minLengthRuleClass,
    $maxLengthRuleClass,
    $patternRuleClass,
    $minOccursRuleClass,
    $maxOccursRuleClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?>

{
    /**
     * Map of rules, keyed by name.
     *
     * @var <?php echo $validationRuleInterface->getFullyQualifiedName(true); ?>[]
     */
    protected static array $_rules = [];

    private static bool $_initialized = false;

    /**
     * Define a validation rule.  Will overwrite any pre-existing rule with the same name.
     *
     * @param <?php echo $validationRuleInterface->getFullyQualifiedName(true); ?> $rule
     */
    public static function setRule(<?php echo $validationRuleInterface; ?> $rule): void
    {
        self::_init();
        self::$_rules[$rule->getName()] = $rule;
    }

    /**
     * Return the current map of rules
     * @return <?php echo $validationRuleInterface->getFullyQualifiedName(true); ?>[]
     */
    public static function getRules(): array
    {
        self::_init();
        return self::$_rules;
    }

    /**
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $field
     * @param string|<?php echo $validationRuleInterface->getFullyQualifiedName(true); ?> $rule Name of registered validation rule, or a specific rule instance to run.
     * @param mixed $constraint
     * @param mixed $value
     * @return <?php echo $ruleResultClass->getFullyQualifiedName(true); ?>

     */
    public static function runRule(<?php echo $typeInterface; ?> $type,
                                   string $field,
                                   string|<?php echo $validationRuleInterface; ?> $rule,
                                   mixed $constraint,
                                   mixed $value): <?php echo $ruleResultClass; ?>

    {
        if ($rule instanceof <?php echo $validationRuleInterface; ?>) {
            return $rule->assert($type, $field, $constraint, $value);
        }
        self::_init();
        if (isset(self::$_rules[$rule])) {
            return self::$_rules[$rule]->assert($type, $field, $constraint, $value);
        }
        throw new \OutOfBoundsException(sprintf('No rule named "%s" registered.', $rule));
    }

    private static function _init(): void
    {
        if (self::$_initialized) {
            return;
        }
        self::$_rules[<?php echo $valueOneOfRule; ?>::NAME] = new <?php echo $valueOneOfRule; ?>();
        self::$_rules[<?php echo $minLengthRuleClass; ?>::NAME] = new <?php echo $minLengthRuleClass; ?>();
        self::$_rules[<?php echo $maxLengthRuleClass; ?>::NAME] = new <?php echo $maxLengthRuleClass; ?>();
        self::$_rules[<?php echo $patternRuleClass; ?>::NAME] = new <?php echo $patternRuleClass; ?>();
        self::$_rules[<?php echo $minOccursRuleClass; ?>::NAME] = new <?php echo $minOccursRuleClass; ?>();
        self::$_rules[<?php echo $maxOccursRuleClass; ?>::NAME] = new <?php echo $maxOccursRuleClass; ?>();
    }
}
<?php return ob_get_clean();
