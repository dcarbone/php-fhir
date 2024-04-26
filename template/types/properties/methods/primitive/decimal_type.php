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
    /** @var int */
    private int $_decimals = 1;

    /** @var bool */
    private bool $_commas = false;

    /**
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeSetterDoc($config, $primitiveType, true, false); ?> $value
     * @return static
     */
    public function setValue(<?php echo TypeHintUtils::typeSetterTypeHint($config, $type, true); ?> $value): self
    {
        if (null === $value) {
            $this->value = null;
            $this->_decimals = 1;
            $this->_commas = false;
            return $this;
        }
        $str = (string)$value;
        $dec = strstr($str, '.');
        if ($this->_commas = str_contains($str, ',')) {
            $str = str_replace(',', '', $str);
        }
        if (false === $dec) {
            $this->_decimals = 0;
        } else {
            $this->_decimals = strlen($dec) - 1;
        }
        $this->value = floatval($str);
        return $this;
    }

    /**
     * @return string
     */
    public function getFormattedValue(): string
    {
        $v = $this->getValue();
        if (null === $v) {
            return '0.0';
        }
        return number_format($v, $this->_decimals, '.', $this->_commas ? ',' : '');
    }
<?php return ob_get_clean();
