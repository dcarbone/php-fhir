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
    /** null|\DateTime */
    private $dateTime = null;

    const TIME_VALUE_REGEX = // language=RegExp
        '([01][0-9]|2[0-3]):[0-5][0-9]:([0-5][0-9]|60)(\.[0-9]+)?';
    const TIME_FORMAT      = 'H:i:s';

    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|string $value
     */
    public function __construct($value = null)
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
            $this->value = $this->dateTime = null;
            return $this;
        }
        if (is_string($value)) {
            $this->value = $value;
            $this->dateTime = null;
            return $this;
        }
        throw new \InvalidArgumentException(sprintf('$value must be null or string, %s seen.', gettype($value)));
    }

    /**
     * @return null|\DateTime
     */
    public function getDateTime()
    {
        if (!isset($this->dateTime)) {
            $value = $this->getValue();
            if (null === $value) {
                return null;
            }
            if (!$this->isValid()) {
                throw new \DomainException(sprintf('Cannot convert "%s" to \\DateTime as it does not conform to "%s"', $value, self::TIME_VALUE_REGEX));
            }
            $parsed = \DateTime::createFromFormat(self::TIME_FORMAT, $value);
            if (false === $parsed) {
                throw new \DomainException(sprintf('Value "%s" could not be parsed as <?php echo $fhirName; ?>: %s', $value, implode(', ', \DateTime::getLastErrors())));
            }
            $this->dateTime = $parsed;
        }
        return $this->dateTime;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        $value = $this->getValue();
        return null === $value || is_string($value) && preg_match('/' . self::TIME_VALUE_REGEX . '/', $value);
    }

    /**
     * @return null|string
     */
    public function getValue()
    {
        return $this->value;
    }

<?php return ob_get_clean();