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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$fqns = $type->getFullyQualifiedNamespace(true);
$classDocumentation = $type->getDocBlockDocumentationFragment(1, true);
$namespace = trim($fqns, PHPFHIR_NAMESPACE_TRIM_CUTSET);
$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_FILE_DIR . DIRECTORY_SEPARATOR . 'header_type.php',
    [
        'config' => $config,
        'fqns' => $fqns,
        'skipImports' => false,
        'type' => $type,
        'types' => $types,
    ]
);

// build class header ?>
/**<?php if ('' !== $classDocumentation) : ?>

<?php echo $classDocumentation; ?>
 *<?php endif; ?>

 * Class <?php echo $type->getClassName(); ?>

 * @package <?php echo $fqns; ?>

 */
class <?php echo $type->getClassName(); ?>
{
    use <?php echo PHPFHIR_TRAIT_CHANGE_TRACKING; ?>;

    /** @var null|string */
    private null|string $_data = null;

    /**
     * <?php echo PHPFHIR_XHTML_TYPE_NAME; ?> Constructor
     * @param null|string $data
     */
    public function __construct(null|string $data = null)
    {
        $this->setData($data);
    }

    /**
     * @return null|string
     */
    public function getData(): null|string
    {
        return $this->_data;
    }

    /**
     * @param null|string $data
     * @return static
     */
    public function setData(null|string $data): self
    {
        $this->_trackValueSet($this->_data, $data);
        $this->_data = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->_data ?? '';
    }
}<?php return ob_get_clean();