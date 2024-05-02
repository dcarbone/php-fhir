<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n"; ?>
/**
 * Class <?php echo PHPFHIR_CLASSNAME_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> implements \JsonSerializable
{
    /** @var bool */
    private bool $registerAutoloader = false;

    /** @var int */
    private int $libxmlOpts;

    /** @var string */
    private string $rootXmlns;

    /** @var bool */
    private bool $overrideSourceXmlns;

    /**
     * <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::values() as $k => $_) {
            if (isset($config[$k]) || array_key_exists($k, $config)) {
                $this->setKey($k, $config[$k]);
            }
        }
    }

    /**
     * Set arbitrary key on this config
     *
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_CONFIG_KEY); ?>|string $key
     * @param mixed $value
     * @return static
     */
    public function setKey(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>|string $key, mixed $value): self
    {
        if (!is_string($key)) {
            $key = $key->value;
        }
        $this->{'set'.$key}($value);
        return $this;
    }

    /**
     * @param bool $registerAutoloader
     * @return static
     */
    public function setRegisterAutoloader(bool $registerAutoloader): self
    {
        $this->registerAutoloader = $registerAutoloader;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRegisterAutoloader(): bool
    {
        return $this->registerAutoloader;
    }

    /**
     * Sets the option flags to provide to libxml when unserializing XML
     *
     * @param int $libxmlOpts
     * @return static
     */
    public function setLibxmlOpts(int $libxmlOpts): self
    {
        $this->libxmlOpts = $libxmlOpts;
        return $this;
    }

    /**
     * Returns set libxml option flags
     *
     * @return int
     */
    public function getLibxmlOpts(): int
    {
        return $this->libxmlOpts ?? <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DEFAULT_LIBXML_OPTS;
    }

    /**
     * Default root xmlns to use.
     *
     * @param string $rootXmlns
     * @return static
     */
    public function setRootXmlns(string $rootXmlns): self
    {
        $this->rootXmlns = $rootXmlns;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRootXmlns(): null|string
    {
        return $this->rootXmlns ?? null;
    }

    /**
     * If true, overrides the xmlns entry found at the root of a source document, if there was one.
     *
     * @param bool $overrideSourceXmlns
     * @return static
     */
    public function setOverrideSourceXmlns(bool $overrideSourceXmlns): self
    {
        $this->overrideSourceXmlns = $overrideSourceXmlns;
        return $this;
    }

    /**
     * @return bool
     */
    public function getOverrideSourceXmlns(): bool
    {
        return $this->overrideSourceXmlns ?? false;
    }

    /**
     * @return \stdClass
     */
    public function jsonSerialize(): \stdClass
    {
        $out = new \stdClass();
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::cases() as $key) {
            $out->{$k} = $this->{$key->getter()}();
        }
        return $out;
    }
}
<?php return ob_get_clean();