<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$config = $version->getConfig();

$coreFiles = $config->getCoreFiles();
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);

$clientInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_INTERFACE_CLIENT);
$clientResponseFormatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_SERIALIZE_FORMAT);
$clientSortEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_SORT_DIRECTION);
$clientRequestClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_REQUEST);
$clientResponseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);
$clientErrorException = $coreFiles->getCoreFileByEntityName(PHPFHIR_EXCEPTION_CLIENT_ERROR);
$clientUnexpectedResponseCodeException = $coreFiles->getCoreFileByEntityName(PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE);
$httpMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);

$resourceParserClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_RESOURCE_PARSER);

$versionCoreFiles = $version->getCoreFiles();
$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
$versionTypeEnum = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_ENUM_VERSION_TYPES);

$sourceMeta = $version->getSourceMetadata();

$types = $version->getDefinition()->getTypes();

$idType = $types->getTypeByName('id');
$idPrimitiveType = $types->getTypeByName('id-primitive');

$imports = $coreFile->getImports();
$imports
    ->addCoreFileImports(
        $clientInterface,
        $clientResponseFormatEnum,
        $clientSortEnum,
        $clientRequestClass,
        $clientResponseClass,
        $clientErrorException,
        $clientUnexpectedResponseCodeException,

        $resourceParserClass,
        $httpMethodEnum,

        $versionTypeEnum,
        $versionClass,

        $resourceTypeInterface,
    )
    ->addVersionTypeImports(
        $idType,
        $idPrimitiveType,
    );

foreach($types->getIterator() as $type) {
    if ($type->hasResourceTypeParent()
        && 'Bundle' !== $type->getFHIRName()
        && 'DomainResource' !== $type->getFHIRName()
        && !$type->isAbstract()
        && !$type->getKind()->isResourceContainer($version)) {
        $imports->addVersionTypeImports($type);
    }
}

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $version->getFullyQualifiedName(false); ?>;

<?php echo CopyrightUtils::compileFullCopyrightComment($version->getConfig(), $version->getSourceMetadata()); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?>

