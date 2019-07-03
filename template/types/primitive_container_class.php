<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */

// define some common things
$fqns = $type->getFullyQualifiedNamespace(true);
$typeClassName = $type->getClassName();
$typeKind = $type->getKind();
$parentType = $type->getParentType();
$fhirName = $type->getFHIRName();
$sortedProperties = $type->getProperties()->getSortedIterator();
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);

ob_start();

// build file header
echo require PHPFHIR_TEMPLATE_FILE_DIR . '/header_type.php';

// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $typeClassName; ?>

 * @package <?php echo $fqns; ?>

 */
class <?php echo $typeClassName; ?><?php echo null !== $parentType ? " extends {$parentType->getClassName()}" : ' implements ' . PHPFHIR_INTERFACE_TYPE; ?>

{
    // name of FHIR type this class describes
    const FHIR_TYPE_NAME = <?php echo $type->getTypeNameConst(true); ?>;

<?php foreach($sortedProperties as $property) : ?>
<?php echo require PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/constants.php'; ?>

<?php endforeach; ?>
<?php foreach($sortedProperties as $property) : ?>
<?php echo require PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/declaration.php'; ?>

<?php endforeach; ?>
<?php echo require PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/primitive_container.php'; ?>

    /**
     * @return string
     */
    public function getFHIRTypeName()
    {
        return self::FHIR_TYPE_NAME;
    }

<?php echo require PHPFHIR_TEMPLATE_PROPERTIES_DIR . '/methods.php'; ?>

<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/xml.php'; ?>

<?php echo require PHPFHIR_TEMPLATE_SERIALIZATION_DIR . '/json.php'; ?>

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }
}<?php return ob_get_clean();