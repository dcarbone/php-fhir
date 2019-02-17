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


/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;

/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */
/** @var string $fhirName */
/** @var string $typeClassName */

$primitiveTypeString = (string)$primitiveType;

ob_start(); ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|integer|string $value
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }

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
<?php if (PrimitiveTypeEnum::POSITIVE_INTEGER === $primitiveTypeString) : ?>
        if (0 >= $value) {
            throw new \InvalidArgumentException(sprintf('Value must be > 0, "%d" seen.', $value));
        }
<?php elseif ($primitiveType::NEGATIVE_INTEGER === $primitiveTypeString) : ?>
        if (0 <= $value) {
            throw new \InvalidArgumentException(sprintf('Value must be < 0, "%d" seen));
        }
<?php else : ?>
        $this->value = $value;
<?php endif; ?>
        return $this;
    }

    /**
     * @return null|integer|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null|integer|string
     */
    public function jsonSerialize()
    {
        return $this->getValue();
    }

<?php return ob_get_clean();
