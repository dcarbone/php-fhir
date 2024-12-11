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
$defConfig = $version->getDefaultConfig();
$namespace = $version->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $version->getSourceMetadata()->getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_CLASSNAME_ABSTRACT_VERSION_CONFIG); ?>;
use <?php echo $config->getFullyQualifiedName(false, PHPFHIR_ENUM_VERSION_CONFIG_KEY); ?>;

/**
 * Class <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
class <?php echo PHPFHIR_CLASSNAME_VERSION_CONFIG; ?> extends <?php echo PHPFHIR_CLASSNAME_ABSTRACT_VERSION_CONFIG; ?>

{
    private const _VERSION_DEFAULT_UNSERIALIZE_CONFIG = <?php echo pretty_var_export($defConfig->getUnserializeConfig(), 1); ?>;
    private const _VERSION_DEFAULT_SERIALIZE_CONFIG = <?php echo pretty_var_export($defConfig->getSerializeConfig(), 1); ?>;

    /**
     * <?php echo $version->getName(); ?> version config constructor.
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
            $this->setUnserializeConfig(array_merge(parent::_DEFAULT_UNSERIALIZE_CONFIG, self::_VERSION_DEFAULT_UNSERIALIZE_CONFIG));
        }
        if (!isset($this->_serializeConfig)) {
            $this->setSerializeConfig(array_merge(parent::_DEFAULT_SERIALIZE_CONFIG, self::_VERSION_DEFAULT_SERIALIZE_CONFIG));
        }
    }
}
<?php return ob_get_clean();
