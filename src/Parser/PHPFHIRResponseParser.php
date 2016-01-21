<?php namespace PHPFHIR\Parser;

/**
 * Class PHPFHIRResponseParser
 * @package PHPFHIR\Parser
 */
class PHPFHIRResponseParser
{
    /** @var string */
    private $_outputPath;
    /** @var string */
    private $_outputNamespace;

    /** @var ParserMapInterface */
    private $_parserMap;

    /**
     * Constructor
     *
     * @param string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($outputPath, $outputNamespace)
    {
        $this->_outputPath = $outputPath;
        $this->_outputNamespace = $outputNamespace;

        $autoloaderClass = sprintf('\\%s\\PHPFHIRAutoloader', $outputNamespace);

        if (!class_exists($autoloaderClass, true))
        {
            $autoloaderFile = sprintf('%s/%s/PHPFHIRAutoloader.php', $outputPath, $outputNamespace);
            if (!file_exists($autoloaderFile))
            {
                throw new \RuntimeException(sprintf(
                    'PHPFHIRAutoloader class is not defined and was not found at expected location "%s".',
                    $autoloaderFile
                ));
            }

            require $autoloaderFile;
            $autoloader = new $autoloaderClass;

            $autoloader::register();
        }
        $parserMapFile = sprintf('%s/%s/PHPFHIRParserMap.php', $outputPath, $outputNamespace);
        require $parserMapFile;
        $parserMapClass = sprintf('\\%s\\PHPFHIRParserMap', $outputNamespace);

        $this->_parserMap = new $parserMapClass;
    }

    public function parse($response)
    {
        if (!is_string($response))
        {
            throw new \InvalidArgumentException(sprintf(
                '%s::parse - Argument 1 expected to be string, %s seen.',
                get_class($this),
                gettype($response)
            ));
        }

        // For now, expect only XML
        // TODO: Support JSON

        libxml_use_internal_errors(true);
        $sxe = new \SimpleXMLElement($response, LIBXML_COMPACT | LIBXML_NSCLEAN);
        $error = libxml_get_last_error();
        libxml_use_internal_errors(false);

        if ($sxe instanceof \SimpleXMLElement)
            return $this->_parseNode($sxe, $sxe->getName());

        throw new \RuntimeException(sprintf(
            'Unable to parse response: "%s"',
            ($error ? $error->message : 'Unknown Error')
        ));
    }

    /**
     * @param \SimpleXMLElement $element
     * @param string $fhirElementName
     * @return mixed
     */
    private function _parseNode(\SimpleXMLElement $element, $fhirElementName)
    {
        if (!isset($this->_parserMap[$fhirElementName]))
        {
            throw new \RuntimeException(sprintf(
                'Element map does not contain entry for "%s".  This indicates either malformed response or bug in class generation.',
                $fhirElementName
            ));
        }

        $map = $this->_parserMap[$fhirElementName];
        $fullClassName = $map['fullClassName'];
        $properties = $map['properties'];

        $object = new $fullClassName;

        if (isset($element['value']))
        {
            $propertyMap = $properties['value'];
            $setter = $propertyMap['setter'];
            $object->$setter($this->_createPrimitive($element, $propertyMap['type']));
        }
        else
        {
            /** @var \SimpleXMLElement $childElement */
            foreach($element->children() as $childElement)
            {
                $childName = $childElement->getName();
                if (!isset($properties[$childName]))
                {
                    trigger_error(sprintf(
                        'Could not find mapped property called "%s" on object "%s".  This could indicate malformed response or bug in class generator.',
                        $childName,
                        $fhirElementName
                    ));
                    continue;
                }

                $propertyMap = $properties[$childName];
                $setter = $propertyMap['setter'];
                $type = $propertyMap['type'];

                $object->$setter($this->_parseNode($childElement, $type));
            }
        }

        return $object;
    }

    /**
     * @param \SimpleXMLElement $element
     * @param $type
     * @return mixed
     */
    private function _createPrimitive(\SimpleXMLElement $element, $type)
    {
        if (!isset($this->_parserMap[$type]))
        {
            trigger_error(sprintf(
                'Unable to find definition for primitive %s.  This indicates either malformed response or bug in class generator.',
                $type
            ));

            return null;
        }

        $primitiveMap = $this->_parserMap[$type];
        $fullClassName = $primitiveMap['fullClassName'];

        $primitiveObject = new $fullClassName;
        $primitiveObject->setValue((string)$element['value']);

        return $primitiveObject;
    }
}