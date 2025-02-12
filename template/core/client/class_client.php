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

$clientInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_INTERFACE_CLIENT);
$clientConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_CONFIG);
$clientRequestClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_REQUEST);
$clientResponseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);
$clientHTTPMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);

$imports->addCoreFileImports(
    $clientInterface,
    $clientConfigClass,
    $clientRequestClass,
    $clientResponseClass,
    $clientHTTPMethodEnum,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo $coreFile; ?>

 *
 * Basic implementation of the <?php echo $clientInterface; ?> interface.
 */
class <?php echo $coreFile; ?> implements <?php echo $clientInterface; ?>

{
    private const _PARAM_FORMAT = '_format';
    private const _PARAM_SORT = '_sort';
    private const _PARAM_COUNT = '_count';

    private const _BASE_CURL_OPTS = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'php-fhir client (build: <?php echo $config->getStandardDate(); ?>;)',
    ];

    protected <?php echo $clientConfigClass; ?> $_config;

    /**
     * <?php echo $coreFile; ?> Constructor
     *
     * @param string|<?php echo $clientConfigClass->getFullyQualifiedName(true); ?> $config Fully qualified address of FHIR server, or configuration object.
     */
    public function __construct(string|<?php echo $clientConfigClass; ?> $config)
    {
        if (is_string($config)) {
            $config = new <?php echo $clientConfigClass; ?>(address: $config);
        }
        $this->_config = $config;
    }

    public function getConfig(): <?php echo $clientConfigClass; ?>

    {
        return $this->_config;
    }

    /**
     * @param <?php echo $clientRequestClass->getFullyQualifiedName(true); ?> $request
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function exec(<?php echo $clientRequestClass; ?> $request): <?php echo $clientResponseClass; ?>

    {
        $queryParams = array_merge($this->_config->getQueryParams(), $request->queryParams ?? []);

        $format = $request->format ?? $this->_config->getDefaultFormat();
        if (null !== $format) {
            $queryParams[self::_PARAM_FORMAT] = $format;
        }
        if (isset($request->sort)) {
            $queryParams[self::_PARAM_SORT] = $request->sort;
        }
        if (isset($request->count)) {
            $queryParams[self::_PARAM_COUNT] = $request->count;
        }

        $rc = new <?php echo $clientResponseClass; ?>();

        $url = "{$this->_config->getAddress()}{$request->path}?" . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $curlOpts = self::_BASE_CURL_OPTS
            + [CURLOPT_CUSTOMREQUEST => $request->method]
            + array_merge($this->_config->getCurlOpts(), $request->options ?? []);

        $parseResponseHeaders = ($this->_config->getParseResponseHeaders()
            && (!isset($req->parseResponseHeaders) || $req->parseResponseHeaders))
            || (isset($req->parseResponseHeaders) && $req->parseResponseHeaders);

        if ($parseResponseHeaders) {
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

        if (0 === $errno) {
            if ($parseResponseHeaders) {
                $rc->resp = substr($resp, $rc->headers->getLength());
            } else {
                $rc->resp = $resp;
            }
        }

        return $rc;
    }
}
<?php return ob_get_clean();
