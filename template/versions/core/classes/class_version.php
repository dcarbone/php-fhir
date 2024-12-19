<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */

$config = $version->getConfig();

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment(); ?>


use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_VERSION); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_VERSION_CONFIG); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_VERSION_CONFIG); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_INTERFACE_VERSION_TYPE_MAP); ?>;

class <?php echo PHPFHIR_CLASSNAME_VERSION; ?> implements <?php echo PHPFHIR_INTERFACE_VERSION; ?>

{
    public const NAME = '<?php echo $version->getName(); ?>';
    public const SOURCE_URL = '<?php echo $version->getSourceUrl(); ?>';
    public const SOURCE_VERSION = '<?php echo $version->getSourceMetadata()->getFHIRVersion(false); ?>';
    public const SOURCE_GENERATION_DATE = '<?php echo $version->getSourceMetadata()->getFHIRGenerationDate(); ?>';

    private const _GENERATED_CONFIG = <?php echo pretty_var_export($version->getDefaultConfig()->toArray(), 1); ?>;

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?> */
    private <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?> $_config;

    /** @var <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION_TYPE_MAP); ?> */
    private static <?php echo PHPFHIR_CLASSNAME_VERSION_TYPE_MAP; ?> $_typeMap;

    /**
     * <?php echo PHPFHIR_CLASSNAME_VERSION; ?> Constructor
     * @param null|array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?> $config
     */
    public function __construct(null|array|<?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?> $config = null)
    {
        if (!is_object($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?>(array_merge(self::_GENERATED_CONFIG, (array)$config));
        } else if (!($config instanceof <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>)) {
            throw new \InvalidArgumentException(sprintf(
                '$config must be an instance of \\%s, %s given',
                <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>::class,
                get_class($config)
            ));
        }
        $this->_config = $config;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getSourceUrl(): string
    {
        return self::SOURCE_URL;
    }

    /**
     * @return string
     */
    public function getSourceVersion(): string
    {
        return self::SOURCE_VERSION;
    }

    /**
     * @return string
     */
    public function getSourceGenerationDate(): string
    {
        return self::SOURCE_GENERATION_DATE;
    }

    /**
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONFIG); ?>

     */
    public function getConfig(): <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

    {
        return $this->_config;
    }

    /**
     * @return <?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION_TYPE_MAP); ?>

     */
    public function getTypeMap(): <?php echo PHPFHIR_INTERFACE_VERSION_TYPE_MAP; ?>

    {
        if (!isset(self::$_typeMap)) {
            self::$_typeMap = new <?php echo PHPFHIR_CLASSNAME_VERSION_TYPE_MAP; ?>();
        }
        return self::$_typeMap;
    }
}
<?php return ob_get_clean();
