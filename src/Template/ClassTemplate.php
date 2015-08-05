<?php namespace PHPFHIR\Template;

use PHPFHIR\Utilities\CopyrightUtils;
use PHPFHIR\Utilities\FileUtils;
use PHPFHIR\Utilities\NameUtils;

/**
 * Class ClassTemplate
 * @package PHPFHIR\Template
 */
class ClassTemplate extends AbstractTemplate
{
    /** @var string */
    protected $namespace;
    /** @var array */
    protected $uses = array();
    /** @var string */
    protected $className;
    /** @var string */
    protected $extends;
    /** @var array */
    protected $parameters = array();
    /** @var array */
    protected $methods = array();

    /**
     * Constructor
     *
     * @param string $namespace
     * @param string $className
     */
    public function __construct($namespace, $className)
    {
        if (NameUtils::isValidNSName($namespace))
            $this->namespace = $namespace;
        else
            throw new \InvalidArgumentException('Namespace "' . $namespace . '" is not valid.');

        if (NameUtils::isValidClassName($className))
            $this->className = $className;
        else
            throw new \InvalidArgumentException('Class Name "' . $className . '" is not valid.');
    }

    /**
     * @return string
     */
    public function getUseStatement()
    {
        return sprintf('%s\\%s;', $this->namespace, $this->className);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getUses()
    {
        return $this->uses;
    }

    /**
     * @param string $use
     */
    public function addUse($use)
    {
        $this->uses[] = $use;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $extends
     */
    public function setExtends($extends)
    {
        $this->extends = $extends;
    }

    /**
     * @return string
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * @param PropertyTemplate $parameter
     */
    public function addParameter(PropertyTemplate $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
    }

    /**
     * @param MethodTemplate $method
     */
    public function addMethod(MethodTemplate $method)
    {
        $this->parameters[$method->getName()] = $method;
    }

    // TODO: Possibly omit __toString use, and write directly to file.  Might be faster.

    /**
     * @param string $outputPath
     * @return bool
     */
    public function writeToFile($outputPath)
    {
        $outputPath = sprintf('%s/%s/%s.php',
            $outputPath,
            FileUtils::buildDirPathFromNS($this->getNamespace()),
            $this->getClassName()
        );

        return (bool)file_put_contents($outputPath, (string)$this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $ns = $this->getNamespace();
        if ('' === $ns)
            $output = "<?php\n\n";
        else
            $output = sprintf("<?php namespace %s;\n\n", $ns);

        $output = sprintf("%s%s\n\n", $output, CopyrightUtils::getHL7Copyright());

        foreach($this->uses as $use)
        {
            $output = sprintf("%suse %s;\n", $output, $use);
        }

        if ("\n\n" !== substr($output, -2))
            $output = sprintf("%s\n", $output);

        if (isset($this->documentation))
        {
            $docs = '';
            foreach($this->documentation as $doc)
            {
                $docs = sprintf("%s * %s\n", $docs, $doc);
            }

            $output = sprintf("%s/**\n%s */\n", $output, $docs);
        }

        if ($this->extends)
            $output = sprintf("%sclass %s extends %s\n", $output, $this->getClassName(), $this->getExtends());
        else
            $output = sprintf("%sclass %s\n", $output, $this->getClassName());

        $output = sprintf("%s{\n", $output);

        return sprintf("%s\n}", $output);
    }
}