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
$testCoreFiles = $config->getCoreTestFiles();

$validatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);
$valueOneOfRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_ONE_OF);
$minLenRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MIN_LENGTH);
$maxLenRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_MAX_LENGTH);
$patternMatchRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_VALUE_PATTERN_MATCH);
$minOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MIN_OCCURS);
$maxOccursRule = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_RULE_CLASSNAME_MAX_OCCURS);

$imports->addCoreFileImports(
    $validatorClass,

    $valueOneOfRule,
    $minLenRule,
    $maxLenRule,
    $patternMatchRule,
    $minOccursRule,
    $maxOccursRule,
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
        $this->assertCount(6, $rules);

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
    }
}
<?php return ob_get_clean();
