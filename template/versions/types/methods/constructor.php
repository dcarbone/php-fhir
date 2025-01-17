<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$coreFiles = $version->getConfig()->getCoreFiles();
$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

$typeKind = $type->getKind();
$typeClassName = $type->getClassName();
$typeImports = $type->getImports();

$parentType = $type->getParentType();
$properties = $type->getProperties();

// used in a few places below.
$valueProperty = $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);

$propertyCount = count($properties);
$parentPropertyCount = count($type->getParentPropertiesIterator());
$totalPropertyCount = $propertyCount + $parentPropertyCount;

ob_start();

if ($typeKind->isOneOf(TypeKindeNum::PRIMITIVE, TypeKindEnum::LIST)) :
    $primitiveType = $type->getPrimitiveType();

    // only define constructor if this primitive does not have a parent.
    if (null === $parentType) : ?>

    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true); ?> $value
     * @param <?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?> $valueXMLLocation
     */
    public function __construct(<?php echo TypeHintUtils::buildSetterParameterHint($version, $valueProperty, true); ?> $value = null,
                                <?php echo $valueXMLLocationEnum->getEntityName(); ?> $valueXMLLocation = <?php echo $valueXMLLocationEnum->getEntityName(); ?>::ATTRIBUTE)
    {
        $this->setValue(value: $value);
        $this->_setValueXMLLocation($valueXMLLocation);
    }
<?php
    endif;
else : ?>

    /**
     * <?php echo $typeClassName; ?> Constructor
<?php foreach($type->getAllPropertiesIndexedIterator() as $property) :
        $propType = $property->getValueFHIRType();
        $propTypeKind = $propType->getKind();
?>
     * @param <?php echo TypeHintUtils::buildSetterParameterDocHint($version, $property, true); ?> $<?php echo $property->getName(); if ($property->isValueProperty()) : ?>

     * @param null|<?php echo $valueXMLLocationEnum->getFullyQualifiedName(true); ?> $valueXMLLocation<?php endif; ?>

<?php endforeach; if ($type->hasCommentContainerParent() || $type->isCommentContainer()) : ?>
     * @param null|string[] $fhirComments
<?php endif; ?>     */
    public function __construct(<?php foreach($type->getAllPropertiesIndexedIterator() as $i => $property) : if ($i > 0) : ?>,
                                <?php endif; echo TypeHintUtils::buildSetterParameterHint($version, $property, true); ?> $<?php echo $property->getName(); ?> = null<?php if ($property->isValueProperty()) : ?>,
                                null|<?php echo $valueXMLLocationEnum->getEntityName(); ?> $valueXMLLocation = null<?php endif; endforeach;
                                if ($type->hasCommentContainerParent() || $type->isCommentContainer()) : if ($totalPropertyCount > 0) : ?>,
                                <?php endif; ?>null|iterable $fhirComments = null<?php endif; ?>)
    {
<?php if (null !== $parentType) : ?>
        parent::__construct(<?php foreach($type->getParentPropertiesIterator() as $i => $property) : if ($i > 0) : ?>,
                            <?php endif; echo $property->getName(); ?>: $<?php echo $property->getName(); ?><?php endforeach; ?><?php
        if ($type->hasCommentContainerParent()) :
            if ($parentPropertyCount > 0) : ?>,
                            <?php endif; ?>fhirComments: $fhirComments<?php endif;
                            if ($type->hasValueContainerParent() || $type->hasPrimitiveContainerParent()) :
                               if ($parentPropertyCount > 0) : ?>,
                            <?php endif; ?>valueXMLLocation: $valueXMLLocation<?php endif; ?>);
<?php endif;
if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>
        if (null !== $fhirComments && [] !== $fhirComments) {
            $this->_setFHIRComments($fhirComments);
        }
<?php endif;

foreach($properties->getIterator() as $property) :
    if ($property->getOverloadedProperty()) {
        continue;
    } ?>
        if (null !== $<?php echo $property->getName(); ?>) {
<?php if ($property->isCollection()) : ?>
            $this->set<?php echo ucfirst($property->getName()); ?>(...$<?php echo $property->getName(); ?>);
<?php else : ?>
            $this-><?php echo $property->getSetterName(); ?>($<?php echo $property->getName(); if ($property->isValueProperty() && ($type->isValueContainer() || $type->isPrimitiveContainer())) : ?>, valueXMLLocation: $valueXMLLocation<?php endif; ?>);
<?php endif; ?>
        }
<?php
endforeach; ?>
    }
<?php endif;

return ob_get_clean();