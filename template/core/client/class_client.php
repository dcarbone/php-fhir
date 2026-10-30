<?php declare(strict_types=1);

/*
 * Copyright 2024-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$requestClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_REQUEST);
$responseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);
$httpMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);
$formatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);
$responseHeaderClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE_HEADERS);

$fhirVersion = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_FHIR_VERSION);
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);

$imports->addCoreFileImports(
    $clientInterface,
    $clientConfigClass,
    $requestClass,
    $responseClass,
    $httpMethodEnum,
    $formatEnum,
    $responseHeaderClass,

    $fhirVersion,
    $resourceTypeInterface,
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
     * @param <?php echo $requestClass->getFullyQualifiedName(true); ?> $request
     * @return <?php echo $responseClass->getFullyQualifiedName(true); ?>

     */
    public function exec(<?php echo $requestClass; ?> $request): <?php echo $responseClass; ?>

    {
        $queryParams = array_merge($this->_config->getDefaultQueryParams(), $request->queryParams ?? []);
        $format = $request->format ?? $this->_config->getDefaultFormat();
        $parseResponseHeaders = match(true) {
            isset($request->parseResponseHeaders) => $request->parseResponseHeaders,
            default => $this->_config->getParseResponseHeaders(),
        };

        $queryParams[self::_PARAM_FORMAT] = $format->value;
        if (isset($request->sort)) {
            $queryParams[self::_PARAM_SORT] = $request->sort;
        }
        if (isset($request->count)) {
            $queryParams[self::_PARAM_COUNT] = $request->count;
        }

        $url = "{$this->_config->getAddress()}{$request->path}?" . http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $rc = new <?php echo $responseClass; ?>($request->method, $url, $format);

        $curlOpts = $this->_buildCurlOpts($request, $rc, $format, $parseResponseHeaders);

        $ch = curl_init($url);
        if (!curl_setopt_array($ch, $curlOpts)) {
            throw new \DomainException(sprintf(
                'curl_setopt_array returned false for "%s" with options: %s',
                $url,
                var_export($curlOpts, true),
            ));
        }

        $resp = curl_exec($ch);
        $rc->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $rc->err = curl_error($ch);
        $rc->errno = curl_errno($ch);

        if (0 === $rc->errno) {
            if ($parseResponseHeaders) {
                $rc->resp = substr($resp, $rc->headers->getLength());
            } else {
                $rc->resp = $resp;
            }
        }

        return $rc;
    }

    /**
     * Builds the final curl options array for a request.
     *
     * Precedence, lowest to highest: built-in defaults, client config options, per-request options.
     * CURLOPT_HTTPHEADER is merged by header name rather than replaced wholesale.
     */
    protected function _buildCurlOpts(<?php echo $requestClass; ?> $request,
                                      <?php echo $responseClass; ?> $rc,
                                      <?php echo $formatEnum; ?> $format,
                                      bool $parseResponseHeaders): array
    {
        $configOpts = $this->_config->getCurlOpts();
        $requestOpts = $request->clientOptions ?? [];

        $curlOpts = array_replace(self::_BASE_CURL_OPTS, $configOpts, $requestOpts);

        $curlOpts[CURLOPT_HTTPHEADER] = $this->_mergeHttpHeaders(
            $configOpts[CURLOPT_HTTPHEADER] ?? [],
            $requestOpts[CURLOPT_HTTPHEADER] ?? [],
        );

        if ($parseResponseHeaders) {
            $rc->headers = new <?php echo $responseHeaderClass; ?>();
            $curlOpts[CURLOPT_HEADER] = 1;
            $curlOpts[CURLOPT_HEADERFUNCTION] = function($ch, string $line) use (&$rc): int {
                    return $rc->headers->addLine($line);
                };
        }

        if (<?php echo $httpMethodEnum; ?>::GET !== $request->method) {
            $curlOpts[CURLOPT_CUSTOMREQUEST] = $request->method->value;
            $curlOpts[CURLOPT_HTTPHEADER][] = "X-HTTP-Method-Override: {$request->method->value}";
        }

        $curlOpts[CURLOPT_HTTPHEADER][] = $this->_buildAcceptHeader($request, $format);

        if (isset($request->resource)) {
            $curlOpts[CURLOPT_HTTPHEADER][] = $this->_buildContentTypeHeader($request, $format);
        }

        return $curlOpts;
    }

    /**
     * Merges config-level and request-level CURLOPT_HTTPHEADER lists.
     *
     * Header names are matched case-insensitively.  A request header replaces a config header of the same name
     * (in the config header's original position); config headers with no request counterpart are retained, and
     * request-only headers are appended.
     *
     * @param string[] $configHeaders
     * @param string[] $requestHeaders
     * @return string[]
     */
    protected function _mergeHttpHeaders(array $configHeaders, array $requestHeaders): array
    {
        $merged = [];
        foreach ([$configHeaders, $requestHeaders] as $headers) {
            foreach ($headers as $header) {
                $header = (string)$header;
                $name = self::_httpHeaderName($header);
                if (null === $name) {
                    $merged[] = $header;
                } else {
                    $merged[$name] = $header;
                }
            }
        }
        return array_values($merged);
    }

    /**
     * Extracts the lower-cased header name from a curl-style header line, or null if there is none.
     *
     * Supports the curl "Name:" (unset header) and "Name;" (send header with empty value) forms.
     */
    private static function _httpHeaderName(string $header): null|string
    {
        $len = strcspn($header, ':;');
        if (0 === $len || $len === strlen($header)) {
            return null;
        }
        return strtolower(trim(substr($header, 0, $len)));
    }

    protected function _buildAcceptHeader(<?php echo $requestClass; ?> $request,
                                          <?php echo $formatEnum; ?> $format): string
    {
        $ver = match(true) {
            isset($request->version) => $request->version,
            isset($request->resource) => $request->resource->_getFHIRVersion(),
            default => null,
        };
        if (null === $ver) {
            return "Accept: application/{$format->value}+json, application/json+{$format->value}";
        } else if ($ver->getFHIRVersionInteger() < <?php echo $fhirVersion; ?>::STU3_MIN_VERSION_INTEGER) {
            return "Accept: application/{$format->value}+fhir; fhirVersion={$ver->getFHIRShortVersion()}";
        } else {
            return "Accept: application/fhir+{$format->value}; fhirVersion={$ver->getFHIRShortVersion()}";
        }
    }

    protected function _buildContentTypeHeader(<?php echo $requestClass; ?> $request,
                                               <?php echo $formatEnum; ?> $format): string
    {
        $ver = $request->resource->_getFHIRVersion();
        if (<?php echo $httpMethodEnum; ?>::PATCH === $request->method) {
            return "Content-Type: application/{$format->value}-patch+{$format->value}; fhirVersion={$ver->getFHIRShortVersion()}";
        } else if ($ver->getFHIRVersionInteger() < <?php echo $fhirVersion; ?>::STU3_MIN_VERSION_INTEGER) {
            return "Content-Type: application/{$format->value}+fhir; fhirVersion={$ver->getFHIRShortVersion()}";
        } else {
            return "Content-Type: application/fhir+{$format->value}; fhirVersion={$ver->getFHIRShortVersion()}";
        }
    }
}
<?php return ob_get_clean();
