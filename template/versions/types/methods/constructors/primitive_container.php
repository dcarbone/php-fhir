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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Property[] $properties */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type|null $parentType */

$valueProperty = $type->getLocalProperties()->getProperty('value');
if (null === $valueProperty) {
    throw ExceptionUtils::createPrimitiveValuePropertyNotFound($type);
}

$valuePropertyType = $valueProperty->getValueFHIRType();
$valuePropertyPrimitiveType = $valuePropertyType->getPrimitiveType();

$typeImports = $type->getImports();

ob_start(); ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param <?php echo TypeHintUtils::typeSetterTypeHint($version, $valuePropertyType, true); ?>|<?php echo $valuePropertyType->getClassName(); ?>|array $data
     */
    public function __construct(<?php echo TypeHintUtils::propertySetterTypeHint($version, $valueProperty, true); ?>|array $data = null)
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
<?php foreach ($properties as $property) :
    if ($property->isOverloaded()) :
        continue;
    endif;
    echo require_with(
            PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR . DIRECTORY_SEPARATOR . 'default_property_setter_call.php',
            [
                'version' => $version,
                'type' => $type,
                'property' => $property,
            ]
    );
endforeach; ?>
    }
<?php return ob_get_clean();
