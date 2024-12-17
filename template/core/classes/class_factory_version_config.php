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


class <?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG; ?>

{
    /** @var string */
    private string $_name;
    /** @var string */
    private string $_class;
    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?> */
    private <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?> $_config;

    /**
     * <?php echo PHPFHIR_CLASSNAME_FACTORY_VERSION_CONFIG; ?> Constructor
     * @param string $name Unique version name
     * @param string $class Fully qualified classname of Version
     * @param null|array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?> $config Version configuration
     */
    public function __construct(string $name, string $class, null|array|<?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?> $config = null)
    {
        $this->_name = $name;
        $this->_class = $class;
        if (null !== $config && [] !== $config) {
            if (is_array($config)) {
                $config = new <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?>($config);
            }
            $this->_config = $config;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->_class;
    }

    /**
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION_CONFIG); ?>

     */
    public function getConfig(): null|<?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

    {
        return $this->_config ?? null;
    }
}
<?php return ob_get_clean();