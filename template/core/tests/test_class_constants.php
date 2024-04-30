<?php declare(strict_types=1);

/*
 * Copyright 2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TestType;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$rootNS = $config->getNamespace(false);
$testNS = $config->getTestsNamespace(TestType::BASE, false);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $testNS; ?>;

<?php echo CopyrightUtils::getFullPHPFHIRCopyrightComment(); ?>

use <?php echo $rootNS; ?>\<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>;
use PHPUnit\Framework\TestCase;

/**
 * Class <?php echo PHPFHIR_TEST_CLASSNAME_CONSTANTS; ?>

 * @package \<?php echo $testNS; ?>

 */
class <?php echo PHPFHIR_TEST_CLASSNAME_CONSTANTS; ?> extends TestCase
{
<?php foreach($types->getNameSortedIterator() as $type) : ?>
    public function testTypeConstantsDefined<?php echo str_replace('\\', '_', $type->getFullyQualifiedClassName(false)); ?>()
    {
        $this->assertEquals('<?php echo $type->getFHIRName(); ?>', <?php echo $type->getTypeNameConst(true); ?>);
        $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo $type->getClassNameConst(true); ?>);
    }

<?php endforeach; ?>
}
<?php
return ob_get_clean();