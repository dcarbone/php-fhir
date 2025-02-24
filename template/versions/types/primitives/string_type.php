<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */

ob_start(); ?>
    /**
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($version, $primitiveType, true, false); ?> $value
     * @return static
     */
    public function setValue(<?php echo TypeHintUtils::typeSetterTypeHint($version, $type, true); ?> $value): self
    {
        if (null === $value) {
            unset($this->value);
        } else {
            $this->value = $value;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function _getValueAsString(): string
    {
        return (string)$this->getValue();
    }

    public function jsonSerialize(): string
    {
        return $this->value ?? '';
    }
<?php return ob_get_clean();
