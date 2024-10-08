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
    public function setValue(<?php echo TypeHintUtils::typeSetterTypeHint($config, $type, true); ?> $value): self
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_string($value)) {
            $this->value = $value;
            return $this;
        }
        if ($value instanceof \DateTimeInterface) {
            $this->value = $value->format(<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR_MONTH_DAY);
            return $this;
        }
        throw new \InvalidArgumentException(sprintf('Value must be null, string, or instance of \\DateTimeInterface, %s seen.', gettype($value)));
    }

    /**
     * @return null|\DateTimeInterface
     */
    public function getDateTime(): null|\DateTimeInterface
    {
        $value = $this->getValue();
        if (null === $value) {
            return null;
        }
        switch(strlen($value)) {
            case 4:
                $dt = \DateTime::createFromFormat(<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR, $value);
                if (!($dt instanceof \DateTime)) {
                    throw new \UnexpectedValueException(sprintf('Unable to parse value "%s" into \DateTime instance with expected format "%s"', $value, <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR));
                }
                return $dt;
            case 7:
                $dt = \DateTime::createFromFormat(<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR_MONTH, $value);
                if (!($dt instanceof \DateTime)) {
                    throw new \UnexpectedValueException(sprintf('Unable to parse value "%s" into \DateTime instance with expected format "%s"', $value, <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR_MONTH));
                }
                return $dt;
            case 10:
                $dt = \DateTime::createFromFormat(<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR_MONTH_DAY, $value);
                if (!($dt instanceof \DateTime)) {
                    throw new \UnexpectedValueException(sprintf('Unable to parse value "%s" into \DateTime instance with expected format "%s"', $value, <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DATE_FORMAT_YEAR_MONTH_DAY));
                }
                return $dt;

            default:
                throw new \UnexpectedValueException(sprintf('Unable to parse value "%s" into \DateTime as it is not a value expected by the Date type', $value));
        }
    }

    /**
     * @return string
     */
    public function getFormattedValue(): string
    {
        return (string)$this->getValue();
    }
<?php return ob_get_clean();