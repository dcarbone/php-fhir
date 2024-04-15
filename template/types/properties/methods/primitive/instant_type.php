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
     * @param <?php echo TypeHintUtils::primitivePHPValueTypeDoc($config, $primitiveType, true, false, '\\DateTimeInterface'); ?> $value
     * @return static
     */
    public function setValue($value): object
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
            $this->value = $value->format(PHPFHIRConstants::DATE_FORMAT_INSTANT);
            return $this;
        }
        throw new \InvalidArgumentException(sprintf('Value must be null, string, or instance of \\DateTimeInterface, %s seen.', gettype($value)));
    }

    /**
     * @return null|\DateTimeInterface
     */
    public function _getDateTime(): ?\DateTimeInterface
    {
        $value = $this->getValue();
        if (null === $value) {
            return null;
        }
        if ([] !== $this->_getValidationErrors()) {
            throw new \DomainException(sprintf(
                'Cannot convert "%s" to \\DateTime as it does not conform to "%s"',
                $value,
                self::$_validationRules[self::FIELD_VALUE][PHPFHIRConstants::<?php echo PHPFHIR_VALIDATION_PATTERN_NAME; ?>]
            ));
        }
        $dt = \DateTime::createFromFormat(PHPFHIRConstants::DATE_FORMAT_INSTANT, $value);
        if (!($dt instanceof \DateTime)) {
            throw new \UnexpectedValueException(sprintf('Unable to parse value "%s" into \DateTime instance with expected format "%s"', $value, PHPFHIRConstants::DATE_FORMAT_INSTANT));
        }
        return $dt;
    }
<?php return ob_get_clean();