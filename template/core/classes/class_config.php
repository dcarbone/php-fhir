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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

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

    /** @var int */
    private int $jsonDecodeMaxDepth;

    /**
     * <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }
    }

    /**
     * Sets the option flags to provide to libxml when unserializing XML
     *
     * @see https://www.php.net/manual/en/libxml.constants.php
     *
     * @param int $libxmlOpts
     * @return self
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
     * @param string $rootXmlns
     * @return self
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
     * @return self
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
     * Max depth option to provide when decoding a JSON input string.
     *
     * See https://www.php.net/manual/en/function.json-decode.php
     *
     * @param int $maxDepth
     * @return self
     */
    public function setJsonDecodeMaxDepth(int $maxDepth): self
    {
        $this->jsonDecodeMaxDepth = $maxDepth;
        return $this;
    }

    /**
     * @return int
     */
    public function getJsonDecodeMaxDepth(): int
    {
        return $this->jsonDecodeMaxDepth ?? <?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::DEFAULT_JSON_DECODE_MAX_DEPTH;
    }

    /**
     * @return \stdClass
     */
    public function jsonSerialize(): \stdClass
    {
        $out = new \stdClass();
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::cases() as $key) {
            $out->{$key->value} = $this->{"get$key->value"}();
        }
        return $out;
    }
}
<?php return ob_get_clean();