<?php declare(strict_types=1);

/*
 * Copyright 2025-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$testCoreFiles = $config->getCoreTestFiles();

$validatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);
$valueOneOfRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_ONE_OF);
$minLenRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MIN_LENGTH);
$maxLenRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MAX_LENGTH);
$patternMatchRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);
$minOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MIN_OCCURS);
$maxOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MAX_OCCURS);
$fixedValueRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_FIXED_VALUE);
$patternValueRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_PATTERN_VALUE);
$sliceOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_SLICE_OCCURS);
$choiceOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_CHOICE_OCCURS);
$codeBindingRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_CODE_BINDING);

$imports->addCoreFileImports(
    $validatorClass,

    $valueOneOfRule,
    $minLenRule,
    $maxLenRule,
    $patternMatchRule,
    $minOccursRule,
    $maxOccursRule,
    $fixedValueRule,
    $patternValueRule,
    $sliceOccursRule,
    $choiceOccursRule,
    $codeBindingRule,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testValidatorHasBaseRules()
    {
        $rules = <?php echo $validatorClass; ?>::getRules();
        $this->assertCount(11, $rules);

        $this->assertArrayHasKey(<?php echo $valueOneOfRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $valueOneOfRule; ?>::class, $rules[<?php echo $valueOneOfRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $minLenRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $minLenRule; ?>::class, $rules[<?php echo $minLenRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $maxLenRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $maxLenRule; ?>::class, $rules[<?php echo $maxLenRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $patternMatchRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $patternMatchRule; ?>::class, $rules[<?php echo $patternMatchRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $minOccursRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $minOccursRule; ?>::class, $rules[<?php echo $minOccursRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $maxOccursRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $maxOccursRule; ?>::class, $rules[<?php echo $maxOccursRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $fixedValueRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $fixedValueRule; ?>::class, $rules[<?php echo $fixedValueRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $patternValueRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $patternValueRule; ?>::class, $rules[<?php echo $patternValueRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $sliceOccursRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $sliceOccursRule; ?>::class, $rules[<?php echo $sliceOccursRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $choiceOccursRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $choiceOccursRule; ?>::class, $rules[<?php echo $choiceOccursRule; ?>::NAME]);

        $this->assertArrayHasKey(<?php echo $codeBindingRule; ?>::NAME, $rules);
        $this->assertInstanceOf(<?php echo $codeBindingRule; ?>::class, $rules[<?php echo $codeBindingRule; ?>::NAME]);
    }

    public function testTightenAddsRulesMissingFromBase()
    {
        $out = <?php echo $validatorClass; ?>::tighten([<?php echo $minOccursRule; ?>::NAME => 1], [<?php echo $maxOccursRule; ?>::NAME => 3]);
        $this->assertSame([<?php echo $minOccursRule; ?>::NAME => 1, <?php echo $maxOccursRule; ?>::NAME => 3], $out);
    }

    public function testTightenNeverLowersMinimum()
    {
        $this->assertSame([<?php echo $minOccursRule; ?>::NAME => 2], <?php echo $validatorClass; ?>::tighten([<?php echo $minOccursRule; ?>::NAME => 2], [<?php echo $minOccursRule; ?>::NAME => 1]));
        $this->assertSame([<?php echo $minOccursRule; ?>::NAME => 3], <?php echo $validatorClass; ?>::tighten([<?php echo $minOccursRule; ?>::NAME => 2], [<?php echo $minOccursRule; ?>::NAME => 3]));
        $this->assertSame([<?php echo $minOccursRule; ?>::NAME => 1], <?php echo $validatorClass; ?>::tighten([<?php echo $minOccursRule; ?>::NAME => 0], [<?php echo $minOccursRule; ?>::NAME => 1]));
    }

    public function testTightenNeverRaisesMaximum()
    {
        $this->assertSame([<?php echo $maxOccursRule; ?>::NAME => 2], <?php echo $validatorClass; ?>::tighten([<?php echo $maxOccursRule; ?>::NAME => 2], [<?php echo $maxOccursRule; ?>::NAME => 5]));
        $this->assertSame([<?php echo $maxOccursRule; ?>::NAME => 1], <?php echo $validatorClass; ?>::tighten([<?php echo $maxOccursRule; ?>::NAME => 2], [<?php echo $maxOccursRule; ?>::NAME => 1]));
    }

    public function testTightenTreatsUnlimitedMaximumAsNoBound()
    {
        $this->assertSame([<?php echo $maxOccursRule; ?>::NAME => 4], <?php echo $validatorClass; ?>::tighten([<?php echo $maxOccursRule; ?>::NAME => -1], [<?php echo $maxOccursRule; ?>::NAME => 4]));
        $this->assertSame([<?php echo $maxOccursRule; ?>::NAME => 4], <?php echo $validatorClass; ?>::tighten([<?php echo $maxOccursRule; ?>::NAME => 4], [<?php echo $maxOccursRule; ?>::NAME => -1]));
    }

    public function testTightenMergesChoiceConstraints()
    {
        $base = [<?php echo $choiceOccursRule; ?>::NAME => ['fields' => ['a', 'b', 'c'], 'allowed' => ['a', 'b'], 'max' => 1]];
        $out = <?php echo $validatorClass; ?>::tighten($base, [<?php echo $choiceOccursRule; ?>::NAME => ['fields' => ['a', 'b', 'c'], 'min' => 1]]);
        $this->assertSame(['fields' => ['a', 'b', 'c'], 'allowed' => ['a', 'b'], 'max' => 1, 'min' => 1], $out[<?php echo $choiceOccursRule; ?>::NAME]);

        $out = <?php echo $validatorClass; ?>::tighten($base, [<?php echo $choiceOccursRule; ?>::NAME => ['fields' => ['a', 'b', 'c'], 'allowed' => ['b', 'c'], 'max' => 2]]);
        $this->assertSame(['fields' => ['a', 'b', 'c'], 'allowed' => ['b'], 'max' => 1], $out[<?php echo $choiceOccursRule; ?>::NAME]);

        // fields are united so a variant missing from a custom base rule is still examined.
        $out = <?php echo $validatorClass; ?>::tighten([<?php echo $choiceOccursRule; ?>::NAME => ['fields' => ['a']]], [<?php echo $choiceOccursRule; ?>::NAME => ['fields' => ['a', 'b'], 'allowed' => ['a']]]);
        $this->assertSame(['fields' => ['a', 'b'], 'allowed' => ['a']], $out[<?php echo $choiceOccursRule; ?>::NAME]);

        // a disabled (null) base rule is replaced, not merged.
        $out = <?php echo $validatorClass; ?>::tighten([<?php echo $choiceOccursRule; ?>::NAME => null], [<?php echo $choiceOccursRule; ?>::NAME => ['fields' => ['a']]]);
        $this->assertSame(['fields' => ['a']], $out[<?php echo $choiceOccursRule; ?>::NAME]);
    }

    public function testTightenReplacesOtherRules()
    {
        $out = <?php echo $validatorClass; ?>::tighten([<?php echo $patternMatchRule; ?>::NAME => '/a/'], [<?php echo $patternMatchRule; ?>::NAME => '/b/']);
        $this->assertSame([<?php echo $patternMatchRule; ?>::NAME => '/b/'], $out);
    }
}
<?php return ob_get_clean();
