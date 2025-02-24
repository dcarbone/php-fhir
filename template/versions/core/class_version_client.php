<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$config = $version->getConfig();

$coreFiles = $config->getCoreFiles();

$clientInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_INTERFACE_CLIENT);
$serializeFormatEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_SERIALIZE_FORMAT);
$clientSortEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_SORT_DIRECTION);
$clientRequestClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_REQUEST);
$clientResponseClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_CLASSNAME_RESPONSE);
$clientErrorException = $coreFiles->getCoreFileByEntityName(PHPFHIR_EXCEPTION_CLIENT_ERROR);
$clientUnexpectedResponseCodeException = $coreFiles->getCoreFileByEntityName(PHPFHIR_EXCEPTION_CLIENT_UNEXPECTED_RESPONSE_CODE);
$httpMethodEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLIENT_ENUM_HTTP_METHOD);

$resourceParserClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_RESOURCE_PARSER);

$versionCoreFiles = $version->getVersionCoreFiles();
$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);
$versionTypeEnum = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_ENUM_VERSION_RESOURCE_TYPE);
$versionResourceTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_RESOURCE_TYPE);

$sourceMeta = $version->getSourceMetadata();

$types = $version->getDefinition()->getTypes();

$idType = $types->getTypeByName('id');
$idPrimitiveType = $types->getTypeByName('id-primitive');

$imports = $coreFile->getImports();
$imports
    ->addCoreFileImports(
        $clientInterface,
        $serializeFormatEnum,
        $clientSortEnum,
        $clientRequestClass,
        $clientResponseClass,
        $clientErrorException,
        $clientUnexpectedResponseCodeException,

        $resourceParserClass,
        $httpMethodEnum,

        $versionTypeEnum,
        $versionClass,
        $versionResourceTypeInterface,
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
<?php if (!$sourceMeta->isDSTU1()) : ?>
     * @param null|int $count
<?php endif; ?>
     * @param null|string|<?php echo $clientSortEnum->getFullyQualifiedName(true); ?> $sort May be a string value if your server supports non-standard sorting methods
     * @param null|<?php echo $serializeFormatEnum->getFullyQualifiedName(true); ?> $format
     * @param null|array $queryParams
     * @param null|bool $parseResponseHeaders
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function read(<?php echo $versionTypeEnum; ?> $resourceType,
                         <?php if (!$sourceMeta->isDSTU1()) : ?>null|<?php endif; ?>string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID<?php if (!$sourceMeta->isDSTU1()) : ?> = null<?php endif; ?>,
<?php if (!$sourceMeta->isDSTU1()) : ?>
                         null|int $count = null,
<?php endif; ?>
                         null|string|<?php echo $clientSortEnum; ?> $sort = null,
                         null|<?php echo $serializeFormatEnum; ?> $format = null,
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
            method: <?php echo $httpMethodEnum; ?>::GET,
            path: $path,
<?php if (!$sourceMeta->isDSTU1()) : ?>
            count: $count,
<?php endif; ?>
            format: $format,
            sort: $sort,
            acceptVersion: $this->_version->getFHIRVersion(),
            queryParams: $queryParams,
            parseResponseHeaders: $parseResponseHeaders,
        );
        return $this->_client->exec($req);
    }

