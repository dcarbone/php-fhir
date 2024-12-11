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

/** @var \DCarbone\PHPFHIR\Config $config */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n";
?>

/**
 * Class <?php echo PHPFHIR_CLASSNAME_ABSTRACT_VERSION_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class <?php echo PHPFHIR_CLASSNAME_ABSTRACT_VERSION_CONFIG; ?> implements <?php echo PHPFHIR_INTERFACE_VERSION_CONFIG; ?>

{
    // These are used when no default configuration was provided during version code genreation

    protected const _DEFAULT_UNSERIALIZE_CONFIG = [
        'libxmlOpts' => LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL,
        'jsonDecodeMaxDepth' => 512,
    ];
    protected const _DEFAULT_SERIALIZE_CONFIG = [
        'overrideRootXmlns' => false,
        'rootXmlns' => PHPFHIR_FHIR_XMLNS,
    ];

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG); ?> */
    protected <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?> $unserializeConfig;

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_SERIALIZE_CONFIG); ?> */
    protected <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?> $serializeConfig;

    /**
     * @param array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG); ?> $config
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
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG); ?>

     */
    public function getUnserializeConfig(): <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?>

    {
        if (!isset($this->unserializeConfig)) {
            $this->unserializeConfig = new <?php echo PHPFHIR_CLASSNAME_UNSERIALIZE_CONFIG; ?>(self::_DEFAULT_UNSERIALIZE_CONFIG);
        }
        return $this->unserializeConfig;
    }

    /**
     * @param array|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_SERIALIZE_CONFIG); ?> $config
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
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_SERIALIZE_CONFIG); ?>

     */
    public function getSerializeConfig(): <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?>

    {
        if (!isset($this->serializeConfig)) {
            $this->serializeConfig = new <?php echo PHPFHIR_CLASSNAME_SERIALIZE_CONFIG; ?>(self::_DEFAULT_SERIALIZE_CONFIG);
        }
        return $this->serializeConfig;
    }
}
<?php return ob_get_clean();
