<?php namespace PHPFHIR\Utilities;

abstract class XMLUtils
{
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
        $documentation = $parent->xpath('./xs:annotation/xs:documentation');
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
}