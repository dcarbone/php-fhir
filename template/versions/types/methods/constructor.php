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

$typeKind = $type->getKind();
$typeClassName = $type->getClassName();
$typeImports = $type->getImports();

$parentType = $type->getParentType();
$properties = $type->getProperties();

// used in a few places below.
$valueProperty = $type->getProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);

ob_start();

if ($typeKind->isOneOf(TypeKindeNum::PRIMITIVE, TypeKindEnum::LIST)) :
    $primitiveType = $type->getPrimitiveType();

    if (null !== $parentType) :
        // only define constructor if this parent has more than just a "value" property.

        if ($parentType->getKind() !== TypeKindeNum::PRIMITIVE && !$parentType->isValueContainer()) : ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true, false); ?>|array $value
     */
    public function __construct(<?php echo TypeHintUtils::primitiveValuePropertyTypeHint($version, $valueProperty, true); ?>|array $value = null)
    {
        if (null === $value) {
            parent::__construct(null);
        } elseif (is_scalar($value)) {
            parent::__construct(null);
            $this->setValue($value);
        } elseif (is_array($value)) {
            parent::__construct($value);
            if (array_key_exists(self::FIELD_VALUE, $value)) {
                $this->setValue($value[self::FIELD_VALUE]);
            }
        } else {
             throw new \InvalidArgumentException(sprintf(
                '<?php echo $typeClassName; ?>::__construct - $data expected to be null, <?php echo $primitiveType->getPHPValueTypes(); ?>, or array, %s seen',
                gettype($value)
            ));
        }
    }
<?php
        endif;
    else :
        // in all other cases, just set value and move on.
        ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true, false); ?> $value
     */
    public function __construct(<?php echo TypeHintUtils::typeSetterTypeHint($version, $type, true); ?> $value = null)
    {
        $this->setValue($value);
    }
<?php
    endif;

elseif ($typeKind === TypeKindEnum::PRIMITIVE_CONTAINER) :
    $valuePropertyType = $valueProperty->getValueFHIRType();
    $valuePropertyPrimitiveType = $valuePropertyType->getPrimitiveType();
?>

    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|array $data
     * @param <?php echo TypeHintUtils::typeSetterTypeHint($version, $valuePropertyType, true); ?>|<?php echo $valuePropertyType->getClassName(); ?> $value
<?php foreach($type->getAllPropertiesIndexedIterator() as $property) :
    if ($property->isValueProperty()) {
        continue;
    }
    ?>
     * @param <?php echo TypeHintUtils::propertySetterTypeHint($version, $property, true); ?> $<?php echo $property->getName(); ?>

<?php endforeach; ?>     */
    public function __construct(null|array $data = null,
                                <?php echo TypeHintUtils::propertySetterTypeHint($version, $valueProperty, true); ?> $value = null<?php foreach($type->getAllPropertiesIndexedIterator() as $property) :
    if ($property->isValueProperty()) {
        continue;
    }
    ?>,
                                <?php echo TypeHintUtils::propertySetterTypeHint($version, $property, true); ?> $<?php echo $property->getName(); ?> = null<?php endforeach; ?>)
    {
        if (null === $data) {
            return;
        }
        if (is_scalar($data) || $data instanceof <?php echo $typeImports->getImportByType($valuePropertyType); ?>) {
            $this->setValue($data);
            return;
        }<?php if (null !== $parentType) : ?>

        parent::__construct($data);
<?php endif; ?><?php if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>

        if (isset($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
            if (is_array($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_setFHIRComments($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS]);
            } elseif (is_string($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_addFHIRComment($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS]);
            }
        }<?php endif; ?>
<?php foreach ($properties->getGenerator() as $property) :
    if ($property->getOverloadedProperty()) {
        continue;
    }

    echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'default_property_setter_call.php',
        [
            'property' => $property,
        ]
    );
endforeach; ?>
    }
<?php else : ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|array $data
<?php if ($type->isValueContainer()) : ?>

     * @param <?php echo TypeHintUtils::propertySetterTypeHint($version, $valueProperty, true); ?> $value = null
<?php endif; ?>
<?php foreach($type->getAllPropertiesIndexedIterator() as $property) :
        $pt = $property->getValueFHIRType();
        if ($type->isValueContainer() && $property->isValueProperty()) {
            continue;
        }
        ?>
     * @param <?php echo TypeHintUtils::propertySetterTypeHint($version, $property, true); ?> $<?php echo $property->getName(); ?>

<?php endforeach; ?>     */
    public function __construct(null|array $data = null<?php if ($type->isValueContainer()) : ?>,
                                <?php echo TypeHintUtils::propertySetterTypeHint($version, $valueProperty, true);?> $value = null<?php endif; ?>
<?php foreach($type->getAllPropertiesIndexedIterator() as $property) :
        if ($type->isValueContainer() && $property->isValueProperty()) {
            continue;
        }
    ?>,
                                <?php echo TypeHintUtils::propertySetterTypeHint($version, $property, true); ?> $<?php echo $property->getName(); ?>
<?php endforeach; ?>)
    {
        if (null === $data || [] === $data) {
<?php if ($type->hasParent()) : ?>
            parent::__construct(null);
<?php endif; ?>
            return;
        }<?php if ($type->isValueContainer()) : ?>

        if (!is_array($data)) {
<?php if ($type->hasParent()) : ?>
            parent::__construct(null);
<?php endif; ?>
            $this->setValue($data);
            return;
        }<?php endif; if ($type->hasParentWithLocalProperties() || $type->hasCommentContainerParent()) : // add parent constructor call ?>

        parent::__construct($data);<?php endif; ?><?php if ($type->isCommentContainer() && !$type->hasCommentContainerParent()) : // only parse comments if parent isn't already doing it. ?>

        if (isset($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
            if (is_array($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_setFHIRComments($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS]);
            } elseif (is_string($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_addFHIRComment($data[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_FHIR_COMMENTS]);
            }
        }<?php endif; ?>

<?php foreach($properties->getGenerator() as $property) :
    if ($property->getOverloadedProperty()) {
        continue;
    }
    if (($propType = $property->getValueFHIRType()) && $propType->getKind()->isOneOf(TypeKindEnum::RESOURCE_INLINE, TypeKindEnum::RESOURCE_CONTAINER)) :
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'resource_container_property_setter_call.php',
            [
                'type' => $type,
                'property' => $property,
            ]
        );
    else :
        echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'default_property_setter_call.php',
            [
                'type' => $type,
                'property' => $property
            ]
        );
    endif;
endforeach; ?>
    }
<?php endif;

return ob_get_clean();