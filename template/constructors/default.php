<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */

$typeClassName = $type->getClassName();

ob_start(); ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|array $data
     */
    public function __construct($data = null)
    {
        if (null === $data || [] === $data) {
            return;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf(
                '<?php echo $typeClassName; ?>::_construct - $data expected to be null or array, %s seen',
                gettype($data)
            ));
        }<?php if ($parentType) : ?>

        parent::__construct($data);<?php endif; ?><?php if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>

        if (isset($data[self::FIELD_FHIR_COMMENTS])) {
            if (is_array($data[self::FIELD_FHIR_COMMENTS])) {
                $this->_setFHIRComments($data[self::FIELD_FHIR_COMMENTS]);
            } else if (is_string($data[self::FIELD_FHIR_COMMENTS])) {
                $this->_addFHIRComment($data[self::FIELD_FHIR_COMMENTS]);
            }
        }<?php endif; ?>

<?php foreach($sortedProperties as $property) :
    if (($propType = $property->getValueFHIRType()) && $propType->getKind()->isOneOf([TypeKindEnum::RESOURCE_INLINE, TypeKindEnum::RESOURCE_CONTAINER])) :
        echo require_with(
                PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/property_setter_call_resource_container.php',
                [
                        'type' => $type,
                        'property' => $property,
                ]
        );
    else :
        echo require_with(
                PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/property_setter_call_default.php',
                [
                        'type' => $type,
                        'property' => $property
                ]
        );
    endif;
endforeach; ?>
    }
<?php return ob_get_clean();