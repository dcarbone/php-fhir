<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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


/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum $primitiveType */
/** @var string $fhirName */
/** @var string $typeClassName */

ob_start(); ?>
    const DATE_FORMAT_REGEX          = // language=RegExp
        '([0-9]([0-9]([0-9][1-9]|[1-9]0)|[1-9]00)|[1-9]000)(-(0[1-9]|1[0-2])(-(0[1-9]|[1-2][0-9]|3[0-1]))?)?';
    const DATE_FORMAT_YEAR           = 'Y';
    const DATE_FORMAT_YEAR_MONTH     = 'Y-m';
    const DATE_FORMAT_YEAR_MONTH_DAY = 'Y-m-d';

    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|string $value
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * @var null|string $value
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function setValue($value)
    {
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_string($value) && preg_match('/' . self::DATE_FORMAT_REGEX . '/', $value)) {
            switch(strlen($value)) {
                case 4:
                    $parsed = \DateTime::createFromFormat(self::DATE_FORMAT_YEAR, $value);
                    break;
                case 7:
                    $parsed = \DateTime::createFromFormat(self::DATE_FORMAT_YEAR_MONTH, $value);
                    break;
                case 10:
                    $parsed = \DateTime::createFromFormat(self::DATE_FORMAT_YEAR_MONTH_DAY, $value);
                    break;

                default:
                    throw new \DomainException(sprintf('Value expected to meet %s, %s seen', self::DATE_FORMAT_REGEX, $value));
            }
            if (false === $value) {
                throw new \DomainException(sprintf('Value "%s" could not be parsed as <?php echo $fhirName; ?>: %s', implode(', ', \DateTime::getLastErrors())));
            }
            $parsed = $value;
        }
        if (!($value instanceof \DateTime)) {
            throw new \InvalidArgumentException(sprintf('Value must be null, string of proper format, or instance of \\DateTime, %s seen.', gettype($value)));
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null|string
     */
    public function jsonSerialize()
    {
        return $this->getValue();
    }

<?php return ob_get_clean();