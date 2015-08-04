<?php namespace PHPFHIR;

use PHPFHIR\Template\ClassTemplate;
use PHPFHIR\Utilities\CopyrightUtils;
use PHPFHIR\Utilities\FileUtils;
use PHPFHIR\Utilities\GeneratorUtils;
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
            $classTemplate = GeneratorUtils::buildClassTemplate($data);

            $this->determineBaseClass($data['sxe'], $classTemplate);
            $this->addClassParameters($objectName, $data, $classTemplate);

            FileUtils::createDirsFromNS($this->outputPath, $classTemplate->getNamespace());
            // Just test what we have so far.
            $classTemplate->writeToFile($this->outputPath);
        }
    }

    protected function determineBaseClass(\SimpleXMLElement $sxe, ClassTemplate $classTemplate)
    {
        $baseObjectName = XMLUtils::getBaseObjectName($sxe);
        if (null === $baseObjectName)
            return null;

        $baseClassName = $this->XSDMap->getClassNameForObject($baseObjectName);
        $useStatement = $this->XSDMap->getClassUseStatementForObject($baseObjectName);

        $classTemplate->addUse($useStatement);
        $classTemplate->setExtends($baseClassName);
    }

    protected function addClassParameters($objectName, array $data, ClassTemplate $classTemplate)
    {

    }

}