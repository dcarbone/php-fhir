<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\FileUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>
/**
 * Class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> implements \JsonSerializable
{
    const KEY_REGISTER_AUTOLOADER = 'registerAutoloader';
    const KEY_SIMPLEXML_FLAGS = 'simpleXMLFlags';

    /** @var array */
    private static $_keysWithDefaults = [
        self::KEY_REGISTER_AUTOLOADER => false,
        self::KEY_SIMPLEXML_FLAGS => 73728, // LIBXML_COMPACT | LIBXML_NSCLEAN
    ];

    /** @var bool */
    private $registerAutoloader;
    /** @var int */
    private $simpleXMLFlags;

    /**
     * <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER_CONFIG; ?> Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach(self::$_keysWithDefaults as $k => $v) {
            if (isset($config[$k]) || array_key_exists($k, $config)) {
                $this->{'set'.$k}($config[$k]);
            } else {
                $this->{'set'.$k}($v);
            }
        }
    }

    /**
     * @param bool $registerAutoloader
     * @return void
     */
    public function setRegisterAutoloader($registerAutoloader)
    {
        $this->registerAutoloader = (bool)$registerAutoloader;
    }

    /**
     * @return bool
     */
    public function getRegisterAutoloader()
    {
        return $this->registerAutoloader;
    }

    /**
     * @param int $simpleXMLFlags
     */
    public function setSimpleXMLFlags($simpleXMLFlags)
    {
        $this->simpleXMLFlags = (int)$simpleXMLFlags;
    }

    /**
     * @return int
     */
    public function getSimpleXMLFlags()
    {
        return $this->simpleXMLFlags;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $a = [];
        foreach(self::$_keysWithDefaults as $k => $_) {
            $a[$k] = $this->{'get'.$k}();
        }
        return $a;
    }
}
<?php return ob_get_clean();