<?php namespace PHPFHIR\ClassGenerator\Template;
use PHPFHIR\ClassGenerator\Utilities\CopyrightUtils;

/**
 * Class ParserMapTemplate
 * @package PHPFHIR\ClassGenerator\Template
 */
class ParserMapTemplate extends AbstractTemplate
{
    /** @var string */
    private static $_template = <<<STRING
<?php namespace %s;

%s

class PHPFHIRParserMap
{
    /** @var array */
    private \$_elementClassMap = %s;

    /** @var array */
    private \$_structureMap = %s;

    /**
     * @var string \$elementName
     * @return array|null
     */
    public function getElementStructure(\$elementName)
    {
        if (isset(\$this->_elementClassMap[\$elementName]))
            return \$this->_elementClassMap[\$elementName];

        return null;
    }

    /**
     * @var string \$name
     * @return array|null
     */
    public function getElementClasses(\$name)
    {
        if (isset(\$this->_elementClassMap[\$name]))
            return \$this->_elementClassMap[\$name];

        return null;
    }
}
STRING;

    /** @var array */
    private $_elementClassMap = array();
    /** @var array */
    private $_elementStructureMap = array();
    /** @var array */
    private $_classMap = array();

    /** @var string */
    private $_baseDir;
    /** @var string */
    private $_baseNS;

    /** @var string */
    private $_classPath;
    /** @var string */
    private $_className;

    /**
     * Constructor
     *
     * @param string $baseDir
     * @param string $baseNS
     */
    public function __construct($baseDir, $baseNS)
    {
        $this->_baseDir = rtrim($baseDir, "\\/");
        $this->_baseNS = $baseNS;

        $this->_classPath = sprintf('%s/PHPFHIRParserMap.php', $this->_baseDir);
        $this->_className = sprintf('%s\\PHPFHIRParserMap', $this->_baseNS);
    }

    public function addElementClass($elementName, ClassTemplate $classTemplate)
    {
        if (isset($this->_elementClassMap[$elementName]))
        {
            throw new \RuntimeException(sprintf(
                    'Element with name %s is already defined with class %s.  New input: %s',
                    $elementName,
                    $this->_elementClassMap[$elementName],
                    $classTemplate->getClassName()
                )
            );
        }

        $this->_elementClassMap[$elementName] = sprintf('\\%s\\%s', $classTemplate->getNamespace(), $classTemplate->getClassName());
        $this->_classMap[$classTemplate->getClassName()] = $this->_elementClassMap[$elementName];
        $this->_elementStructureMap[$elementName] = array();

        /** @var \PHPFHIR\ClassGenerator\Template\PropertyTemplate $property */
        foreach($classTemplate->getProperties() as $property)
        {
            $this->_elementStructureMap[$elementName][$property->getName()] = $property->getTypes();
        }
    }

    /**
     * @inheritDoc
     */
    public function compileTemplate()
    {
        return sprintf(
            self::$_template,
            $this->_baseNS,
            CopyrightUtils::getBasePHPFHIRCopyrightComment(),
            var_export($this->_elementClassMap, true),
            var_export($this->_elementStructureMap, true)
        );
    }

    /**
     * @return bool
     */
    public function writeToFile()
    {
        return (bool)file_put_contents(
            $this->_classPath,
            $this->compileTemplate()
        );
    }
}