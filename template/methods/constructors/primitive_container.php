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

use DCarbone\PHPFHIR\Utilities\ExceptionUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $properties */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */

$valueProperty = $type->getProperties()->getProperty('value');
if (null === $valueProperty) {
    throw ExceptionUtils::createPrimitiveValuePropertyNotFound($type);
}

$valuePrimitiveType = $valueProperty->getValueFHIRType();
$valuePrimitiveTypeKind = $valuePrimitiveType->getPrimitiveType();

$typeImports = $type->getImports();

ob_start(); ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param <?php echo TypeHintUtils::propertyTypeDoc($config, $valueProperty, true, 'array'); ?> $data
     */
    public function __construct($data = null)
    {
        if (null === $data) {
            return;
        }
        if ($data instanceof <?php echo $typeImports->getImportByType($valuePrimitiveType); ?>) {
            $this->setValue($data);
            return;
        }
        if (is_scalar($data)) {
            $this->setValue(new <?php echo $typeImports->getImportByType($valuePrimitiveType); ?>($data));
            return;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf(
                '$data must be null, <?php echo $valuePrimitiveTypeKind->getPHPValueType(); ?>, instance of <?php echo $valuePrimitiveType->getFullyQualifiedClassName(true); ?>, or array.  %s seen.',
                gettype($data)
            ));
        }<?php if ($parentType) : ?>

        parent::__construct($data);
<?php endif; ?><?php if (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>

        if (isset($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS])) {
            if (is_array($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_setFHIRComments($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS]);
            } elseif (is_string($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS])) {
                $this->_addFHIRComment($data[PHPFHIRConstants::JSON_FIELD_FHIR_COMMENTS]);
            }
        }<?php endif; ?>
<?php foreach ($properties as $property) :
    if ($property->isOverloaded()) :
        continue;
    endif;
    echo require_with(
            PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'default_property_setter_call.php',
            [
                'config' => $config,
                'type' => $type,
                'property' => $property,
            ]
    );
endforeach; ?>
    }
<?php return ob_get_clean();
