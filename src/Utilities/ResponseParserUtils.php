<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition;

/**
 * Class ResponseParserUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class ResponseParserUtils
{
    const VARS = <<<PHP
    /**
     * If response is XML, these arguments will be passed into the \SimpleXMLElement constructor
     * @see http://php.net/manual/en/libxml.constants.php for a list of options.
     * @var int
     */
    private \$_sxeArgs;

PHP;

    const FUNC_CONSTRUCTOR = <<<PHP
    /**
     * PHPFHIRResponseParser Constructor
     * @param bool \$registerAutoloader Whether to try to register the Autoloader at construction
     * @param int \$sxeArgs LIBXML_* constants to use when constructing a SimpleXMLElement when parsing an XML response
     * @throws \Exception
     */
    public function __construct(\$registerAutoloader = false, \$sxeArgs = null)
    {
        if (\$registerAutoloader) {
            if (!(require __DIR__.'/PHPFHIRAutoloader.php')) {
                throw new \RuntimeException(sprintf(
                    'Unable to locate PHPFHIRAutoloader at expected path %s',
                    __DIR__.'/PHPFHIRAutoloader.php'
                ));
            }
            if (!PHPFHIRAutoloader::register()) {
                throw new \RuntimeException('Failed to register autoloader');
            }
        }
        
        if (null === \$sxeArgs) {
            \$sxeArgs = LIBXML_COMPACT | LIBXML_NSCLEAN;
        }        
        if (!is_int(\$sxeArgs)) {
            throw new \InvalidArgumentException(sprintf(
                '\$sxeArgs must be integer, %s seen',
                gettype(\$sxeArgs)
            ));
        }
        \$this->_sxeArgs = \$sxeArgs;
    }

PHP;

    const FUNC_PARSE = <<<PHP
    /**
     * @param string \$in
     * @return object|null
     */
    public function parse(\$in)
    {
        if (!is_string(\$in)) {
            throw new \InvalidArgumentException(sprintf(
                '\$in must be string, %s seen',
                gettype(\$in)
            ));
        }
        
        \$first = substr(\$in, 0, 1);
        if ('<' === \$first) {
            return \$this->_parseXML(\$in);
        } else if ('{' === \$first) {
            return \$this->_parseJSON(\$in);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'Unknown data format provided, expected XML ("<") or JSON ("{") first character, saw %s',
                \$first
            ));
        }
    }
PHP;

    const FUNC_PARSE_JSON = <<<PHP
    /**
     * @param string \$in
     * @return null|object
     */
    private function _parseJSON(\$in)
    {
        \$decoded = json_decode(\$in, true, 512, JSON_BIGINT_AS_STRING);
        \$err = json_last_error();
        if (JSON_ERROR_NONE !== \$err) {
            throw new \RuntimeException(sprintf(
                'Unable to decode JSON input: %s',
                json_last_error_msg()
            ));
        }
        if (null === \$decoded) {
            return null;
        }
        if (!is_array(\$decoded)) {
            throw new \DomainException(sprintf(
                'Expected decoded JSON response to be associative array, saw %s',
                gettype(\$decoded)
            ));
        }
        if (!isset(\$decoded[self::RESOURCE_TYPE_FIELD])) {
            throw new \DomainException(sprintf(
                'Cannot decode JSON response, expected field "%s" not found.  Root fields: ["%s"]',
                self::RESOURCE_TYPE_FIELD,
                implode('","', array_keys(\$decoded))
            ));
        }
        if (!isset(\$this->_typeMap[\$decoded[self::RESOURCE_TYPE_FIELD]])) {
            throw new \DomainException(sprintf(
                'Unable to find class for type %s',
                \$decoded[self::RESOURCE_TYPE_FIELD]
            ));
        }
        \$class = \$this->_typeMap[\$decoded[self::RESOURCE_TYPE_FIELD]];
        return new \$class(\$decoded);
    }

PHP;

    const FUNC_PARSE_XML = <<<PHP
    /**
     * @param string \$in
     * @return null|object
     */
    private function _parseXML(\$in)
    {
        libxml_use_internal_errors(true);
        \$sxe = new \SimpleXMLElement(\$in, \$this->_sxeArgs);
        \$err = libxml_get_last_error();
        libxml_use_internal_errors(false);
        
        if (!(\$sxe instanceof \SimpleXMLElement)) {
            throw new \RuntimeException(sprintf(
                'Unable to parse response as XML: %s',
                \$err
            ));
        }
        if (!isset(\$this->_typeMap[\$sxe->getName()])) {
            throw new \DomainException(sprintf(
                'Unable to find class for type %s',
                \$sxe->getName()
            ));
        }
        \$class = \$this->_typeMap[\$sxe->getName()];
        return \$class::xmlUnserialize(\$sxe);
    }

PHP;


    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition $definition
     * @return string
     */
    public static function buildResponseParser(Config\VersionConfig $config, Definition $definition)
    {
        $rootNamespace = $config->getNamespace();
        $typeMap = [];
        foreach ($definition->getTypes()->getIterator() as $type) {
            $typeMap[$type->getFHIRName()] = $type->getFullyQualifiedClassName(true);
        }

        $out = FileUtils::buildFileHeader($config->getNamespace());

        $out .= <<<PHP
/**
 * @class PHPFHIRResponseParser
PHP;

        if ('' !== $rootNamespace) {
            $out .= "\n * @package {$rootNamespace}\n";
        }

        $out .= <<<PHP
 */
class PHPFHIRResponseParser
{

PHP;

        $out .= sprintf(self::VARS, var_export($typeMap, true));
        $out .= "\n";
        $out .= self::FUNC_CONSTRUCTOR;
        $out .= "\n";
        $out .= self::FUNC_PARSE;
        $out .= "\n";
        $out .= self::FUNC_PARSE_JSON;
        $out .= "\n";
        $out .= self::FUNC_PARSE_XML;
        return $out . '}';
    }
}