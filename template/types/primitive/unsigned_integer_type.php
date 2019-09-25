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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */
/** @var string $typeClassName */

ob_start(); ?>
<?php
echo require_with(
    PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/primitive.php',
    [
        'primitiveType' => $primitiveType,
        'typeClassName' => $typeClassName
    ]
);
?>
    /**
     * @param null|integer|string
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_int($value)) {
            if (0 > $value) {
                throw new \OutOfBoundsException(sprintf('Value must be >= 0, %d seen.', $value));
            }
            $value = (string)$value;
        }
        if (!is_string($value) || !ctype_digit($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be null, positive integer, or string representation of positive integer, "%s" seen.', gettype($value)));
        }
        if ('' === $value) {
            $value = '0';
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
        return is_string($value) && ctype_digit($value);
    }

<?php return ob_get_clean();
