<?php

namespace DCarbone\PHPFHIR\Definition;

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition\Type\Properties;
use DCarbone\PHPFHIR\Definition\Type\Property;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/**
 * Class AbstractType
 * @package DCarbone\PHPFHIR\Definition
 */
abstract class AbstractType implements TypeInterface
{
    use DocumentationTrait;

    /** @var \DCarbone\PHPFHIR\Config\VersionConfig */
    private $config;
    /**
     * The raw element this type was parsed from.  Will be null for HTML and Undefined types
     *
     * @var null|\SimpleXMLElement
     */
    private $sourceSXE;

    /**
     * Name of file in definition this type was parsed from
     * @var string
     */
    private $sourceFilename;
    /**
     * The raw name of the FHIR element this type was created from
     * @var string
     */
    private $fhirName;

    /** @var null|string */
    private $componentOfTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\Type */
    private $componentOfType = null;

    /** @var string */
    private $className;

    /** @var null|string */
    private $parentTypeName = null;
    /** @var null|\DCarbone\PHPFHIR\Definition\TypeInterface */
    private $parentType = null;

    /** @var null|\DCarbone\PHPFHIR\Enum\PrimitiveType */
    private $primitiveType;

    /** @var \DCarbone\PHPFHIR\Definition\Type\Properties */
    private $properties;

    /**
     * AbstractType constructor.
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \SimpleXMLElement $sourceSXE
     * @param $sourceFilename
     * @param $fhirName
     */
    public function __construct(VersionConfig $config,
                                \SimpleXMLElement $sourceSXE = null,
                                $sourceFilename = '',
                                $fhirName = '')
    {
        if ('' === $fhirName) {
            throw new \DomainException('$fhirName must be defined');
        }
        $this->config = $config;
        $this->sourceSXE = $sourceSXE;
        $this->sourceFilename = $sourceFilename;
        $this->fhirName = $fhirName;
        $this->properties = new Properties($config, $this);
    }


    /**
     * @return array
     */
    public function __debugInfo()
    {
        $vars = get_object_vars($this);
        unset($vars['config']);
        return $vars;
    }

    /**
     * @return \DCarbone\PHPFHIR\Config\VersionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return null|\SimpleXMLElement
     */
    public function getSourceSXE()
    {
        return $this->sourceSXE;
    }

    /**
     * @return string
     */
    public function getSourceFilename()
    {
        return $this->sourceFilename;
    }

    /**
     * @return string
     */
    public function getSourceFileBasename()
    {
        return basename($this->getSourceFilename());
    }

