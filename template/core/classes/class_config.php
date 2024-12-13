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
    private bool $_registerAutoloader = false;

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?>[] */
    private array $_versionConfigs = [];

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
     * @var bool $registerAutoloader
     * @return self
     */
    public function setRegisterAutoloader(bool $registerAutoloader): self
    {
        $this->_registerAutoloader = $registerAutoloader;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRegisterAutoloader(): bool
    {
        return $this->_registerAutoloader;
    }

    /**
     * Register a FHIR version's config.  Will overwrite an existing version config with the same name.
     *
     * @param string $name Unique FHIR version name.
     * @param array $config Configuration array for this version.
     * @return self
     */
    public function setVersion(string $name, array $config): self
    {
        if (isset($this->_versions[$name])) {
            throw new \InvalidArgumentException(sprintf('Version "%s" already defined.', $name));
        }

        $ve = VersionEnum::from($name);
        $configClass = $ve->getVersionConfigClass();
        $this->_versions[$name] = new $vc($config);

        return $this;
    }

    /**
     * Retrieve a specific FHIR version config by name.  Returns null if no version registered with that name.
     *
     * @param string $name
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?>

     */
    public function getVersion(string $name): null|<?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

    {
        return $this->_versions[$name] ?? null;
    }

    /**
     * Define all versions at once.  Will overwrite any existing versions.
     *
     * @param array $versions Map of version $name => $config
     * @return self
     */
    public function setVersions(array $versions): self
    {
        $this->_versions = [];
        foreach($versions as $name => $config) {
            $this->setVersion($name, $config);
        }
        return $this;
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