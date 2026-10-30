<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$constantsClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CONSTANTS);
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$validationRuleInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_INTERFACE_RULE);

$valueOneOfRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_ONE_OF);
$minLengthRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MIN_LENGTH);
$maxLengthRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MAX_LENGTH);
$patternRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);
$minOccursRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MIN_OCCURS);
$maxOccursRuleClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MAX_OCCURS);
$fixedValueRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_FIXED_VALUE);
$patternValueRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_PATTERN_VALUE);
$sliceOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_SLICE_OCCURS);
$choiceOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_CHOICE_OCCURS);
$codeBindingRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_CODE_BINDING);

$imports->addCoreFileImports(
    $constantsClass,
    $typeInterface,
    $validationRuleInterface,

    $valueOneOfRule,
    $minLengthRuleClass,
    $maxLengthRuleClass,
    $patternRuleClass,
    $minOccursRuleClass,
    $maxOccursRuleClass,
    $fixedValueRule,
    $patternValueRule,
    $sliceOccursRule,
    $choiceOccursRule,
    $codeBindingRule,
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
        self::$_rules[$rule->getName()] = $rule;
    }

    /**
     * Return a rule by name, if it exists
     *
     * @param string $ruleName
     * @return null|<?php echo $validationRuleInterface->getFullyQualifiedName(true); ?>

     */
    public static function getRule(string $ruleName): null|<?php echo $validationRuleInterface; ?>

    {
        return self::$_rules[$ruleName] ?? null;
    }

    /**
     * Return the current map of rules
     * @return <?php echo $validationRuleInterface->getFullyQualifiedName(true); ?>[]
     */
    public static function getRules(): array
    {
        return self::$_rules;
    }

    /**
     * @param <?php echo $typeInterface->getFullyQualifiedName(true); ?> $type
     * @param string $field
     * @param string|<?php echo $validationRuleInterface->getFullyQualifiedName(true); ?> $rule Name of registered validation rule, or a specific rule instance to run.
     * @param mixed $constraint
     * @param mixed $value
     * @return null|string
     */
    public static function runRule(<?php echo $typeInterface; ?> $type,
                                   string $field,
                                   string|<?php echo $validationRuleInterface; ?> $rule,
                                   mixed $constraint,
                                   mixed $value): null|string
    {
        if ($rule instanceof <?php echo $validationRuleInterface; ?>) {
            return $rule->assert($type, $field, $constraint, $value);
        }
        if (isset(self::$_rules[$rule])) {
            return self::$_rules[$rule]->assert($type, $field, $constraint, $value);
        }
        throw new \OutOfBoundsException(sprintf('No rule named "%s" registered.', $rule));
    }

    /**
     * Applies the rules of a constraining definition, such as a profile, on top of the rules of its base.  Unlike a
     * plain override, a constraint may only narrow its base: the greater of two minimums and the lesser of two
     * maximums win.  Any other rule present in both is replaced by the constraining definition's.
     *
     * @param array $base Map of ["rule" => {constraint}] of the base definition.
     * @param array $constraining Map of ["rule" => {constraint}] of the constraining definition.
     * @return array
     */
    public static function tighten(array $base, array $constraining): array
    {
        foreach ($constraining as $rule => $constraint) {
            if (!array_key_exists($rule, $base)) {
                $base[$rule] = $constraint;
            } elseif (<?php echo $minOccursRuleClass; ?>::NAME === $rule) {
                $base[$rule] = max($base[$rule], $constraint);
            } elseif (<?php echo $maxOccursRuleClass; ?>::NAME === $rule) {
                $base[$rule] = match (true) {
                    <?php echo $constantsClass; ?>::UNLIMITED === $base[$rule] => $constraint,
                    <?php echo $constantsClass; ?>::UNLIMITED === $constraint => $base[$rule],
                    default => min($base[$rule], $constraint),
                };
            } elseif (<?php echo $choiceOccursRule; ?>::NAME === $rule) {
                $base[$rule] = self::_tightenChoice($base[$rule], $constraint);
            } else {
                $base[$rule] = $constraint;
            }
        }
        return $base;
    }

    /**
     * Allowed variants are intersected and bounds narrowed, so a profile never loosens its parent's choice.
     */
    private static function _tightenChoice(array $base, array $constraining): array
    {
        if (isset($constraining['allowed'])) {
            $base['allowed'] = isset($base['allowed'])
                ? array_values(array_intersect($base['allowed'], $constraining['allowed']))
                : $constraining['allowed'];
        }
        if (isset($constraining['min'])) {
            $base['min'] = max($base['min'] ?? 0, $constraining['min']);
        }
        if (isset($constraining['max'])) {
            $base['max'] = isset($base['max']) ? min($base['max'], $constraining['max']) : $constraining['max'];
        }
        return $base;
    }

    public static function _init(): void
    {
        if (self::$_initialized) {
            return;
        }
        self::$_initialized = true;
        self::$_rules[<?php echo $valueOneOfRule; ?>::NAME] = new <?php echo $valueOneOfRule; ?>();
        self::$_rules[<?php echo $minLengthRuleClass; ?>::NAME] = new <?php echo $minLengthRuleClass; ?>();
        self::$_rules[<?php echo $maxLengthRuleClass; ?>::NAME] = new <?php echo $maxLengthRuleClass; ?>();
        self::$_rules[<?php echo $patternRuleClass; ?>::NAME] = new <?php echo $patternRuleClass; ?>();
        self::$_rules[<?php echo $minOccursRuleClass; ?>::NAME] = new <?php echo $minOccursRuleClass; ?>();
        self::$_rules[<?php echo $maxOccursRuleClass; ?>::NAME] = new <?php echo $maxOccursRuleClass; ?>();
        self::$_rules[<?php echo $fixedValueRule; ?>::NAME] = new <?php echo $fixedValueRule; ?>();
        self::$_rules[<?php echo $patternValueRule; ?>::NAME] = new <?php echo $patternValueRule; ?>();
        self::$_rules[<?php echo $sliceOccursRule; ?>::NAME] = new <?php echo $sliceOccursRule; ?>();
        self::$_rules[<?php echo $choiceOccursRule; ?>::NAME] = new <?php echo $choiceOccursRule; ?>();
        self::$_rules[<?php echo $codeBindingRule; ?>::NAME] = new <?php echo $codeBindingRule; ?>();
    }
}

<?php echo $coreFile; ?>::_init();
<?php return ob_get_clean();
