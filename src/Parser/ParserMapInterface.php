<?php namespace PHPFHIR\Parser;

/**
 * Interface ParserMapInterface
 * @package PHPFHIR\Parser
 */
interface ParserMapInterface
{
    /**
     * @var string $elementName
     * @return array|null
     */
    public function getElementStructure($elementName);

    /**
     * @var string $name
     * @return array|null
     */
    public function getElementClass($name);
}