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
/** @var \DCarbone\PHPFHIR\Version $version */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Class <?php echo $version->getVersionClassname(); if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo $version->getVersionClassname(); ?> implements <?php echo PHPFHIR_INTERFACE_FHIR_VERSION; ?>

{
    /** @var <?php $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> */
    private <?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $_config;

    /** @var <?php $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE_MAP); ?> */
    private static <?php echo PHPFHIR_INTERFACE_TYPE_MAP; ?> $_typeMap;

    /**
     * <?php echo $version->getVersionClassname(); ?> Constructor
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> $config
     */
    public function __construct(null|<?php echo PHPFHIR_CLASSNAME_CONFIG; ?> $config = null)
    {
        if (null === $config) {
            $this->_config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        } else {
            $this->_config = $config;
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return <?php echo PHPFHIR_CLASSNAME_VERSION_CONSTANTS ?>::VERSION_NAME;
    }

    /**
     * @return string
     */
    public function getSourceUrl(): string
    {
        return <?php echo PHPFHIR_CLASSNAME_VERSION_CONSTANTS ?>::VERSION_SOURCE_URL;
    }

    /**
     * @return string
     */
    public function getSourceVersion(): string
    {
        return <?php echo PHPFHIR_CLASSNAME_VERSION_CONSTANTS ?>::VERSION_SOURCE_VERSION;
    }

    /**
     * @return string
     */
    public function getSourceGenerationDate(): string
    {
        return <?php echo PHPFHIR_CLASSNAME_VERSION_CONSTANTS ?>::VERSION_SOURCE_GENERATION_DATE;
    }

    /**
     * @return <?php $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?>

     */
    public function getConfig(): <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>

    {
        return $this->_config;
    }

    /**
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE_MAP); ?>

     */
    public function getTypeMap(): <?php echo PHPFHIR_INTERFACE_TYPE_MAP; ?>

    {
        if (!isset(self::$_typeMap)) {
            self::$_typeMap = new <?php echo $version->getVersionClassname(); ?>();
        }
        return self::$_typeMap;
    }
}
<?php return ob_get_clean();