<?php if (!$sourceMeta->isDSTU1()) : ?>
    /**
     * Create a resource.
     *
     * @see https://www.hl7.org/fhir/http.html#create
     *
     * @param <?php echo $versionResourceTypeInterface->getFullyQualifiedName(true); ?> $resource The resource to update, must have a defined ID.
     * @param null|<?php echo $serializeFormatEnum->getFullyQualifiedName(true); ?> $format
     * @param null|array $queryParams Any additional query params to send as part of this request
     * @param null|bool $parseResponseHeaders
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function create(<?php echo $versionResourceTypeInterface; ?> $resource,
                           null|<?php echo $serializeFormatEnum; ?> $format = null,
                           null|array $queryParams = null,
                           null|bool $parseResponseHeaders = null): <?php echo $clientResponseClass; ?>

    {
        $req = new <?php echo $clientRequestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::POST,
            path: "/{$resource->_getFHIRTypeName()}",
            format: $format,
            acceptVersion: $resource->_getFHIRVersion(),
            resource: $resource,
            resourceSerializeConfig: $this->_version->getConfig()->getSerializeConfig(),
            queryParams: $queryParams,
            parseResponseHeaders: $parseResponseHeaders,
        );
        return $this->_client->exec($req);
    }

    /**
     * Update or create a specific resource.
     *
     * @see https://www.hl7.org/fhir/http.html#update
     *
     * @param <?php echo $versionResourceTypeInterface->getFullyQualifiedName(true); ?> $resource The resource to update, must have a defined ID.
     * @param null|<?php echo $serializeFormatEnum->getFullyQualifiedName(true); ?> $format
     * @param null|array $queryParams Any additional query params to send as part of this request
     * @param null|bool $parseResponseHeaders
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function update(<?php echo $versionResourceTypeInterface; ?> $resource,
                           null|<?php echo $serializeFormatEnum; ?> $format = null,
                           null|array $queryParams = null,
                           null|bool $parseResponseHeaders = null): <?php echo $clientResponseClass ?>

    {
        $req = new <?php echo $clientRequestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::PUT,
            path: "/{$resource->_getFHIRTypeName()}/{$this->_mustGetResourceID($resource)}",
            format: $format,
            acceptVersion: $resource->_getFHIRVersion(),
            resource: $resource,
            resourceSerializeConfig: $this->_version->getConfig()->getSerializeConfig(),
            queryParams: $queryParams,
            parseResponseHeaders: $parseResponseHeaders,
        );
        return $this->_client->exec($req);
    }

    /**
     * Perform a partial update on a resource.
     *
     * @see https://www.hl7.org/fhir/http.html#patch
     *
     * @param <?php echo $versionResourceTypeInterface->getFullyQualifiedName(true); ?> $resource The resource to update, must have a defined ID.
     * @param null|<?php echo $serializeFormatEnum->getFullyQualifiedName(true); ?> $format
     * @param null|array $queryParams Any additional query params to send as part of this request
     * @param null|bool $parseResponseHeaders
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function patch(<?php echo $versionResourceTypeInterface; ?> $resource,
                           null|<?php echo $serializeFormatEnum; ?> $format = null,
                           null|array $queryParams = null,
                           null|bool $parseResponseHeaders = null): <?php echo $clientResponseClass; ?>

    {
        $req = new <?php echo $clientRequestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::PATCH,
            path: "/{$resource->_getFHIRTypeName()}/{$this->_mustGetResourceID($resource)}",
            format: $format,
            acceptVersion: $resource->_getFHIRVersion(),
            resource: $resource,
            resourceSerializeConfig: $this->_version->getConfig()->getSerializeConfig(),
            queryParams: $queryParams,
            parseResponseHeaders: $parseResponseHeaders,
        );
        return $this->_client->exec($req);
    }

    /**
     * Delete a resource by ID.
     *
     * @see https://www.hl7.org/fhir/http.html#delete
     *
     * @param <?php echo $versionTypeEnum->getFullyQualifiedName(true); ?> $resourceType
     * @param string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID ID of resource to delete.
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function delete(<?php echo $versionTypeEnum; ?> $resourceType,
                           string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID): <?php echo $clientResponseClass; ?>

    {
        $req = new <?php echo $clientRequestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::DELETE,
            path: "{$resourceType->value}/{$resourceID}",
        );
        return $this->_client->exec($req);
    }

    /**
     * Delete a specific resource.
     *
     * @see https://www.hl7.org/fhir/http.html#delete
     *
     * @param <?php echo $versionResourceTypeInterface->getFullyQualifiedName(true); ?> $resource Specific resource to delete.
     * @return <?php echo $clientResponseClass->getFullyQualifiedName(true); ?>

     */
    public function deleteResource(<?php echo $versionResourceTypeInterface; ?> $resource): <?php echo $clientResponseClass; ?>

    {
        $req = new <?php echo $clientRequestClass; ?>(
            method: <?php echo $httpMethodEnum; ?>::DELETE,
            path: "/{$resource->_getFHIRTypeName()}/{$this->_mustGetResourceID($resource)}",
        );
        return $this->_client->exec($req);
    }
