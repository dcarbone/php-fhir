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
    PHPFHIR_TEMPLATE_METHODS_DIR . '/constructor.php',
    [
        'primitiveType' => $primitiveType,
        'typeClassName' => $typeClassName,
        'type'          => $type,
    ]
);
?>
    /**
     * @param null|float|string $value
     * @return static
     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
        } elseif (is_scalar($value)) {
            $this->value = floatval($value);
        } else {
            throw new \InvalidArgumentException(sprintf('<?php echo $fhirName; ?> value must be null, float, or numeric string, %s seen.', gettype($value)));
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function _isValid()
    {
        return is_float($this->getValue());
    }

<?php return ob_get_clean();
