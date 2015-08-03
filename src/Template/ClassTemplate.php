<?php namespace PHPFHIR\Template;

use PHPFHIR\Utilities\NameUtils;

/**
 * Class ClassTemplate
 * @package PHPFHIR\Template
 */
class ClassTemplate
{
    /** @var string */
    protected $namespace;
    /** @var string[] */
    protected $uses = array();
    /** @var string */
    protected $className;
    /** @var string */
    protected $extends;
    /** @var string */
    protected $documentation;
    /** @var array */
    protected $parameters = array();
    /** @var array */
    protected $methods = array();

    /**
     * @param string $namespace
     * @param string $className
     * @param string|null $documentation
     */
    public function __construct($namespace, $className, $documentation = null)
    {
        if (NameUtils::isValidNSName($namespace))
            $this->namespace = $namespace;
        else
            throw new \InvalidArgumentException('Namespace "'.$namespace.'" is not valid.');

        if (NameUtils::isValidClassName($className))
            $this->className = $className;
        else
            throw new \InvalidArgumentException('Class Name "'.$className.'" is not valid.');

        if (null === $documentation || is_string($documentation) || is_array($documentation))
            $this->documentation = $documentation;
        else
            throw new \InvalidArgumentException('Documentation expected to be null or string.');
    }

    /**
     * @return string
     */
    public function getUseStatement()
    {
        return sprintf('use %s\\%s;', $this->namespace, $this->className);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return \string[]
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
     * @return string
     */
    public function getDocumentation()
    {
        return $this->documentation;
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
     * @param ParameterTemplate $parameter
     */
    public function addParameter(ParameterTemplate $parameter)
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

    public function writeToFile($outputPath)
    {

    }

    public function __toString()
    {
        $ns = $this->getNamespace();
        if ('' === $ns)
            $output = "<?php\n\n";
        else
            $output = sprintf("<?php namespace %s;\n\n", $ns);

        foreach($this->uses as $use)
        {
//            $output = sprintf()
        }

        return $output;
    }
}