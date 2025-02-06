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

/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */
/** @var \DCarbone\PHPFHIR\Version $version */

$types = $version->getDefinition()->getTypes();

$coreFiles = $version->getCoreFiles();

ob_start();
echo "<?php\n\n"; ?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


use <?php echo $coreFiles
    ->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP)
    ->getFullyQualifiedName(false); ?>;
use PHPUnit\Framework\TestCase;

class <?php echo PHPFHIR_TEST_CLASSNAME_TYPE_MAP; ?> extends TestCase
{
    public function testGetTypeClassWithNonStringReturnsNull()
    {
        $this->assertNull(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getTypeClassName(1));
    }

    public function testGetTypeClassName()
    {
<?php foreach($types->getNamespaceSortedIterator() as $type): ?>
    $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getTypeClassName('<?php echo $type->getFHIRName(); ?>'));
<?php endforeach; ?>
    }

    public function testGetContainedTypeClassName()
    {
<?php foreach($types->getNameSortedIterator() as $type) :
    if ($type->isContainedType()) : ?>
        $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassName('<?php echo $type->getFHIRName(); ?>'));
<?php else : ?>
        $this->assertNull(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassName('<?php echo $type->getFHIRName(); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithClassname()
    {
<?php foreach($types->getNameSortedIterator() as $type) :
    // TODO(@dcarbone): don't do this.
    if ($type->getFHIRName() === PHPFHIR_XHTML_TYPE_NAME) {
        continue;
    }
    if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(false); ?>'));
        $this->assertTrue(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(true); ?>'));
<?php else : ?>
        $this->assertFalse(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(false); ?>'));
        $this->assertFalse(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource('<?php echo $type->getFullyQualifiedClassName(true); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithTypeName()
    {
<?php foreach($types->getNameSortedIterator() as $type) :
    if ($type->isAbstract()) {
        continue;
    }
    // TODO(@dcarbone): don't do this.
    if ($type->getFHIRName() === PHPFHIR_XHTML_TYPE_NAME) {
        continue;
    }
    if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource('<?php echo $type->getFHIRName(); ?>'));
<?php else : ?>
        $this->assertFalse(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource('<?php echo $type->getFHIRName(); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithInstance()
    {
<?php foreach($types->getNameSortedIterator() as $type) :
    if ($type->isAbstract()) {
        continue;
    }
    // TODO(@dcarbone): don't do this.
    if ($type->getFHIRName() === PHPFHIR_XHTML_TYPE_NAME) {
        continue;
    }
?>
        $type = new <?php echo $type->getFullyQualifiedClassName(true); ?>;
<?php if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource($type));
<?php else : ?>
        $this->assertFalse(<?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::isContainableResource($type));
<?php endif;
endforeach; ?>
    }
}
<?php
return ob_get_clean();