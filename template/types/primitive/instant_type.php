<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var string $typeClassName */

ob_start(); ?>
    /** @var null|\DateTime */
    private $dateTime = null;

    const VALUE_REGEX    = // language=RegExp
        '([0-9]([0-9]([0-9][1-9]|[1-9]0)|[1-9]00)|[1-9]000)-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])T([01][0-9]|2[0-3]):[0-5][0-9]:([0-5][0-9]|60)(\.[0-9]+)?(Z|(\+|-)((0[0-9]|1[0-3]):[0-5][0-9]|14:00))';
    const FORMAT_INSTANT = 'Y-m-d\\TH:i:s\\.uP';

<?php
echo require_with(
    PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/primitive.php',
    [
        'primitiveType' => $primitiveType,
        'typeClassName' => $typeClassName
    ]
);
?>
    /**
     * @param null|string $value
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public function setValue($value)
    {
        $this->dateTime = null;
        if (null === $value) {
            $this->value = null;
            return $this;
        }
        if (is_string($value)) {
            $this->value = $value;
            return $this;
        }
        throw new \InvalidArgumentException(sprintf('Value must be null, string of proper format, or instance of \\DateTime, %s seen.', gettype($value)));
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
                throw new \DomainException(sprintf('Cannot convert "%s" to \\DateTime as it does not conform to "%s"', $value, self::VALUE_REGEX));
            }
            $parsed = \DateTime::createFromFormat(self::FORMAT_INSTANT, $value);
            if (false === $value) {
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
        return null === $value || preg_match('/' . self::VALUE_REGEX . '/', $value);
    }

<?php return ob_get_clean();