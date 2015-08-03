<?php namespace PHPFHIR;

use PHPFHIR\Utilities\ComplexTypeClassGenerator;
use PHPFHIR\Utilities\SimpleTypeClassGenerator;
use PHPFHIR\Utilities\SimpleXMLBuilder;

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
            $outputPath = __DIR__.'/../output';

        if (!is_dir($outputPath))
            throw new \RuntimeException('Unable to locate output dir "'.$outputPath.'"');

        $this->outputNamespace = trim($outputNamespace, '\\');
        $this->outputPath = $outputPath;
        $this->XSDMap = new XSDMap($this->xsdPath);
        $this->ClassMap = new ClassMap();
    }

    public function buildClasses()
    {
        $this->buildBase();
    }

    protected function buildBase()
    {
        $sxe = SimpleXMLBuilder::constructWithFilePath($this->XSDMap->getXSDPath().'fhir-base.xsd');

        foreach($sxe->children('xs', true) as $child)
        {
            /** @var \SimpleXMLElement $child */
            $type = $child->getName();
            $attributes = $child->attributes();
            $name = (string)$attributes['name'];

            $class = null;
            switch($type)
            {
                case 'simpleType':
                    $class = SimpleTypeClassGenerator::generate($child, $name, $this->outputNamespace);
                    break;

                case 'complexType':
                    $class = ComplexTypeClassGenerator::generate($child, $name, $this->outputNamespace);
                    break;
            }

            if (null === $class)
                continue;


        }
    }
}