{
    private const _STATUS_OK = 200;

    protected <?php echo $clientInterface; ?> $_client;
    protected <?php echo $versionClass; ?> $_version;

    /**
     * <?php echo $coreFile; ?> Constructor
     *
     * @param <?php echo $clientInterface->getFullyQualifiedName(true); ?> $client
     * @param <?php echo $versionClass->getFullyQualifiedName(true); ?> $version
     */
    public function __construct(<?php echo $clientInterface; ?> $client, <?php echo $versionClass; ?> $version)
    {
        $this->_client = $client;
        $this->_version = $version;
    }

    /**
     * Queries for one <?php if ($sourceMeta->isDSTU1()) : ?>resource<?php else : ?>or more resources<?php endif; ?> of a given type, returning the raw response fromm the server.
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param <?php echo $versionTypeEnum->getFullyQualifiedName(true); ?> $resourceType
     * @param <?php if (!$sourceMeta->isDSTU1()) : ?>null|<?php endif; ?>string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|int $count
     * @param null|string|<?php echo $clientSortEnum->getFullyQualifiedName(true); ?> $sort May be a string value if your server supports non-standard sorting methods
     * @param null|<?php echo $clientResponseFormatEnum->getFullyQualifiedName(true); ?> $format
     * @param null|array $queryParams
     * @param null|bool $parseResponseHeaders
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     * @throws \Exception
     */
    public function readRaw(<?php echo $versionTypeEnum; ?> $resourceType,
                            <?php if (!$sourceMeta->isDSTU1()) : ?>null|<?php endif; ?>string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID<?php if (!$sourceMeta->isDSTU1()) : ?> = null<?php endif; ?>,
                            null|int $count = null,
                            null|string|<?php echo $clientSortEnum; ?> $sort = null,
                            null|<?php echo $clientResponseFormatEnum; ?> $format = null,
                            null|array $queryParams = null,
                            null|bool $parseResponseHeaders = null): <?php echo $clientResponseClass; ?>

    {

        $path = "/{$resourceType->value}";
<?php
// DSTU1 uses an ATOM feed for its "bundle" type, and I don't want to bother with that so if you use DSTU1, you only
// get single resource querying.
if ($sourceMeta->isDSTU1()) : ?>
        $resourceID = trim((string)$resourceID);
        if ('' === $resourceID) {
            throw new \InvalidArgumentException('Resource ID must be null or valued.');
        }
        $path .= "/{$resourceID}";
<?php else : ?>
        if (null !== $resourceID) {
            $resourceID = trim((string)$resourceID);
            if ('' === $resourceID) {
                throw new \InvalidArgumentException('Resource ID must be null or valued.');
            }
            $path .= "/{$resourceID}";
        }
<?php endif; ?>
        $req = new <?php echo $clientRequestClass; ?>(
            acceptVersion: $this->_version->getFHIRVersion(),
            method: <?php echo $httpMethodEnum; ?>::GET,
            path: $path,
        );
        if (null !== $count) {
            $req->count = $count;
        }
        if (null !== $sort) {
            $req->sort = is_string($sort) ? $sort : $sort->value;
        }
        if (null !== $format) {
            $req->format = $format;
        }
        if (null !== $parseResponseHeaders) {
            $req->parseResponseHeaders = $parseResponseHeaders;
        }
        if (null !== $queryParams) {
            $req->queryParams = $queryParams;
        }
        return $this->_client->exec($req);
    }

<?php if (!$sourceMeta->isDSTU1()) : ?>
    /**
     * Queries for one or more resources of a given type, returning the unserialized response from the server.
     *
     * @see https://www.hl7.org/fhir/http.html#read
     *
     * @param <?php echo $versionTypeEnum->getFullyQualifiedName(true); ?> $resourceType
     * @param null|string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|int $count
     * @param null|string|<?php echo $clientSortEnum->getFullyQualifiedName(true); ?> $sort May be a string value if your server supports non-standard sorting methods
     * @param null|<?php echo $clientResponseFormatEnum->getFullyQualifiedName(true); ?> $format
     * @param null|array $queryParams
     * @param null|bool $parseResponseHeaders
     * @return null|<?php echo $resourceTypeInterface->getFullyQualifiedName(true); ?>

     * @throws \Exception
     */
    public function read(<?php echo $versionTypeEnum; ?> $resourceType,
                         null|string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID = null,
                         null|int $count = null,
                         null|string|<?php echo $clientSortEnum; ?> $sort = null,
                         null|<?php echo $clientResponseFormatEnum; ?> $format = null,
                         null|array $queryParams = null,
                         null|bool $parseResponseHeaders = null): null|<?php echo $resourceTypeInterface; ?>

    {
        $rc = $this->readRaw($resourceType, $resourceID, $count, $sort, $format, $queryParams, $parseResponseHeaders);
        $this->_requireOK($rc);
        return <?php echo $resourceParserClass; ?>::parse($this->_version, $rc->resp);
    }

    public function update(<?php echo $versionTypeEnum; ?> $resourceType,
                           null|string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID = null,
                           null|int $count = null,
                           null|string|<?php echo $clientSortEnum; ?> $sort = null,
                           null|<?php echo $clientResponseFormatEnum; ?> $format = null,
                           null|array $queryParams = null,
                           null|bool $parseResponseHeaders = null): null|<?php echo $resourceTypeInterface; ?>

    {
        $rc = $this->readRaw($resourceType, $resourceID, $count, $sort, $format, $queryParams, $parseResponseHeaders);
        $this->_requireOK($rc);
        return <?php echo PHPFHIR_ENCODING_CLASSNAME_RESOURCE_PARSER; ?>::parse($this->_version, $rc->resp);
    }

<?php endif; ?>
    /**
     * @param <?php echo $clientResponseClass->getFullyQualifiedName(true); ?> $rc
     * @throws <?php echo $clientErrorException->getFullyQualifiedName(true); ?>

     * @throws <?php echo $clientUnexpectedResponseCodeException->getFullyQualifiedName(true); ?>

     */
    protected function _requireOK(<?php echo $clientResponseClass; ?> $rc): void
    {
        if (isset($rc->err)) {
            throw new <?php echo PHPFHIR_EXCEPTION_CLIENT_ERROR; ?>($rc);
        }
        if (!isset($rc->code) || self::_STATUS_OK !== $rc->code) {
            throw new <?php echo PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE; ?>($rc, self::_STATUS_OK);
        }
    }
<?php foreach($version->getDefinition()->getTypes()->getNameSortedIterator() as $rsc) :
    if (!$rsc->hasResourceTypeParent()
        || 'Bundle' === $rsc->getFHIRName()
        || 'DomainResource' === $rsc->getFHIRName()
        || $rsc->isAbstract()
        || $rsc->getKind()->isResourceContainer($version)) {
        continue;
    }

    $rscName = $rsc->getFHIRName();
    $rscNameLen = strlen($rscName);
    $rscNameIndent = str_repeat(' ', $rscNameLen);
    ?>

    /**
     * Read one <?php echo $rsc->getFHIRName(); ?> resource.
     *
     * @param string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|<?php echo $clientResponseFormatEnum->getFullyQualifiedName(true); ?> $format
     * @return <?php echo $rsc->getFullyQualifiedClassName(true); ?>

     * @throws \Exception
     */
    public function readOne<?php echo $rsc->getFHIRName(); ?>(string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID,
                           <?php echo $rscNameIndent; ?> null|<?php echo PHPFHIR_CLIENT_ENUM_SERIALIZE_FORMAT; ?> $format = null): <?php echo $rsc->getClassName(); ?>

    {
        $rc = $this->readRaw(resourceType: <?php echo $versionTypeEnum; ?>::<?php echo $rsc->getConstName(false); ?>,
                             resourceID: $resourceID,
                             format: $format);
        $this->_requireOK($rc);
        return match($format) {
            <?php echo $clientResponseFormatEnum; ?>::JSON => <?php echo $rsc->getClassName(); ?>::jsonUnserialize(
                json: $rc->resp,
                config: $this->_version->getConfig()->getUnserializeConfig(),
            ),
            <?php echo $clientResponseFormatEnum; ?>::XML => <?php echo $rsc->getClassName(); ?>::xmlUnserialize(
                element: $rc->resp,
                config: $this->_version->getConfig()->getUnserializeConfig(),
            ),
            default => <?php echo $resourceParserClass; ?>::parse($this->_version, $rc->resp),
        };
    }
<?php endforeach; ?>

}
<?php return ob_get_clean();