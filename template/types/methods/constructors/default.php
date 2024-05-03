<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $properties */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */

$typeClassName = $type->getClassName();
$valueProperty = null;
if ($type->isValueContainer()) {
    $valueProperty = $type->getLocalProperties()->getProperty(PHPFHIR_VALUE_PROPERTY_NAME);
}

ob_start(); ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|array<?php if ($type->isValueContainer()) : ?>|<?php echo TypeHintUtils::propertySetterTypeHint($config, $valueProperty, false); endif; ?> $data
     */
    public function __construct(null|array<?php if ($type->isValueContainer()) : ?>|<?php echo TypeHintUtils::propertySetterTypeHint($config, $valueProperty, false); endif; ?> $data = null)
    {
        if (null === $data || [] === $data) {
            return;
        }
<?php if ($type->isValueContainer()) : ?>
        if (!is_array($data)) {
            $this->setValue($data);
            return;
        }
<?php endif; if ($type->hasParentWithLocalProperties()) : // add parent constructor call ?>
        parent::__construct($data);<?php endif; ?><?php if ($type->isCommentContainer() && !$type->hasCommentContainerParent()) : // only parse comments if parent isn't already doing it. ?>

        if (isset($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS])) {
            if (is_array($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_setFHIRComments($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS]);
            } elseif (is_string($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_addFHIRComment($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS]);
            }
        }<?php endif; ?>

<?php foreach($properties as $property) :
    if ($property->isOverloaded()) {
        continue;
    }
    if (($propType = $property->getValueFHIRType()) && $propType->getKind()->isOneOf(TypeKind::RESOURCE_INLINE, TypeKind::RESOURCE_CONTAINER)) :
        echo require_with(
                PHPFHIR_TEMPLATE_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'resource_container_property_setter_call.php',
                [
                    'config' => $config,
                    'type' => $type,
                    'property' => $property,
                ]
        );
    else :
        echo require_with(
                PHPFHIR_TEMPLATE_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'default_property_setter_call.php',
                [
                    'config' => $config,
                    'type' => $type,
                    'property' => $property
                ]
        );
    endif;
endforeach; ?>
    }
<?php return ob_get_clean();