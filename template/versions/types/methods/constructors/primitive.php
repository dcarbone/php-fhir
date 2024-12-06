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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type|null $parentType */

$typeClassName = $type->getClassName();
$primitiveType = $type->getPrimitiveType();
$valueProperty = $type->getLocalProperties()->getProperty('value');

if (null !== $parentType) {
    // if this is a primitive that inherits from a parent primitive, there is no reason to define a constructor here.
    if ($parentType->getKind() === TypeKind::PRIMITIVE || $parentType->isValueContainer()) {
        return;
    }
    
    // otherwise, assume this is a primitive type who's parent has properties other than just "value"
    ob_start(); ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true, false); ?>|array $value
     */
    public function __construct(<?php echo TypeHintUtils::primitiveValuePropertyTypeHint($version, $valueProperty, true); ?>|array $value = null)
    {
        if (null === $value) {
            parent::__construct();
        } elseif (is_scalar($value)) {
            parent::__construct();
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
    return ob_get_clean();
}

// in all other cases, just set value and move on.
ob_start(); ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true, false); ?> $value
     */
    public function __construct(<?php echo TypeHintUtils::typeSetterTypeHint($version, $type, true); ?> $value = null)
    {
        $this->setValue($value);
    }
<?php return ob_get_clean();