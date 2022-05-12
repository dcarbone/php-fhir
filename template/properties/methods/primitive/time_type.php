<?php declare(strict_types=1);

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
    /**
     * @param null|string $value
     * @return static
     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_string($value)) {
            $this->value = $value;
            return $this;
        }
        throw new \InvalidArgumentException(sprintf('$value must be null or string, %s seen.', gettype($value)));
    }

    /**
     * @return null|\DateTime|false
     */
    public function _getDateTime()
    {
        $value = $this->getValue();
        if (null === $value) {
            return null;
        }
        if ([] !== $this->_getValidationErrors()) {
            throw new \DomainException(sprintf(
                'Cannot convert "%s" to \\DateTime as it does not conform to "%s"',
                $value,
                self::$_fieldValidation[self::FIELD_VALUE][PHPFHIRConstants::PHPFHIR_VALIDATION_ENUM_NAME]
            ));
        }
        return \DateTime::createFromFormat(PHPFHIRConstants::TIME_FORMAT, $value);
    }
<?php return ob_get_clean();