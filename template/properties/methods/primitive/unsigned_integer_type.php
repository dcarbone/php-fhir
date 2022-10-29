<?php declare(strict_types=1);

/*
 * Copyright 2018-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

// TODO: use big numbers library here..

use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */

ob_start(); ?>
    /**
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeDoc($config, $primitiveType, true, false, 'int', 'float', 'string'); ?> $value
     * @return static
     */
    public function setValue($value): object
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_float($value) || is_string($value)) {
            $value = intval($value, 10);
        }
        if (is_int($value)) {
            if (0 > $value) {
                throw new \OutOfBoundsException(sprintf('Value must be >= 0, %d seen.', $value));
            }
            $value = strval($value);
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
<?php return ob_get_clean();
