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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>

{
    private const XML_START = ['<'];
    private const JSON_START = ['{', '['];

    /**
     * Attempts to parse the provided input into FHIR objects.
     *
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public static function parse(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version, null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (null === $input) {
            return null;
        } else if (is_string($input)) {
            return static::parseString($version, $input);
        } else if (is_array($input)) {
            return static::parseArray($version, $input);
        } else {
            return static::parseObject($version, $input);
        }
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param array $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public static function parseArray(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version, array $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if ([] === $input) {
            return null;
        }
        if (isset($input[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE])) {
            $className = $version->getTypeMap()::getTypeClassName($input[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
            if (null === $className) {
                throw new \UnexpectedValueException(sprintf(
                    'Provided input has "%s" value of "%s", but it does not map to any known type.  Other keys: ["%s"]',
                    <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE,
                    $input[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE],
                    implode('","', array_keys($input))
                ));
            }
            return new $className($input);
        }
        throw new \DomainException(sprintf(
            'Unable to determine FHIR Type from provided array: missing "%s" key.  Available keys: ["%s"]',
            <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE,
            implode('","', array_keys($input))
        ));
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param \stdClass $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public static function parseStdClass(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,\stdClass $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return static::parseArray($version, (array)$input);
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param \SimpleXMLElement $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public static function parseSimpleXMLElement(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,\SimpleXMLElement $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $elementName = $input->getName();
        /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?> $fhirType */
        $fhirType = $version->getTypeMap()::getTypeClassName($elementName);
        if (null === $fhirType) {
            throw new \UnexpectedValueException(sprintf(
                'Unable to locate FHIR type for root XML element "%s". Input seen: %s',
                $elementName,
                static::getPrintableStringInput($input->saveXML())
            ));
        }
        return $fhirType::xmlUnserialize($input, null, $version->getConfig()->getUnserializeConfig());
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param \DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public static function parseDOMDocument(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return static::parseSimpleXMLElement($version, simplexml_import_dom($input));
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param \stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public static function parseObject(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,\stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if ($input instanceof \stdClass) {
            return static::parseStdClass($version, $input);
        } elseif ($input instanceof \SimpleXMLElement) {
            return static::parseSimpleXMLElement($version, $input);
        } else {
            return static::parseDOMDocument($version, $input);
        }
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param string $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public static function parseXML(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,string $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return static::parseSimpleXMLElement($version, new \SimpleXMLElement($input, $version->getConfig()->getUnserializeConfig()->getLibxmlOpts()));
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param string $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public static function parseJSON(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,string $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $decoded = json_decode($input, true, $version->getConfig()->getUnserializeConfig()->getJSONDecodeMaxDepth());
        $err = json_last_error();
        if (JSON_ERROR_NONE !== $err) {
            throw new \DomainException(sprintf(
                'Unable to parse provided input as JSON.  Error: %s; Input: %s',
                json_last_error_msg(),
               static::getPrintableStringInput($input)
            ));
        }

        return static::parseArray($version, $decoded);
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION); ?> $version
     * @param string $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public static function parseString(<?php echo PHPFHIR_INTERFACE_VERSION; ?> $version,string $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $input = trim($input);
        if ('' === $input) {
            return null;
        }
        $chr = $input[0];
        if (in_array($chr, self::XML_START, true)) {
            return static::parseXML($version, $input);
        } elseif (in_array($chr, self::JSON_START, true)) {
            return static::parseJSON($version, $input);
        }
        throw new \UnexpectedValueException(sprintf(
            'Input string must be either XML or JSON encoded object.  Provided: %s',
           static::getPrintableStringInput($input)
        ));
    }

    /**
     * @param string $input
     * @return string
     */
    protected static function getPrintableStringInput(string $input): string
    {
        if (strlen($input) > 100) {
            return sprintf('%s[...]', substr($input, 0, 100));
        }
        return $input;
    }
}
<?php return ob_get_clean();