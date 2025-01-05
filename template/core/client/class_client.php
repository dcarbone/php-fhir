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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$imports = $coreFile->getImports();

$imports->addCoreFileImportsByName(
    PHPFHIR_CLASSNAME_CLIENT_CONFIG,
    PHPFHIR_CLASSNAME_CLIENT_REQUEST,
    PHPFHIR_CLASSNAME_CLIENT_RESPONSE,
    PHPFHIR_ENUM_CLIENT_HTTP_METHOD,
);

$confClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CLIENT_CONFIG);
$reqClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CLIENT_REQUEST);
$respClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CLIENT_RESPONSE);
$htMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENUM_CLIENT_HTTP_METHOD);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(false); ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo PHPFHIR_CLASSNAME_CLIENT_CLIENT; ?>

 *
 * Basic implementation of the <?php echo PHPFHIR_INTERFACE_CLIENT_CLIENT; ?> interface.
 */
class <?php echo PHPFHIR_CLASSNAME_CLIENT_CLIENT; ?> implements <?php echo PHPFHIR_INTERFACE_CLIENT_CLIENT; ?>

{
    private const _PARAM_FORMAT = '_format';
    private const _PARAM_SORT = '_sort';
    private const _PARAM_COUNT = '_count';

    private const _BASE_CURL_OPTS = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'php-fhir client (build: <?php echo $config->getStandardDate(); ?>;)',
    ];

    protected <?php echo PHPFHIR_CLASSNAME_CLIENT_CONFIG; ?> $_config;

    /**
     * <?php echo PHPFHIR_CLASSNAME_CLIENT_CLIENT; ?> Constructor
     *
     * @param string|<?php echo $confClass->getFullyQualifiedName(true); ?> $config Fully qualified address of FHIR server, or configuration object.
     */
    public function __construct(string|<?php echo PHPFHIR_CLASSNAME_CLIENT_CONFIG; ?> $config)
    {
        if (is_string($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CLIENT_CONFIG; ?>($config);
        }
        $this->_config = $config;
    }

    public function getConfig(): <?php echo PHPFHIR_CLASSNAME_CLIENT_CONFIG; ?>

    {
        return $this->_config;
    }

    /**
     * @param <?php echo $reqClass->getFullyQualifiedName(true); ?> $request
     * @return <?php echo $respClass->getFullyQualifiedName(true); ?>

     */
    public function exec(<?php echo PHPFHIR_CLASSNAME_CLIENT_REQUEST; ?> $request): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

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

        $rc = new <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>();

        $url = "{$this->_config->getAddress()}{$request->path}?" . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $curlOpts = self::_BASE_CURL_OPTS
            + [CURLOPT_CUSTOMREQUEST => $request->method]
            + array_merge($this->_config->getCurlOpts(), $request->options ?? []);

        $parseHeaders = ($this->_config->getParseHeaders() && (!isset($req->parseHeaders) || $req->parseHeaders))
            || (isset($req->parseHeaders) && $req->parseHeaders);

        if ($parseHeaders) {
            $curlOpts[CURLOPT_HEADER] = 1;
            $curlOpts[CURLOPT_HEADERFUNCTION] = function($ch, string $line) use (&$rc): int {
                    return $rc->headers->addLine($line);
                };
        } else {
            $curlOpts[CURLOPT_HEADER] = 0;
        }

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
        $errno = curl_errno($ch);
        curl_close($ch);

        $rc->method = $request->method;
        $rc->url = $url;
        $rc->code = $code;
        $rc->err = $err;
        $rc->errno = $errno;

        if ($parseHeaders) {
            $rc->resp = substr($resp, $rc->headers->getLength());
        } else {
            $rc->resp = $resp;
        }

        return $rc;
    }

    private function _exec(string|<?php echo $htMethodEnum->getEntityName(); ?> $method, string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        $req = new <?php echo PHPFHIR_CLASSNAME_CLIENT_REQUEST; ?>();
        $req->method = (string)$method;
        $req->path = $path;
        $req->queryParams = $queryParams;
        $req->options = $curlOpts;
        return $this->exec($req);
    }

    public function get(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_exec(<?php echo $htMethodEnum->getEntityName(); ?>::GET, $path, $queryParams, $curlOpts);
    }

    public function put(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_exec(<?php echo $htMethodEnum->getEntityName(); ?>::PUT, $path, $queryParams, $curlOpts);
    }

    public function post(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_exec(<?php echo $htMethodEnum->getEntityName(); ?>::POST, $path, $queryParams, $curlOpts);
    }

    public function patch(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_exec(<?php echo $htMethodEnum->getEntityName(); ?>::PATCH, $path, $queryParams, $curlOpts);
    }

    public function delete(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_exec(<?php echo $htMethodEnum->getEntityName(); ?>::DELETE, $path, $queryParams, $curlOpts);
    }

    public function head(string $path, array $queryParams = [], array $curlOpts = []): <?php echo PHPFHIR_CLASSNAME_CLIENT_RESPONSE; ?>

    {
        return $this->_exec(<?php echo $htMethodEnum->getEntityName(); ?>::HEAD, $path, $queryParams, $curlOpts);
    }
}
<?php return ob_get_clean();
