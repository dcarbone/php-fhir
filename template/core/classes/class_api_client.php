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


class <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?>

{
    private const _METHOD_GET = 'GET';
    private const _METHOD_PUT = 'PUT';
    private const _METHOD_POST = 'POST';
    private const _METHOD_PATCH = 'PATCH';
    private const _METHOD_DELETE = 'DELETE';
    private const _METHOD_HEAD = 'HEAD';

    private const _BASE_CURL_OPTS = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => 0,
        CURLOPT_USERAGENT => 'php-fhir client (build: <?php echo $config->getStandardDate(); ?>;)',
    ];

    /** @var string */
    private string $_baseUrl;
    /** @var array */
    private array $_curlOpts;
    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_RESPONSE_PARSER); ?> */
    private <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> $parser;

    /**
     * <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?> Constructor
     *
     * @param string $baseUrl URL of FHIR server to query
     * @param array $curlOpts Base curl options array
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_RESPONSE_PARSER); ?> $parser
     */
    public function __construct(string $baseUrl, array $curlOpts = [],  null|<?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?> $parser = null)
    {
        $this->_baseUrl = $baseUrl;
        $this->_curlOpts = $curlOpts;
        if (null === $parser) {
            $this->parser =  new <?php echo PHPFHIR_CLASSNAME_RESPONSE_PARSER; ?>(new <?php echo PHPFHIR_CLASSNAME_FACTORY_CONFIG; ?>());
        } else {
            $this->parser = $parser;
        }
    }

    /**
     * @return string
     */
    public function _getBaseUrl(): string
    {
        return $this->_baseUrl;
    }

    /**
     * @return array
     */
    public function _getBaseCurlOpts(): array
    {
        return $this->_curlOpts;
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    private function _exec(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        $url = sprintf('%s%s?%s', $this->_baseUrl, $path, http_build_query($queryParams, '', '&',  PHP_QUERY_RFC3986));
        
        $ch = curl_init($url);
        curl_setopt_array(
            $ch,
            self::_BASE_CURL_OPTS + $this->_curlOpts + $curlOpts
        );

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        $rc = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>();
        $rc->url = $url;
        $rc->code = $code;
        $rc->resp = $resp;
        $rc->err = $err;

        return $rc;
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function get(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec($path, $queryParams, [CURLOPT_CUSTOMREQUEST => self::_METHOD_GET] + $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function put(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec($path, $queryParams, [CURLOPT_CUSTOMREQUEST => self::_METHOD_PUT] + $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function post(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec($path, $queryParams, [CURLOPT_CUSTOMREQUEST => self::_METHOD_POST] + $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function patch(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec($path, $queryParams, [CURLOPT_CUSTOMREQUEST => self::_METHOD_PATCH] + $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function delete(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec($path, $queryParams, [CURLOPT_CUSTOMREQUEST => self::_METHOD_DELETE] + $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function head(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec($path, $queryParams, [CURLOPT_CUSTOMREQUEST => self::_METHOD_HEAD] + $curlOpts);
    }

    /**
     * Execute a read operation for a particular resource.
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_VERSION_ENUM_TYPE); ?> $resourceType
     * @param string $id
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_API_FORMAT); ?> $format
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function readOne(string|<?php echo PHPFHIR_VERSION_ENUM_TYPE; ?> $resourceType, string $id, <?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = <?php echo PHPFHIR_ENUM_API_FORMAT; ?>::JSON): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (!is_string($resourceType)) {
            $resourceType = $resourceType->value;
        }

        $rc = $this->get(sprintf('/%s/%s', $resourceType, $id), ['_format' => $format->value]);

        if ('' !== $rc->err) {
            throw new \Exception(sprintf('Error executing "%s": %s', $rc->url, $rc->err));
        }
        if (200 !== $rc->code) {
            throw new \Exception(sprintf('Error executing "%s": Expected 200 OK, saw %d', $rc->url, $rc->code));
        }

        return $this->parser->parse($rc->resp);
    }

    /**
     * Queries for the first available of a given resource
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param string|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_VERSION_ENUM_TYPE); ?> $resourceType
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_API_FORMAT); ?> $format
     * @return null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_INTERFACE_TYPE); ?>

     * @throws \Exception
     */
    public function readFirst(string|<?php echo PHPFHIR_VERSION_ENUM_TYPE; ?> $resourceType, <?php echo PHPFHIR_ENUM_API_FORMAT; ?> $format = <?php echo PHPFHIR_ENUM_API_FORMAT; ?>::JSON): null|<?php echo PHPFHIR_INTERFACE_TYPE; ?>

    {
        if (!is_string($resourceType)) {
            $resourceType = $resourceType->value;
        }

        $rc = $this->get(sprintf('/%s', $resourceType), ['_format' => $format->value, '_count' => '1']);

        if ('' !== $rc->err) {
            throw new \Exception(sprintf('Error executing "%s": %s', $rc->url, $rc->err));
        }
        if (200 !== $rc->code) {
            throw new \Exception(sprintf('Error executing "%s": Expected 200 OK, saw %d', $rc->url, $rc->code));
        }

        return $this->parser->parse($rc->resp);
    }
}
<?php return ob_get_clean();