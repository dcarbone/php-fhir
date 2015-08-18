<?php namespace PHPFHIR;

use PHPFHIR\Utilities\CopyrightUtils;
use PHPFHIR\Utilities\FileUtils;
use PHPFHIR\Utilities\ClassGeneratorUtils;
use PHPFHIR\Utilities\PropertyGeneratorUtils;
use PHPFHIR\Utilities\XMLUtils;

/**
 * Class Generator
 * @package PHPFHIR
 */
class Generator
{
    /** @var string */
    protected $outputPath;
    /** @var string */
    protected $outputNamespace;
    /** @var XSDMap */
    protected $XSDMap;
    /** @var ClassMap */
    protected $ClassMap;

    /**
     * Constructor
     *
     * @param string $xsdPath
     * @param string $outputNamespace
     * @param null|string $outputPath
     */
    public function __construct($xsdPath, $outputNamespace = '', $outputPath = null)
    {
        if (!is_dir($xsdPath))
            throw new \RuntimeException('Unable to locate XSD dir "'.$xsdPath.'"');

        $this->xsdPath = rtrim($xsdPath, "/\\").'/';

        if (null === $outputPath)
            $outputPath = sprintf('%s/../output', __DIR__);

        if (!is_dir($outputPath))
            throw new \RuntimeException('Unable to locate output dir "'.$outputPath.'"');

        $this->outputNamespace = trim($outputNamespace, '\\');
        $this->outputPath = $outputPath;
        $this->XSDMap = XMLUtils::buildXSDMap($this->xsdPath, $this->outputNamespace);
        $this->ClassMap = new ClassMap();

        CopyrightUtils::loadCopyright($this->xsdPath);
    }

    public function generate()
    {
        foreach($this->XSDMap as $objectName=>$data)
        {
            $classTemplate = ClassGeneratorUtils::buildClassTemplate($this->XSDMap, $data);

            FileUtils::createDirsFromNS($this->outputPath, $classTemplate->getNamespace());
            // Just test what we have so far.
            $classTemplate->writeToFile($this->outputPath);
        }
    }

}