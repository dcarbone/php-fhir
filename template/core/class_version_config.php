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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    PHPFHIR_CLASSNAME_SERIALIZE_CONFIG,
    PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG,
);

$coreFiles = $config->getCoreFiles();

$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?> implements <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

{
    // These are used when no default configuration was provided during version code genreation

    protected const _DEFAULT_UNSERIALIZE_CONFIG = [
        'libxmlOpts' => LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL,
        'jsonDecodeMaxDepth' => 512,
    ];
    protected const _DEFAULT_SERIALIZE_CONFIG = [
        'overrideRootXMLNS' => false,
        'rootXMLNS' => PHPFHIR_FHIR_XMLNS,
    ];

    /** @var <?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?> $unserializeConfig;

    /** @var <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> */
    private <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?> $serializeConfig;

    /**
     * <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?> constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach(<?php echo PHPFHIR_ENUM_VERSION_CONFIG_KEY; ?>::cases() as $k) {
            if (isset($config[$k->value]) || array_key_exists($k->value, $config)) {
                $this->{"set{$k->value}"}($config[$k->value]);
            }
        }

        if (!isset($this->_unserializeConfig)) {
            $this->setUnserializeConfig(self::_DEFAULT_UNSERIALIZE_CONFIG);
        }
        if (!isset($this->_serializeConfig)) {
            $this->setSerializeConfig(self::_DEFAULT_SERIALIZE_CONFIG);
        }
    }

    /**
     * @param array|<?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return self
     */
    public function setUnserializeConfig(array|<?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?> $config): self
    {
        if (is_array($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?>($config);
        }
        $this->unserializeConfig = $config;
        return $this;
    }

    /**
     * @return <?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?>

     */
    public function getUnserializeConfig(): <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?>

    {
        return $this->unserializeConfig;
    }

    /**
     * @param array|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return self
     */
    public function setSerializeConfig(array|<?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?> $config): self
    {
        if (is_array($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?>($config);
        }
        $this->serializeConfig = $config;
        return $this;
    }

    /**
     * @return <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?>

     */
    public function getSerializeConfig(): <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?>

    {
        return $this->serializeConfig;
    }
}
<?php return ob_get_clean();
