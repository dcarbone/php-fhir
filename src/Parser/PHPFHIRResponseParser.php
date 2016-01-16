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
        {
            return $this->parseNode($sxe);
        }
        else
        {
            throw new \RuntimeException(sprintf(
                'Unable to parse response: "%s"',
                ($error ? $error->message : 'Unknown Error')
            ));
        }
    }

    /**
     * @param \SimpleXMLElement $element
     * @return mixed
     */
    protected function parseNode(\SimpleXMLElement $element)
    {
        $name = $element->getName();
        $class = $this->_parserMap->getElementClass($name);
        $structure = $this->_parserMap->getElementStructure($name);

        $object = new $class;

        foreach($structure as $parameter=>$types)
        {
            if (isset($element->{$parameter}))
            {
                $paramElement = $element->{$parameter};
                $paramObject = $this->parseChild($paramElement, $parameter, key($types));
                $setter = sprintf('set%s', ucfirst($parameter));
                $object->{$setter}($paramObject);
            }
        }

        return $object;
    }

    protected function parseChild(\SimpleXMLElement $element, $paramName, $paramType)
    {

    }
}