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
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $config->getFullyQualifiedName(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


/**
 * Class <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?>

 *
 * Configuration class for built-in FHIR API client.  If you are not using the built-in client,
 * you can ignore this class.
 */
class <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?>

{
    /** @var string */
    private string $_address;
    /** @var array */
    private array $_curlOpts;
    /** @var array */
    private array $_queryParams;
    /** @var null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_API_FORMAT); ?> */
    private null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $_defaultFormat;

    /**
     * <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?> Constructor
     *
     * @param string $address Fully qualified address of FHIR server, including scheme, port, and any path prefix.
     * @param array $curlOpts Base curl options array.  These will be added to every request.  May be overridden by an individual request.
     * @param array $queryParams Base query parameters array.  These will be added to every request.  May be overridden by an individual request.
     * @param null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $defaultFormat Default format to request from server.  If not provided, server default will be used.  May be overridden by an individual request.
     */
    public function __construct(string $address,
                                array $curlOpts = [],
                                array $queryParams = [],
                                null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?> $defaultFormat = null)
    {
        $this->_address = $address;
        $this->_curlOpts = $curlOpts;
        $this->_queryParams = $queryParams;
        $this->_defaultFormat = $defaultFormat;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->_address;
    }

    /**
     * @return array
     */
    public function getCurlOpts(): array
    {
        return $this->_curlOpts;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->_queryParams;
    }

    /**
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_API_FORMAT); ?>

     */
    public function getDefaultFormat(): null|<?php echo PHPFHIR_ENUM_API_FORMAT; ?>

    {
        return $this->_defaultFormat;
    }
}
<?php return ob_get_clean();