    /**
     * @return string
     */
    public function getFHIRName()
    {
        return $this->fhirName;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface|null
     */
    public function getComponentOfType()
    {
        return $this->componentOfType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\TypeInterface $type
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setComponentOfType(TypeInterface $type)
    {
        $this->componentOfType = $type;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getComponentOfTypeName()
    {
        return $this->componentOfTypeName;
    }

    /**
     * @param null|string $componentOfTypeName
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setComponentOfTypeName($componentOfTypeName)
    {
        $this->componentOfTypeName = $componentOfTypeName;
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface[]
     */
    public function getParentTypes()
    {
        $parents = [];
        $p = $this->getParentType();
        while (null !== $p) {
            $parents[] = $p;
            $p = $p->getParentType();
        }
        return $parents;
    }

    /**
     * @return string
     */
    public function getFHIRTypeNamespace()
    {
        if ($this->isRootType()) {
            return '';
        }
        $ns = [];
        foreach ($this->getParentTypes() as $parent) {
            array_unshift($ns, $parent->getClassName());
        }
        if ($ctype = $this->getComponentOfType()) {
            $ns[] = $ctype->getClassName();
        }
        return implode('\\', $ns);
    }

    /**
     * @return bool
     */
    public function isRootType()
    {
        return null === $this->getParentType();
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function getRootType()
    {
        if ($this->isRootType()) {
            return $this;
        }
        $parents = $this->getParentTypes();
        return end($parents);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        if (!isset($this->className)) {
            $this->className = NameUtils::getTypeClassName($this->getFHIRName());
        }
        return $this->className;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedNamespace($leadingSlash)
    {
        $ns = $this->getConfig()->getNamespace();
        $fhirNS = $this->getFHIRTypeNamespace();
        if ('' !== $fhirNS) {
            $ns = "{$ns}\\{$fhirNS}";
        }
        return $leadingSlash ? '\\' . $ns : $ns;
    }

    /**
     * @param bool $leadingSlash
     * @return string
     */
    public function getFullyQualifiedClassName($leadingSlash)
    {
        return $this->getFullyQualifiedNamespace($leadingSlash) . '\\' . $this->getClassName();
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface|null
     */
    public function getParentType()
    {
        return $this->parentType;
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\TypeInterface $type
     * @return $this|\DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setParentType(TypeInterface $type)
    {
        $this->parentType = $type;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getParentTypeName()
    {
        return $this->parentTypeName;
    }

    /**
     * @param string|null $parentTypeName
     * @return $this|\DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function setParentTypeName($parentTypeName)
    {
        $this->parentTypeName = $parentTypeName;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return null !== $this->getParentTypeName();
    }

    /**
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return \DCarbone\PHPFHIR\Definition\TypeInterface
     */
    public function addProperty(Property $property)
    {
        $this->properties->addProperty($property);
        return $this;
    }

    /**
     * @return \DCarbone\PHPFHIR\Definition\Type\Properties
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return bool
     */
    public function isResourceContainer()
    {
        return PHPFHIR_TYPE_RESOURCE_CONTAINER === $this->getFHIRName();
    }

    /**
     * @return bool
     */
    public function isInlineResource()
    {
        return PHPFHIR_TYPE_RESOURCE_INLINE === $this->getFHIRName();
    }

    /**
     * Is this a child of a "primitive" type?
     *
     * @return bool
     */
    public function hasPrimitiveParent()
    {
        foreach ($this->getParentTypes() as $parent) {
            if ($parent->isPrimitive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Is this immediate type a "primitive"?
     *
     * @return bool
     */
    public function isPrimitive()
    {
        return false !== strpos($this->getFHIRName(), '-primitive');
    }

    /**
     * @return bool
     */
    public function isList()
    {
        return false !== strpos($this->getFHIRName(), '-list');
    }

    /**
     * Is this type just a primitive container?
     *
     * TODO: this could stand to be improved, right now only looks for "value" types...
     *
     * @return bool
     */
    public function isPrimitiveContainer()
    {
        return 1 === count($this->properties) &&
            null !== ($prop = $this->properties->getProperty('value')) &&
            null !== ($type = $prop->getValueType()) &&
            ($type->isPrimitive() || $type->hasPrimitiveParent());
    }

    /**
     * Does this type extend a type that is a primitive container?
     *
     * @return bool
     */
    public function hasPrimitiveContainerParent()
    {
        foreach ($this->getParentTypes() as $parentType) {
            if ($parentType->isPrimitiveContainer()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isResource()
    {
        $n = $this->getFHIRName();
        if (PHPFHIR_TYPE_RESOURCE === $n || PHPFHIR_TYPE_DOMAIN_RESOURCE === $n) {
            return true;
        }
        foreach ($this->getParentTypes() as $parentType) {
            $n = $parentType->getFHIRName();
            if (PHPFHIR_TYPE_RESOURCE === $n || PHPFHIR_TYPE_DOMAIN_RESOURCE === $n) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true if this Type is an element who's only properties are various "valueString",
     * "valueCodeableConcept", etc...
     *
     * @return bool
     */
    public function isVariadicValueElement()
    {
        if ($this->isPrimitive() || $this->isPrimitiveContainer() || $this->isList()) {
            return false;
        }
        if (1 < count($this->properties)) {
            foreach ($this->getProperties()->getIterator() as $property) {
                $name = $property->getName();
                if ('value' !== $name && 0 === strpos($property->getName(), 'value')) {
                    continue;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFHIRName();
    }
}