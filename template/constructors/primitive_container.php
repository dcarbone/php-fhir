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

use DCarbone\PHPFHIR\Utilities\ExceptionUtils;

/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */
/** @var string $typeClassName */

$valueProperty = $type->getProperties()->getProperty('value');
if (null === $valueProperty) {
    throw ExceptionUtils::createPrimitiveValuePropertyNotFound($type);
}

$valuePrimitiveType =$valueProperty->getValueFHIRType();
$valuePrimitiveTypeKind = $valuePrimitiveType->getPrimitiveType();

ob_start(); ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|array|<?php echo $valuePrimitiveTypeKind->getPHPValueType(); ?>|<?php echo $valuePrimitiveType->getFullyQualifiedClassName(true); ?> $data
     */
    public function __construct($data = null)
    {
        if (null === $data) {
            return;
        }
        if ($data instanceof <?php echo $valuePrimitiveType->getClassName(); ?>) {
            $this->setValue($data);
            return;
        }
        if (is_scalar($data)) {
            $this->setValue(new <?php echo $valuePrimitiveType->getClassName(); ?>($data));
            return;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf(
                '$data must be null, <?php echo $valuePrimitiveTypeKind->getPHPValueType(); ?>, instance of <?php echo $valuePrimitiveType->getFullyQualifiedClassName(true); ?>, or array.  %s seen.',
                gettype($data)
            ));
        }<?php if ($parentType) : ?>

        parent::__construct($data);
<?php endif; ?>
<?php foreach ($sortedProperties as $property) :
    echo require PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/property_setter_call_default.php';
endforeach; ?>
    }
<?php return ob_get_clean();
