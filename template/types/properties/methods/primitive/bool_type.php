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

use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveType $primitiveType */

ob_start(); ?>
    /**
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($config, $primitiveType, true, false); ?> $value
     * @return static
     */
    public function setValue(<?php echo TypeHintUtils::typeSetterTypeHint($config, $type, true); ?> $value = null): self
    {
        if (null === $value) {
            $this->value = null;
        } elseif (is_string($value)) {
            $this->value = <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::STRING_TRUE === strtolower($value);
        } else {
            $this->value = (bool)$value;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedValue(): string
    {
        return $this->getValue() ? <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::STRING_TRUE : <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::STRING_FALSE;
    }
<?php return ob_get_clean();