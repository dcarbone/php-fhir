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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>

/**
 * Class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>

{
    private const XML_START = ['<'];
    private const JSON_START = ['{', '['];

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config */
    private <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config;

    /**
     * <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> Constructor
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     */
    public function __construct(null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null)
    {
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>;
        }
        $this->config = $config;
    }

    /**
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?>

     */
    public function getConfig(): <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>

    {
        return $this->config;
    }

    /**
     * Attempts to parse the provided input into FHIR objects.
     *
     * @param null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function parse(null|string|array|\stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (null === $input) {
            return null;
        } else if (is_string($input)) {
            return $this->parseString($input);
        } else if (is_array($input)) {
            return $this->parseArray($input);
        } else {
            return $this->parseObject($input);
        }
    }

    /**
     * @param array $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public function parseArray(array $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if ([] === $input) {
            return null;
        }
        if (isset($input[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE])) {
            $className = <?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getTypeClass($input[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
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
     * @param \stdClass $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public function parseStdClass(\stdClass $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return $this->parseArray((array)$input);
    }

    /**
     * @param \SimpleXMLElement $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public function parseSimpleXMLElement(\SimpleXMLElement $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $elementName = $input->getName();
        /** @var \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?> $fhirType */
        $fhirType = <?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getTypeClass($elementName);
        if (null === $fhirType) {
            throw new \UnexpectedValueException(sprintf(
                'Unable to locate FHIR type for root XML element "%s". Input seen: %s',
                $elementName,
                $this->getPrintableStringInput($input->saveXML())
            ));
        }
        return $fhirType::xmlUnserialize($input, null, $this->config);
    }

    /**
     * @param \DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public function parseDOMDocument(\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return $this->parseSimpleXMLElement(simplexml_import_dom($input));
    }

    /**
     * @param \stdClass|\SimpleXMLElement|\DOMDocument $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public function parseObject(\stdClass|\SimpleXMLElement|\DOMDocument $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if ($input instanceof \stdClass) {
            return $this->parseStdClass($input);
        } elseif ($input instanceof \SimpleXMLElement) {
            return $this->parseSimpleXMLElement($input);
        } else {
            return $this->parseDOMDocument($input);
        }
    }

    /**
     * @param string $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function parseXml(string $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return $this->parseSimpleXMLElement(new \SimpleXMLElement($input, $this->config->getLibxmlOpts()));
    }

    /**
     * @param string $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     */
    public function parseJson(string $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $decoded = json_decode($input, true);
        $err = json_last_error();
        if (JSON_ERROR_NONE !== $err) {
            throw new \DomainException(sprintf(
                'Unable to parse provided input as JSON.  Error: %s; Input: %s',
                json_last_error_msg(),
               $this->getPrintableStringInput($input)
            ));
        }

        return $this->parseArray($decoded);
    }

    /**
     * @param string $input
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function parseString(string $input): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $input = trim($input);
        if ('' === $input) {
            return null;
        }
        $chr = $input[0];
        if (in_array($chr, self::XML_START, true)) {
            return $this->parseXml($input);
        } elseif (in_array($chr, self::JSON_START, true)) {
            return $this->parseJson($input);
        }
        throw new \UnexpectedValueException(sprintf(
            'Input string must be either XML or JSON encoded object.  Provided: %s',
           $this->getPrintableStringInput($input)
        ));
    }

    /**
     * @param string $input
     * @return string
     */
    protected function getPrintableStringInput(string $input): string
    {
        if (strlen($input) > 100) {
            return sprintf('%s[...]', substr($input, 0, 100));
        }
        return $input;
    }
}
<?php return ob_get_clean();