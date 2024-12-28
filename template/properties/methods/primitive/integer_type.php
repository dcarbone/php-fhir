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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */
/** @var string $typeClassName */

$primitiveTypeString = (string)$primitiveType;

ob_start(); ?>
    /**
     * @param null|integer|string $value
     * @return static
     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_string($value)) {
            if ('' === $value) {
                $value = 0;
            } else {
                $neg = 1;
                if ('-' === $value[0]) {
                    $neg = -1;
                    $value = substr($value, 1);
                }
                $value = $neg * intval($value, 10);
            }
        }
        if (!is_int($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be null, integer, or string containing only numbers, %s seen.', $value));
        }
        $this->value = $value;
        return $this;
    }
<?php return ob_get_clean();
