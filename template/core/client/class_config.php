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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

/**
 * Class <?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?>

 *
 * Configuration class for built-in FHIR API client.  If you are not using the built-in client,
 * you can ignore this class.
 */
class <?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?>

{
    /** @var string */
    private string $_address;
    /** @var array */
    private array $_curlOpts;
    /** @var array */
    private array $_queryParams;
    /** @var null|<?php echo $formatEnum->getFullyQualifiedName(true); ?> */
    private null|<?php echo PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT; ?> $_defaultFormat;
    /** @var bool */
    private bool $_parseResponseHeaders;

    /**
     * <?php echo PHPFHIR_CLIENT_CLASSNAME_CONFIG; ?> Constructor
     *
     * @param string $address Fully qualified address of FHIR server, including scheme, port, and any path prefix.
     * @param null|<?php echo $formatEnum->getFullyQualifiedName(true); ?> $defaultFormat Default format to request from server.  If not provided, server default will be used.  May be overridden by an individual request.
     * @param array $queryParams Base query parameters array.  These will be added to every request.  May be overridden by an individual request.
     * @param array $curlOpts Base curl options array.  These will be added to every request.  May be overridden by an individual request.
     * @param bool $parseResponseHeaders Whether or not to parse headers from response.  This adds a small amount of overhead, so it is recommended to only set to true if actually used.
     */
    public function __construct(string $address,
                                null|<?php echo PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT; ?> $defaultFormat = null,
                                array $queryParams = [],
                                array $curlOpts = [],
                                bool $parseResponseHeaders = false)
    {
        $this->_address = $address;
        $this->_defaultFormat = $defaultFormat;
        $this->_queryParams = $queryParams;
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
     * @return null|<?php echo $formatEnum->getFullyQualifiedName(true); ?>

     */
    public function getDefaultFormat(): null|<?php echo PHPFHIR_CLIENT_ENUM_RESPONSE_FORMAT; ?>

    {
        return $this->_defaultFormat;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->_queryParams;
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
