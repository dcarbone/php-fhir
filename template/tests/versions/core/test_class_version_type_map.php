<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

// TODO: I did not separate each type into its own test method because new PHPStorm absolutely shits itself with that many test funcs.

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */
/** @var \DCarbone\PHPFHIR\Version $version */

$versionCoreFiles = $version->getVersionCoreFiles();
$imports = $coreFile->getImports();

$typeMapClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP);

$imports->addCoreFileImports(
    $typeMapClass,
);

$types = $version->getDefinition()->getTypes();

ob_start();
echo "<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */\n\n"; ?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>
use PHPUnit\Framework\TestCase;

class <?php echo $coreFile; ?> extends TestCase
{
    public function testGetTypeClassnameWithInvalidString()
    {
        $this->assertNull(<?php echo $typeMapClass; ?>::getTypeClassname('\\stdClass'));
    }

    public function testGetTypeClassnameWithInvalidXML()
    {
        $sxe = new \SimpleXMLElement('<NotAResource></NotAResource>');
        $this->assertNull(<?php echo $typeMapClass; ?>::getTypeClassname($sxe));
    }

    public function testGetTypeClassnameWithJSONMissingResourceType()
    {
        $json = new \stdClass();
        $json->jimmy = 'Observation';
        $this->assertNull(<?php echo $typeMapClass; ?>::getTypeClassname($json));
    }

    public function testGetTypeClassnameWithJSONInvalidResourceType()
    {
        $json = new \stdClass();
        $json-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?> = 'Steve';
        $this->assertNull(<?php echo $typeMapClass; ?>::getTypeClassname($json));
    }

    public function testGetTypeClassnameWithTypeName()
    {
<?php foreach($types->getNamespaceSortedIterator() as $type) : ?>
        $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo $typeMapClass; ?>::getTypeClassname('<?php echo $type->getFHIRName(); ?>'));
<?php endforeach; ?>
    }

    public function testGetContainedTypeClassnameWithTypeName()
    {
<?php foreach($types->getNamespaceSortedIterator() as $type) :
    if ($type->isContainedType()) : ?>
        $this->assertEquals('<?php echo $type->getFullyQualifiedClassName(true); ?>', <?php echo $typeMapClass; ?>::getContainedTypeClassname('<?php echo $type->getFHIRName(); ?>'));
<?php else : ?>
        $this->assertNull(<?php echo $typeMapClass; ?>::getContainedTypeClassname('<?php echo $type->getFHIRName(); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithClassname()
    {
<?php foreach($types->getNamespaceSortedIterator() as $type) :
    if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo $typeMapClass; ?>::isContainableType('<?php echo $type->getFullyQualifiedClassName(false); ?>'), sprintf('Expected input "%s" to return true.', '<?php echo $type->getFullyQualifiedClassName(false); ?>'));
        $this->assertTrue(<?php echo $typeMapClass; ?>::isContainableType('<?php echo $type->getFullyQualifiedClassName(true); ?>'), sprintf('Expected input "%s" to return true.', '<?php echo $type->getFullyQualifiedClassName(true); ?>'));
<?php else : ?>
        $this->assertFalse(<?php echo $typeMapClass; ?>::isContainableType('<?php echo $type->getFullyQualifiedClassName(false); ?>'), sprintf('Expected input "%s" to return false.', '<?php echo $type->getFullyQualifiedClassName(false); ?>'));
        $this->assertFalse(<?php echo $typeMapClass; ?>::isContainableType('<?php echo $type->getFullyQualifiedClassName(true); ?>'), sprintf('Expected input "%s" to return false.', '<?php echo $type->getFullyQualifiedClassName(true); ?>'));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableResourceWithInstance()
    {
<?php foreach($types->getNamespaceSortedIterator() as $type) :
    if ($type->isAbstract()) {
        continue;
    } ?>
        $type = new <?php echo $type->getFullyQualifiedClassName(true); ?>;
<?php if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo $typeMapClass; ?>::isContainableType($type), sprintf('Expected instance of "%s" to return true.', $type::class));
        $this->assertTrue(<?php echo $typeMapClass; ?>::isContainableType($type->_getFHIRTypeName()), sprintf('Expected input "%s" to return true.', $type->_getFHIRTypeName()));
<?php else : ?>
        $this->assertFalse(<?php echo $typeMapClass; ?>::isContainableType($type), sprintf('Expected instance of "%s" to return false.', $type::class));
        $this->assertFalse(<?php echo $typeMapClass; ?>::isContainableType($type->_getFHIRTypeName()), sprintf('Expected input "%s" to return false.', $type->_getFHIRTypeName()));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableTypeWithXML()
    {
<?php foreach($types->getNameSortedIterator() as $type) :
    if ($type->isAbstract()) {
        continue;
    } ?>
        $sxe = new \SimpleXMLElement('<<?php echo $type->getFHIRName(); ?>></<?php echo $type->getFHIRName(); ?>>');
<?php if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo $typeMapClass; ?>::isContainableType($sxe), sprintf('Expected input "%s" to return true.', $sxe->saveXML()));
<?php else : ?>
        $this->assertFalse(<?php echo $typeMapClass; ?>::isContainableType($sxe), sprintf('Expected input "%s" to return false.', $sxe->saveXML()));
<?php endif;
endforeach; ?>
    }

    public function testIsContainableTypeWithJSON()
    {
<?php foreach($types->getNameSortedIterator() as $type) :
    if ($type->isAbstract()) {
        continue;
    } ?>
        $json = new \stdClass();
        $json-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?> = '<?php echo $type->getFHIRName(); ?>';
<?php if ($type->isContainedType()) : ?>
        $this->assertTrue(<?php echo $typeMapClass; ?>::isContainableType($json), sprintf('Expected input "%s" to return true.', var_export($json, true)));
<?php else : ?>
        $this->assertFalse(<?php echo $typeMapClass; ?>::isContainableType($json), sprintf('Expected input "%s" to return false.', var_export($json, true)));
<?php endif;
endforeach; ?>
    }
}
<?php
return ob_get_clean();