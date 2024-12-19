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
 * Class <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?>

 *
 * Basic implementation of the <?php echo PHPFHIR_INTERFACE_API_CLIENT; ?> interface.
 */
class <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?> implements <?php echo PHPFHIR_INTERFACE_API_CLIENT; ?>

{
    private const _METHOD_GET = 'GET';
    private const _METHOD_PUT = 'PUT';
    private const _METHOD_POST = 'POST';
    private const _METHOD_PATCH = 'PATCH';
    private const _METHOD_DELETE = 'DELETE';
    private const _METHOD_HEAD = 'HEAD';

    private const _PARAM_FORMAT = '_format';
    private const _PARAM_SORT = '_sort';
    private const _PARAM_COUNT = '_count';

    private const _BASE_CURL_OPTS = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => 0,
        CURLOPT_USERAGENT => 'php-fhir client (build: <?php echo $config->getStandardDate(); ?>;)',
    ];

    /** @var <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_CONFIG); ?> */
    protected <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?> $_config;

    /**
     * <?php echo PHPFHIR_CLASSNAME_API_CLIENT; ?> Constructor
     *
     * @param string|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_CONFIG); ?> $config Fully qualified address of FHIR server, or configuration object.
     */
    public function __construct(string|<?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?> $config)
    {
        if (is_string($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?>($config);
        }
        $this->_config = $config;
    }

    /**
     * Return configuration used by this client.
     *
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_CONFIG); ?>

     */
    public function getConfig(): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_CONFIG; ?>

    {
        return $this->_config;
    }

    /**
     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_REQUEST); ?> $request
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function exec(<?php echo PHPFHIR_CLASSNAME_API_CLIENT_REQUEST; ?> $request): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>

    {
        $queryParams = array_merge($this->_config->getQueryParams(), $request->queryParams ?? []);

        $format = $request->format ?? $this->_config->getDefaultFormat();
        if (null !== $format) {
            $queryParams[self::_PARAM_FORMAT] = $format->value;
        }
        if (isset($request->sort)) {
            $queryParams[self::_PARAM_SORT] = $request->sort->value;
        }
        if (isset($request->count)) {
            $queryParams[self::_PARAM_COUNT] = $request->count;
        }

        $url = sprintf(
            '%s%s?%s',
            $this->_config->getAddress(),
            $request->path,
            http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986),
        );

        $curlOpts = self::_BASE_CURL_OPTS +
            [CURLOPT_CUSTOMREQUEST => $request->method] +
            array_merge($this->_config->getCurlOpts(), $request->options ?? []);

        $ch = curl_init($url);
        if (!curl_setopt_array($ch, $curlOpts)) {
            throw new \DomainException(sprintf(
                'Error setting curl opts for query "%s" with value: %s',
                $url,
                var_export($curlOpts, true),
            ));
        }

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
     * @param string $method
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>

     */
    private function _exec(string $method, string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>

    {
        $req = new <?php echo PHPFHIR_CLASSNAME_API_CLIENT_REQUEST; ?>();
        $req->method = $method;
        $req->path = $path;
        $req->queryParams = $queryParams;
        $req->options = $curlOpts;
        return $this->exec($req);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function get(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec(self::_METHOD_GET, $path, $queryParams, $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function put(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec(self::_METHOD_PUT, $path, $queryParams, $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function post(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec(self::_METHOD_POST, $path, $queryParams, $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function patch(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec(self::_METHOD_PATCH, $path, $queryParams, $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function delete(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec(self::_METHOD_DELETE, $path, $queryParams, $curlOpts);
    }

    /**
     * @param string $path
     * @param array $queryParams
     * @param array $curlOpts
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE); ?>

     */
    public function head(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE; ?>
    {
        return $this->_exec(self::_METHOD_HEAD, $path, $queryParams, $curlOpts);
    }
}
<?php return ob_get_clean();