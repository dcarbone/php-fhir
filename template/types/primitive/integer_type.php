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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */

$primitiveTypeString = (string)$primitiveType;

ob_start(); ?>
    const INT_MAX = 2147483648;
    const INT_MIN = -2147483648;

<?php echo require PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR.'/primitive_types.php'; ?>

    /**
     * @var null|integer|string $value
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
        }
        if (is_string($value) && ctype_digit($value)) {
            $value = (int)$value;
        }
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be null, integer, or string containing only numbers, %s seen.', $value));
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $value = $this->getValue();
        if (null === $value) {
            return true;
        }
<?php if (PrimitiveTypeEnum::POSITIVE_INTEGER === $primitiveTypeString) : ?>
        return 0 < $value && $value <= self::INT_MAX;
<?php elseif (PrimitiveTypeEnum::NEGATIVE_INTEGER === $primitiveTypeString) : ?>
        return 0 > $value && $value >= self::INT_MIN;
<?php elseif (PrimitiveTypeEnum::UNSIGNED_INTEGER === $primitiveTypeString) : ?>
        return 0 <= $value && $value <= self::INT_MAX;
<?php else : ?>
        return self::INT_MIN <= $value && $value <= self::INT_MAX;
<?php endif; ?>
    }

<?php return ob_get_clean();
