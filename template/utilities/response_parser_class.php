<?php declare(strict_types=1);

/*
 * Copyright 2018-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

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
    /** @var \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> $config */
    private <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> $config;

    /**
     * <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> Constructor
     * @param \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>|null $config
     */
    public function __construct(?<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> $config = null)
    {
        if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>;
        }
        $this->config = $config;
    }

    /**
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>

     */
    public function getConfig(): <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?>

    {
        return $this->config;
    }

    /**
     * @param array|string|\SimpleXMLElement|\DOMDocument $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    public function parse($input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $inputType = gettype($input);
        if ('NULL' === $inputType) {
            return null;
        } elseif ('string' === $inputType) {
            return $this->parseStringInput($input);
        } elseif ('array' === $inputType) {
            return $this->parseArrayInput($input);
        } elseif ('object' === $inputType) {
            return $this->parseObjectInput($input);
        } else {
            throw new \InvalidArgumentException(sprintf(
                '%s::parse - $input must be XML or JSON encoded string, array, or an object of type \\DOMElement or \\SimpleXMLElement, %s seen.',
                get_class($this),
                $inputType
            ));
        }
    }

    /**
     * @param array $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseArrayInput(array $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

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
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseObjectStdClassInput(\stdClass $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return self::parseArrayInput((array)$input);
    }

    /**
     * @param \SimpleXMLElement $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseObjectSimpleXMLElementInput(\SimpleXMLElement $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        return self::parseObjectDOMDocumentInput(dom_import_simplexml($input));
    }

    /**
     * @param \DOMDocument $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseObjectDOMDocumentInput(\DOMDocument $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $elementName = $input->documentElement->nodeName;
        $className = <?php echo PHPFHIR_CLASSNAME_TYPEMAP; ?>::getTypeClass($elementName);
         if (null === $className) {
            throw new \UnexpectedValueException(sprintf(
                'Unable to locate class for root XML element "%s". Input seen: %s',
                $elementName,
                $this->getPrintableStringInput($input->saveXML())
            ));
        }
        return $className::xmlUnserialize($input->documentElement);
    }

    /**
     * @param object $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseObjectInput(object $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if ($input instanceof <?php echo PHPFHIR_INTERFACE_TYPE; ?>) {
            return $input;
        } elseif ($input instanceof \stdClass) {
            return $this->parseObjectStdClassInput($input);
        } elseif ($input instanceof \SimpleXMLElement) {
            return $this->parseObjectSimpleXMLElementInput($input);
        } elseif ($input instanceof \DOMDocument) {
            return $this->parseObjectDOMDocumentInput($input);
        }
        throw new \UnexpectedValueException(sprintf(
            'Unable parse provided input object of type "%s"',
            get_class($input)
        ));
    }

    /**
     * @param string $input
     * @param null|int $libxmlOpts
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseStringXMLInput(string $input, ?int $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadXML($input, $libxmlOpts);
        $err = libxml_get_last_error();
        libxml_use_internal_errors(false);
        if (false === $err) {
            return $this->parseObjectDOMDocumentInput($dom);
        }
        throw new \DomainException(sprintf(
            'Unable to parse provided input as XML.  Error: %s; Input: %s',
            $err ? $err->message : 'Unknown',
            $this->getPrintableStringInput($input)
        ));
    }

    /**
     * @param string $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseStringJSONInput(string $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

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

        return $this->parseArrayInput($decoded);
    }

    /**
     * @param string $input
     * @return \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_TYPE; ?>|null
     */
    protected function parseStringInput(string $input): ?<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        $input = trim($input);
        if ('' === $input) {
            return null;
        }
        $chr = $input[0];
        if ('<' === $chr) {
            return $this->parseStringXMLInput($input);
        } elseif ('{' === $chr) {
            return $this->parseStringJSONInput($input);
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Input string must be either XML or JSON encoded object.  Provided: %s',
               $this->getPrintableStringInput($input)
            ));
        }
    }

    /**
     * @param string $input
     * @return string
     */
    protected function getPrintableStringInput(string $input): string
    {
        return strlen($input) > 100 ? substr($input, 0, 100) . '[...]' : $input;
    }
}
<?php return ob_get_clean();