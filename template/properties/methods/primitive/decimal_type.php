<?php

/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

ob_start(); ?>
    /** @var int */
    private $_decimals;

    /**
     * @param null|float|string $value
     * @return static
     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
        } elseif (is_scalar($value)) {
            if (is_string($value)) {
                $this->_decimals = strlen(strstr($value, '.')) - 1;
            }
            $this->value = floatval($value);
        } else {
            throw new \InvalidArgumentException(sprintf('<?php echo $type->getFHIRName(); ?> value must be null, float, or numeric string, %s seen.', gettype($value)));
        }
        return $this;
    }
<?php return ob_get_clean();
