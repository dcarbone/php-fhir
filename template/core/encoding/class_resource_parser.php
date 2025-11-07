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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$versionInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION);
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
$constantsClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CONSTANTS);

$imports = $coreFile->getImports();

$imports->addCoreFileImports(
    $versionInterface,
    $resourceTypeInterface,
    $constantsClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?>

{
    private const XML_START = ['<'];
    private const JSON_START = ['{', '['];

    /**
     * Attempts to parse the provided input into FHIR objects.
     *
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     * @throws \Exception
     */
    public static function parse(<?php echo $versionInterface; ?> $version,
                                 null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo $resourceTypeInterface; ?>

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
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param array $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     */
    public static function parseArray(<?php echo $versionInterface; ?> $version, array $input): null|<?php echo $resourceTypeInterface; ?>

    {
        if ([] === $input) {
            return null;
        }
        return static::parseStdClass($version, (object)$input);
    }

    /**
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param \stdClass $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     */
    public static function parseStdClass(<?php echo $versionInterface; ?> $version, \stdClass $input): null|<?php echo $resourceTypeInterface; ?>

    {
        if (isset($input-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>)) {
            /** @var <?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?> $className */
            $className = $version->getTypeMap()::getTypeClassname($input-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>);
            if (null === $className) {
                throw new \UnexpectedValueException(sprintf(
                    'Provided input has "%s" value of "%s", but it does not map to any known type.  Other keys: ["%s"]',
                    <?php echo $constantsClass; ?>::JSON_FIELD_RESOURCE_TYPE,
                    $input-><?php echo PHPFHIR_JSON_FIELD_RESOURCE_TYPE; ?>,
                    implode('","', array_keys((array)$input))
                ));
            }
            return $className::jsonUnserialize($input, $version->getConfig()->getUnserializeConfig());
        }
        throw new \DomainException(sprintf(
            'Unable to determine FHIR Type from provided array: missing "%s" key.  Available keys: ["%s"]',
            <?php echo $constantsClass; ?>::JSON_FIELD_RESOURCE_TYPE,
            implode('","', array_keys((array)$input))
        ));
    }

    /**
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param \SimpleXMLElement $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     */
    public static function parseSimpleXMLElement(<?php echo $versionInterface; ?> $version, \SimpleXMLElement $input): null|<?php echo $resourceTypeInterface; ?>

    {
        $elementName = $input->getName();
        /** @var <?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?> $fhirType */
        $fhirType = $version->getTypeMap()::getTypeClassname($elementName);
        if (null === $fhirType) {
            throw new \UnexpectedValueException(sprintf(
                'Unable to locate FHIR type for root XML element "%s". Input seen: %s',
                $elementName,
                static::getPrintableStringInput($input->saveXML())
            ));
        }
        return $fhirType::xmlUnserialize($input, $version->getConfig()->getUnserializeConfig());
    }

    /**
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param \DOMDocument $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     */
    public static function parseDOMDocument(<?php echo $versionInterface; ?> $version, \DOMDocument $input): null|<?php echo $resourceTypeInterface; ?>

    {
        return static::parseSimpleXMLElement($version, simplexml_import_dom($input));
    }

    /**
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param \stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     */
    public static function parseObject(<?php echo $versionInterface; ?> $version,
                                       \stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo $resourceTypeInterface; ?>

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
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param string $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     * @throws \Exception
     */
    public static function parseXML(<?php echo $versionInterface; ?> $version, string $input): null|<?php echo $resourceTypeInterface; ?>

    {
        return static::parseSimpleXMLElement(
            $version,
            new \SimpleXMLElement($input, $version->getConfig()->getUnserializeConfig()->getLibxmlOpts()));
    }

    /**
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param string $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     */
    public static function parseJSON(<?php echo $versionInterface; ?> $version, string $input): null|<?php echo $resourceTypeInterface; ?>

    {
        $config = $version->getConfig()->getUnserializeConfig();
        $decoded = json_decode(
            json: $input,
            associative: false,
            depth: $config->getJSONDecodeMaxDepth(),
            flags: $config->getJSONDecodeOpts(),
        );
        $err = json_last_error();
        if (JSON_ERROR_NONE !== $err) {
            throw new \DomainException(sprintf(
                'Unable to parse provided input as JSON.  Error: %s; Input: %s',
                json_last_error_msg(),
               static::getPrintableStringInput($input)
            ));
        }

        return static::parseStdClass($version, $decoded);
    }

    /**
     * @param <?php echo $versionInterface->getFullyQualifiedName(true); ?> $version
     * @param string $input
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     * @throws \Exception
     */
    public static function parseString(<?php echo $versionInterface; ?> $version, string $input): null|<?php echo $resourceTypeInterface; ?>

    {
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
