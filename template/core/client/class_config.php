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

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);

$imports->addCoreFileImports(
    $formatEnum,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo $coreFile; ?>

 *
 * Configuration class for built-in FHIR API client.  If you are not using the built-in client,
 * you can ignore this class.
 */
class <?php echo $coreFile; ?>

{
    private string $_address;
    private array $_curlOpts;
    private array $_defaultQueryParams;
    private <?php echo $formatEnum; ?> $_defaultFormat;
    private bool $_parseResponseHeaders;

    /**
     * <?php echo $coreFile; ?> Constructor
     *
     * @param string $address Fully qualified address of FHIR server, including scheme, port, and any path prefix.
     * @param <?php echo $formatEnum->getFullyQualifiedName(true); ?> $defaultFormat Default serialization format.  Defaults to XML.
     * @param array $defaultQueryParams Base query parameters array.  These will be added to every request.  May be overridden by an individual request.
     * @param array $curlOpts Base curl options array.  These will be added to every request.  May be overridden by an individual request.
     * @param bool $parseResponseHeaders Whether or not to parse headers from response.  This adds overhead to parsing each response, but is also necessary to extract response version information.
     */
    public function __construct(string $address,
                                <?php echo $formatEnum; ?> $defaultFormat = <?php echo $formatEnum; ?>::XML,
                                array $defaultQueryParams = [],
                                array $curlOpts = [],
                                bool $parseResponseHeaders = true)
    {
        $this->_address = $address;
        $this->_defaultFormat = $defaultFormat;
        $this->_defaultQueryParams = $defaultQueryParams;
        $this->_curlOpts = $curlOpts;
        $this->_parseResponseHeaders = $parseResponseHeaders;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->_address;
    }

    /**
     * @return <?php echo $formatEnum->getFullyQualifiedName(true); ?>

     */
    public function getDefaultFormat(): <?php echo $formatEnum; ?>

    {
        return $this->_defaultFormat;
    }

    /**
     * @return array
     */
    public function getDefaultQueryParams(): array
    {
        return $this->_defaultQueryParams;
    }

    /**
     * @return array
     */
    public function getCurlOpts(): array
    {
        return $this->_curlOpts;
    }

    /**
     * @return bool
     */
    public function getParseResponseHeaders(): bool
    {
        return $this->_parseResponseHeaders;
    }
}
<?php return ob_get_clean();
