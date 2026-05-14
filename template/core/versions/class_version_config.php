<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$imports = $coreFile->getImports();
$imports->addCoreFileImportsByName(
    PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG,
    PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG,
    PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT,
    PHPFHIR_CLIENT_CLASSNAME_CONFIG,
);

$coreFiles = $config->getCoreFiles();

$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$serializeFormatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?> implements <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

{
    /** @var <?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?> $_unserializeConfig;

    /** @var <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG; ?> $_serializeConfig;

    /** @var null|<?php echo $clientConfigClass->getFullyQualifiedName(true); ?> */
    private null|<?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?> $_clientConfig = null;

    /**
     * <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?> constructor.
     * @param null|array|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $serializeConfig
     * @param null|array|<?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $unserializeConfig
     * @param null|string|array|<?php echo $clientConfigClass->getFullyQualifiedName(true); ?> $clientConfig
     */
    public function __construct(null|array|<?php echo $unserializeConfigClass; ?> $unserializeConfig = null,
                                null|array|<?php echo $serializeConfigClass; ?> $serializeConfig = null,
                                null|string|array|<?php echo $clientConfigClass; ?> $clientConfig = null)
    {
        if (null === $unserializeConfig) {
            $unserializeConfig = new <?php echo $unserializeConfigClass; ?>();
        }
        $this->setUnserializeConfig($unserializeConfig);
        if (null === $serializeConfig) {
            $serializeConfig = new <?php echo $serializeConfigClass; ?>();
        }
        $this->setSerializeConfig($serializeConfig);
        if (null !== $clientConfig) {
            $this->setClientConfig($clientConfig);
        }
    }

    /**
     * @param array|<?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return self
     */
    public function setUnserializeConfig(array|<?php echo PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?> $config): self
    {
        if (is_array($config)) {
            $config = new <?php echo PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?>(
                libxmlOpts: $config['libxmlOpts'] ?? null,
                jsonDecodeMaxDepth: $config['jsonDecodeMaxDepth'] ?? null,
                jsonDecodeOpts: $config['jsonDecodeOpts'] ?? null,
            );
        }
        $this->_unserializeConfig = $config;
        return $this;
    }

    /**
     * @return <?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?>

     */
    public function getUnserializeConfig(): <?php echo PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG; ?>

    {
        return $this->_unserializeConfig;
    }

    /**
     * @param array|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return self
     */
    public function setSerializeConfig(array|<?php echo PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG; ?> $config): self
    {
        if (is_array($config)) {
            $config = new <?php echo PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG; ?>(
                overrideSourceXMLNS: $config['overrideSourceXMLNS'] ?? null,
                rootXMLNS: $config['rootXMLNS'] ?? null,
                xhtmlLibxmlOpts: $config['xhtmlLibxmlOpts'] ?? null,
            );
        }
        $this->_serializeConfig = $config;
        return $this;
    }

    /**
     * @return <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?>

     */
    public function getSerializeConfig(): <?php echo PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG; ?>

    {
        return $this->_serializeConfig;
    }

    /**
     * @param null|string|array|<?php echo $clientConfigClass->getFullyQualifiedName(true); ?> $config
     * @return self
     */
    public function setClientConfig(null|string|array|<?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?> $config): self
    {
        if (null === $config) {
            $this->_clientConfig = null;
        } elseif (is_string($config)) {
            $this->_clientConfig = new <?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?>(address: $config);
        } elseif (is_array($config)) {
            if (!isset($config['address']) || '' === trim($config['address'])) {
                throw new \InvalidArgumentException(
                    'Client config array must contain a non-empty "address" key.'
                );
            }
            $this->_clientConfig = new <?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?>(
                address: $config['address'],
                defaultFormat: isset($config['defaultFormat']) ? <?php echo $serializeFormatEnum; ?>::from($config['defaultFormat']) : <?php echo $serializeFormatEnum; ?>::XML,
                defaultQueryParams: $config['defaultQueryParams'] ?? [],
                curlOpts: $config['curlOpts'] ?? [],
                parseResponseHeaders: $config['parseResponseHeaders'] ?? true,
            );
        } else {
            $this->_clientConfig = $config;
        }
        return $this;
    }

    /**
     * @return null|<?php echo $clientConfigClass->getFullyQualifiedName(true); ?>

     */
    public function getClientConfig(): null|<?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?>

    {
        return $this->_clientConfig;
    }
}
<?php return ob_get_clean();