<?php endif; 
foreach($version->getDefinition()->getTypes()->getNameSortedIterator() as $rsc) :
    if (!$rsc->hasResourceTypeParent()
        || 'Bundle' === $rsc->getFHIRName()
        || 'DomainResource' === $rsc->getFHIRName()
        || $rsc->getKind()->isResourceContainer($version)) {
        continue;
    }

    $rscNameIndent = str_repeat(' ', strlen($rsc->getFHIRName()));
    ?>

    /**
     * Read one <?php echo $rsc->getFHIRName(); ?> resource.
     *
     * @param string|<?php echo $idType->getFullyQualifiedClassName(true); ?>|<?php echo $idPrimitiveType->getFullyQualifiedClassName(true); ?> $resourceID
     * @param null|<?php echo $serializeFormatEnum->getFullyQualifiedName(true); ?> $format
     * @return <?php echo $rsc->getFullyQualifiedClassName(true); ?>

     * @throws \Exception
     */
    public function readOne<?php echo $rsc->getFHIRName(); ?>(string|<?php echo $idType->getClassName(); ?>|<?php echo $idPrimitiveType->getClassName(); ?> $resourceID,
                           <?php echo $rscNameIndent; ?> null|<?php echo $serializeFormatEnum; ?> $format = null): <?php echo $rsc->getClassName(); ?>

    {
        $rc = $this->read(resourceType: <?php echo $versionTypeEnum; ?>::<?php echo $rsc->getConstName(false); ?>,
                          resourceID: $resourceID,
                          format: $format);
        $this->_requireOK($rc);
        return match($format) {
            <?php echo $serializeFormatEnum; ?>::JSON => <?php echo $rsc->getClassName(); ?>::jsonUnserialize(
                json: $rc->resp,
                config: $this->_version->getConfig()->getUnserializeConfig(),
            ),
            <?php echo $serializeFormatEnum; ?>::XML => <?php echo $rsc->getClassName(); ?>::xmlUnserialize(
                element: $rc->resp,
                config: $this->_version->getConfig()->getUnserializeConfig(),
            ),
        };
    }
<?php endforeach; ?>

    protected function _requireOK(<?php echo $clientResponseClass; ?> $rc): void
    {
        if (isset($rc->err)) {
            throw new <?php echo $clientErrorException; ?>($rc);
        }
        if (!isset($rc->code) || self::_STATUS_OK !== $rc->code) {
            throw new <?php echo $clientUnexpectedResponseCodeException; ?>($rc, self::_STATUS_OK);
        }
    }

    protected function _parseResponse(<?php echo $versionTypeEnum; ?> $resourceType,
                                     <?php echo $clientResponseClass; ?> $rc): <?php echo $versionResourceTypeInterface; ?>

    {
        /** @var <?php echo $versionResourceTypeInterface->getFullyQualifiedName(true); ?> $class */
        $class = $this->_version->getTypeMap()::getTypeClassname($resourceType->name);
        return match ($rc->getResponseFormat()) {
            <?php echo $serializeFormatEnum; ?>::JSON => $class::jsonUnserialize(
                json: $rc->resp,
                config: $this->_version->getConfig()->getUnserializeConfig(),
            ),
            <?php echo $serializeFormatEnum; ?>::XML => $class::xmlUnserialize(
                element: $rc->resp,
                config: $this->_version->getConfig()->getUnserializeConfig(),
            ),
            null => <?php echo $resourceParserClass; ?>::parse($this->_version, $rc->resp),
        };
    }

    protected function _mustGetResourceID(<?php echo $versionResourceTypeInterface; ?> $resource): string
    {
        $id = $resource->getId()?->_getValueAsString();
        if (null === $id || '' === $id) {
            throw new \DomainException(sprintf(
                'Cannot update resource of type "%s" as its ID is undefined or empty',
                $resource->_getFHIRTypeName(),
            ));
        }
        return $id;
    }
}
<?php return ob_get_clean();