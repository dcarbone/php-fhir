<?php namespace PHPFHIR\Utilities;

use PHPFHIR\XSDMap;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class XMLUtils
 * @package PHPFHIR\Utilities
 */
abstract class XMLUtils
{
    /**
     * @param \SimpleXMLElement $sxe
     * @return null|string
     */
    public static function getBaseObjectName(\SimpleXMLElement $sxe)
    {
        $xpath = $sxe->xpath('xs:complexContent/xs:extension');
        if (0 === count($xpath))
            return null;

        $attributes = $xpath[0]->attributes();
        return (string)$attributes['base'];
    }

    /**
     * @param \SimpleXMLElement $sxe
     * @return null|string
     */
    public static function getObjectName(\SimpleXMLElement $sxe)
    {
        $attributes = $sxe->attributes();

        if ($name = $attributes['name'])
            return (string)$name;

        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|\SimpleXMLElement
     */
    public static function getAnnotationElement(\SimpleXMLElement $parent)
    {
        $annotation = $parent->xpath('xs:annotation');
        if (1 === count($annotation))
            return $annotation[0];

        return null;
    }

    /**
     * @param \SimpleXMLElement $parent
     * @return null|string|array
     */
    public static function getDocumentation(\SimpleXMLElement $parent)
    {
        $documentation = $parent->xpath('xs:annotation/xs:documentation');
        switch(count($documentation))
        {
            case 0: return null;
            case 1: return (string)$documentation[0];
            default:
                $return = array();
                foreach($documentation as $element)
                {
                    $return[] = (string)$element;
                }
                return $return;
        }
    }

    /**
     * @param string $xsdPath
     * @param string $outputNS
     * @return XSDMap
     */
    public static function buildXSDMap($xsdPath, $outputNS)
    {
        $xsdMap = new XSDMap();

        if (!file_exists($xsdPath.'fhir-base.xsd'))
            throw new \RuntimeException('Unable to locate "fhir-base.xsd"');

        // First get class references in fhir-base.xsd
        self::getClassesFromXSD(new \SplFileInfo($xsdPath . 'fhir-base.xsd'), $xsdMap, $outputNS);

        // Then scoop up the rest
        $finder = new Finder();
        $finder->files()
            ->in($xsdPath)
            ->ignoreDotFiles(true)
            ->name('*.xsd')
            ->notName('fhir-*.xsd')
            ->notName('xml.xsd');

        foreach($finder as $file)
        {
            /** @var SplFileInfo $file */
            self::getClassesFromXSD($file, $xsdMap, $outputNS);
        }

        return $xsdMap;
    }

    /**
     * @param \SplFileInfo $file
     * @param XSDMap $xsdMap
     * @param string $outputNS
     */
    public static function getClassesFromXSD(\SplFileInfo $file, XSDMap $xsdMap, $outputNS)
    {
        $sxe = SimpleXMLBuilder::constructWithFileInfo($file);
        foreach($sxe->children('xs', true) as $child)
        {
            /** @var \SimpleXMLElement $child */
            $attributes = $child->attributes();
            $name = (string)$attributes['name'];

            if ('' === $name)
                continue;

            $objectType = $child->getName();
            switch($objectType)
            {
                case 'simpleType':
                    $type = ClassTypeUtils::getSimpleClassType($name);
                    $rootNS = NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getSimpleTypeNamespace($type)
                    );
                    $className = NameUtils::getSimpleTypeClassName($name);
                    break;
                case 'complexType':
                    $type = ClassTypeUtils::getComplexClassType($child);
                    $rootNS = NSUtils::generateRootNamespace(
                        $outputNS,
                        NSUtils::getComplexTypeNamespace($name, $type)
                    );
                    $className = NameUtils::getComplexTypeClassName($name);
                    break;

                default: continue 2;
            }

            $xsdMap[$name] = array(
                'sxe' => $child,
                'rootNS' => $rootNS,
                'className' => $className,
                'objectType' => $objectType,
            );
        }
    }
}