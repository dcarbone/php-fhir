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
$xmlLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_XML_LOCATION);

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
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true, false); ?> $value
     * @param <?php echo $xmlLocationEnum->getFullyQualifiedName(true); ?> $xmlLocation
     */
    public function __construct(<?php echo TypeHintUtils::buildSetterParameterHint($version, $valueProperty, true); ?> $value = null,
                                <?php echo $xmlLocationEnum->getEntityName(); ?> $xmlLocation = <?php echo $xmlLocationEnum->getEntityName(); ?>::ATTRIBUTE)
    {
        $this->setValue(value: $value);
        $this->setXMLLocation($xmlLocation);
    }
<?php
    endif;
else : ?>

    /**
     * <?php echo $typeClassName; ?> Constructor
<?php foreach($type->getAllPropertiesIndexedIterator() as $property) :
        $pt = $property->getValueFHIRType(); ?>
     * @param <?php echo TypeHintUtils::buildSetterParameterDocHint($version, $property, true); ?> $<?php echo $property->getName(); ?>

<?php endforeach; if ($type->hasCommentContainerParent() || $type->isCommentContainer()) : ?>
     * @param null|string[] $fhirComments
<?php endif;
if ($type->isValueContainer() || $type->hasValueContainerParent() || $type->hasPrimitiveContainerParent() || $typeKind === TypeKindEnum::PRIMITIVE_CONTAINER) : ?>
    * @param <?php echo $xmlLocationEnum->getFullyQualifiedName(true); ?> $xmlLocation
<?php endif; ?>     */
    public function __construct(<?php foreach($type->getAllPropertiesIndexedIterator() as $i => $property) : if ($i > 0) : ?>,
                                <?php endif; echo TypeHintUtils::buildSetterParameterHint($version, $property, true); ?> $<?php echo $property->getName(); ?> = null<?php endforeach;
                                if ($type->hasCommentContainerParent() || $type->isCommentContainer()) : if ($totalPropertyCount > 0) : ?>,
                                <?php endif; ?>null|iterable $fhirComments = null<?php endif;
                                if ($type->isValueContainer() || $type->hasValueContainerParent() || $type->hasPrimitiveContainerParent() || $typeKind === TypeKindEnum::PRIMITIVE_CONTAINER) : ?>,
                                <?php echo $xmlLocationEnum->getEntityName(); ?> $xmlLocation = <?php echo $xmlLocationEnum->getEntityName(); ?>::<?php if ($type->isValueContainer()) : ?>ELEMENT<?php else : ?>ATTRIBUTE<?php endif; endif; ?>)
    {
<?php if (null !== $parentType) : ?>
        parent::__construct(<?php foreach($type->getParentPropertiesIterator() as $i => $property) : if ($i > 0) : ?>,
                            <?php endif; echo $property->getName(); ?>: $<?php echo $property->getName(); ?><?php endforeach; ?><?php
        if ($type->hasCommentContainerParent()) :
            if ($parentPropertyCount > 0) : ?>,
                            <?php endif; ?>fhirComments: $fhirComments<?php endif;
                            if ($type->hasValueContainerParent() || $type->hasPrimitiveContainerParent()) :
                               if ($parentPropertyCount > 0) : ?>,
                                   <?php endif; echo $xmlLocationEnum->getEntityName(); ?>: $xmlLocation<?php endif; ?>);
<?php endif;
if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>
        if (null !== $fhirComments && [] !== $fhirComments) {
            $this->_setFHIRComments($fhirComments);
        }
<?php endif;
if (($type->isValueContainer() || $typeKind === TypeKindEnum::PRIMITIVE_CONTAINER) && !($type->hasPrimitiveContainerParent() || $type->hasValueContainerParent())) : ?>
        $this->setXMLLocation($xmlLocation);
<?php endif;

foreach($properties->getIterator() as $property) :
    if ($property->getOverloadedProperty()) {
        continue;
    }
    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'default_property_setter_call.php',
        [
            'type' => $type,
            'property' => $property
        ]
    );
endforeach; ?>
    }
<?php endif;

return ob_get_clean();