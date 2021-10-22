<?php
/*
 * Copyright 2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$rootNS = $config->getNamespace(false);
$testNS = $config->getTestsNamespace(PHPFHIR_TEST_TYPE_BASE, false);

ob_start();
echo "<?php\n\n"; ?>
namespace <?php echo $testNS; ?>;

<?php echo CopyrightUtils::getFullPHPFHIRCopyrightComment(); ?>

use <?php echo $rootNS; ?>\<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>;
use PHPUnit\Framework\TestCase;

/**
 * Class <?php echo PHPFHIR_TEST_CLASSNAME_TYPEMAP; ?>

 * @package \<?php echo $testNS; ?>

 */
class <?php echo PHPFHIR_TEST_CLASSNAME_TYPEMAP; ?> extends TestCase
{
    public function testGetTypeClassWithNonStringReturnsNull()
    {
        $this->assertNull(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getTypeClass(1));
    }

    public function testGetContainedTypeClassName()
    {
<?php foreach($types->getSortedIterator() as $type) :
    if ($type->isContainedType()) : ?>
        $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getContainedTypeClassName('<?php echo $type->getFHIRName(); ?>'));
<?php else : ?>
        $this->assertNull(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getContainedTypeClassName('<?php echo $type->getFHIRName(); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithClassname()
    {
<?php foreach($types->getSortedIterator() as $type) :
    if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(false); ?>'));
        $this->assertTrue(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(true); ?>'));
<?php else : ?>
        $this->assertFalse(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(false); ?>'));
        $this->assertFalse(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(true); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithTypeName()
    {
<?php foreach($types->getSortedIterator() as $type) :
    if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource('<?php echo $type->getFHIRName(); ?>'));
<?php else : ?>
        $this->assertFalse(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource('<?php echo $type->getFHIRName(); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithInstance()
    {
<?php foreach($types->getSortedIterator() as $type) : ?>
        $type = new <?php echo $type->getFullyQualifiedClassName(true); ?>;
<?php if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource($type));
<?php else : ?>
        $this->assertFalse(<?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::isContainableResource($type));
<?php endif;
endforeach; ?>
    }

    public function testGetTypeClass()
    {
<?php foreach($types->getNamespaceSortedIterator() as $type): ?>
        $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getTypeClass('<?php echo $type->getFHIRName(); ?>'));
<?php endforeach; ?>
    }
}
<?php
return ob_get_clean();