<?php namespace PHPFHIR\Parser;

/**
 * Class AbstractResponseParser
 * @package PHPFHIR\Parser
 */
abstract class AbstractResponseParser
{
    /** @var string */
    protected $outputPath;
    /** @var string */
    protected $outputNamespace;

    /** @var ParserMapInterface */
    protected $parserMap;

    /**
     * Constructor
     *
     * @param string $outputPath
     * @param string $outputNamespace
     */
    public function __construct($outputPath, $outputNamespace)
    {
        $this->outputPath = $outputPath;
        $this->outputNamespace = $outputNamespace;

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

        if (!file_exists($parserMapFile))
        {
            throw new \RuntimeException(sprintf(
                'PHPFHIRParserMap class file not found in expected location "%s".  This could mean the class generator has not been run or incorrect constructor parameters.',
                $parserMapFile
            ));
        }

        require $parserMapFile;
        $parserMapClass = sprintf('\\%s\\PHPFHIRParserMap', $outputNamespace);

        if (!class_exists($parserMapClass, false))
        {
            throw new \RuntimeException(sprintf(
                'PHPFHIRParserMap class "%s" file was loaded from "%s", but it does not appear to contain the class definition.  Please re-run class generator or report this as a bug.',
                $parserMapClass,
                $parserMapFile
            ));
        }

        $this->parserMap = new $parserMapClass;
    }

    /**
     * @param string $input
     * @return object
     */
    abstract public function parse($input);

    /**
     * @param string $fhirElementName
     * @return array
     */
    protected function tryGetMapEntry($fhirElementName)
    {
        if (!isset($this->parserMap[$fhirElementName]))
        {
            throw new \RuntimeException(sprintf(
                'Element map does not contain entry for "%s".  This indicates either malformed response or bug in class generation.',
                $fhirElementName
            ));
        }

        return $this->parserMap[$fhirElementName];
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return null|object
     */
    protected function createPrimitive($value, $type)
    {
        if (!isset($this->parserMap[$type]))
        {
            trigger_error(sprintf(
                'Unable to find definition for primitive "%s".  This indicates either malformed response or bug in class generator.',
                $type
            ));

            return null;
        }

        $primitiveMap = $this->parserMap[$type];
        $fullClassName = $primitiveMap['fullClassName'];

        $primitiveObject = new $fullClassName;
        $primitiveObject->setValue($value);

        return $primitiveObject;
    }

    /**
     * @param string $fhirElementName
     * @param string $propertyName
     * @return bool
     */
    protected function triggerPropertyNotFoundError($fhirElementName, $propertyName)
    {
        return trigger_error(sprintf(
            'Could not find mapped property called "%s" on object "%s".  This could indicate malformed response or bug in class generator.',
            $propertyName,
            $fhirElementName
        ));
    }

    /**
     * @param mixed $response
     * @return \InvalidArgumentException
     */
    protected function createNonStringArgumentException($response)
    {
        return new \InvalidArgumentException(sprintf(
            '%s::parse - Argument 1 expected to be string, %s seen.',
            get_called_class(),
            gettype($response)
        ));
    }
}