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

$namespace = $config->getNamespace(false);

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
    public const DEFAULT_LIBXML_OPTS = LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL;

    /** @var bool */
    private bool $registerAutoloader = false;

    /** @var int */
    private int $libxmlOpts;

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
     * @param \<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_ENUM_CONFIG_KEY; ?>|string $key
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
        return $this->libxmlOpts ?? static::DEFAULT_LIBXML_OPTS;
    }

    /**
     * @return \stdClass
     */
    public function jsonSerialize(): \stdClass
    {
        $out = new \stdClass();
        foreach(<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::values() as $k => $_) {
            $out->{$k} = $this->{'get'.$k}();
        }
        return $out;
    }
}
<?php return ob_get_clean();