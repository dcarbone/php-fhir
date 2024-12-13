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
class <?php echo PHPFHIR_CLASSNAME_FACTORY; ?>

{
    private const _CLASSES = [
<?php foreach($config->getVersionsIterator() as $version) : ?>
        '<?php echo $version->getName(); ?>' => '<?php echo $version->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_VERSION); ?>',
<?php endforeach; ?>
    ];

    private const _CONFIGS = [
<?php foreach($config->getVersionsIterator() as $version) : ?>
        '<?php echo $version->getName(); ?>' => <?php echo pretty_var_export($version->getDefaultConfig()->toArray(), 2); ?>,
<?php endforeach; ?>
    ];

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_CONFIG); ?> */
    private Config $_config;
    /** @var  */
    private array $_versions = [];

    /**
     * <?php echo PHPFHIR_CLASSNAME_FACTORY; ?> Constructor
     */
    public function __construct()
    {
        $this->_config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>
    }
}
<?php return ob_get_clean();
