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

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


/**
 * Class <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>

 *
 * Configuration class for the <?php echo PHPFHIR_CLASSNAME_FACTORY; ?>.  If you are not using the factory, then
 * this class serves no purpose for your implementation.
 */
final class <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>

{
    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG); ?>[] */
    private array $_versions = [];

    /**
     * <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?> Constructor
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach(<?php echo PHPFHIR_ENUM_FACTORY_CONFIG_KEY; ?>::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }
    }

    /**
     * Register a new FHIR version config with the this factory.  Will overwrite any existing version configuration with
     * the same name.
     *
     * @param array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG); ?> $version
     * @return self
     */
    public function setVersion(array|<?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG; ?> $version): self
    {
        if (is_array($version)) {
            if (!isset($version['name'])) {
                throw new \InvalidArgumentException('Must provide "name" field when registering version');
            }
            if (!isset($version['class'])) {
                throw new \InvalidArgumentException('Must provide "class" field when registering version');
            } else if (!class_exists($version['class'], true)) {
                throw new \InvalidArgumentException(sprintf('Class "%s" could not be autoloaded', $version['class']));
            }
            $version = new <?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG; ?>(
                $version['name'],
                $version['class'],
                $version['config'] ?? null,
            );
        }

        if (!($version instanceof <?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG; ?>)) {
            throw new \InvalidArgumentException(sprintf(
                '$config must be an instance of \\%s, %s given',
                <?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG; ?>::class,
                get_class($version)
            ));
        }

        $this->_versions[$version->getName()] = $version;

        return $this;
    }

    /**
     * Return a registered version configuration, if defined.
     *
     * @param string $name
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG); ?>

     */
    public function getVersion(string $name): null|<?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG ?>

    {
        return $this->_versions[$name] ?? null;
    }

    /**
     * Define all versions at once.  Will overwrite any existing versions.
     *
     * @param array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG); ?>[] $versions Array of version configurations.
     * @return self
     */
    public function setVersions(array $versions): self
    {
        $this->_versions = [];
        foreach($versions as $config) {
            $this->setVersion($config);
        }
        return $this;
    }
}
<?php return ob_get_clean();