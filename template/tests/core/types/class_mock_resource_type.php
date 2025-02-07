<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$primitiveContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE);
$resourceTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
$commentContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_COMMENT_CONTAINER);
$commentContainerTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_TRAIT_COMMENT_CONTAINER);
$sourceXMLNSTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_TRAIT_SOURCE_XMLNS);

$typeValidationTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_TRAIT_TYPE_VALIDATIONS);

$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);
$jsonSerializableOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_JSON_SERIALIZATION_OPTIONS);
$xmlSerializationOptionsTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_TRAIT_XML_SERIALIZATION_OPTIONS);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$unserializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$serializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $primitiveTypeInterface,
    $primitiveContainerInterface,
    $resourceTypeInterface,
    $commentContainerInterface,
    $commentContainerTrait,
    $sourceXMLNSTrait,

    $typeValidationTrait,

    $valueXMLLocationEnum,
    $jsonSerializableOptionsTrait,
    $xmlSerializationOptionsTrait,
    $xmlWriterClass,
    $unserializeConfig,
    $serializeConfig,
);

ob_start();
echo "<?php\n\n";?>
namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

class <?php echo $coreFile; ?> implements <?php echo $resourceTypeInterface; ?>, <?php echo $commentContainerInterface; ?>

{
    use <?php echo $typeValidationTrait; ?>,
        <?php echo $jsonSerializableOptionsTrait; ?>,
        <?php echo $xmlSerializationOptionsTrait; ?>,
        <?php echo $commentContainerTrait; ?>,
        <?php echo $sourceXMLNSTrait; ?>;

    private const _FHIR_VALIDATION_RULES = [];

    protected string $_name;
    protected array $_fields = [];

    private array $_valueXMLLocations = [];

    public function __construct(string $name,
                                array $fields = [],
                                array $validationRuleMap = [],
                                array $fhirComments = [])
    {
        $this->_name = $name;
        $this->_fields = $fields;
        $this->_setFHIRComments($fhirComments);
        foreach($validationRuleMap as $field => $rules) {
            $this->_setFieldValidationRules($field, $validationRuleMap);
        }

        // process field declarations, performing some basic value validation and processing.
        foreach($this->_fields as $field => $def) {
            if (!isset($def['class'])) {
                throw new \LogicException(sprintf('Field "%s" definition must contain "class" key', $field));
            }

            $class = $def['class'];
            $value = $def['value'] ?? null;
            $collection = $def['collection'] ?? false;

            $primitive = is_a($class, <?php echo $primitiveTypeInterface; ?>::class, true);
            $primitiveContainer = is_a($class, <?php echo $primitiveContainerInterface; ?>::class, true);

            // if value is unset / null, move on to next field
            if (null === $value) {
                continue;
            }

            // start collection field processing
            if ($collection) {
                // if you wish to set an initial value for a collection field, you must provide an array of values.
                if (!is_array($value)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Must leave mock type "%s" collection field "%s" value unset or provide an array of values',
                        $this->_name,
                        $class
                    ));
                }
                foreach($value as $i => $v) {
                    // if we have the expected field class type, move on.
                    if (is_a($v, $class, false)) {
                        continue;
                    }
                    // nulls and anything other than scalar values are prohibited at this point.
                    if (!is_scalar($v)) {
                        throw new \InvalidArgumentException(sprintf(
                            'Unexpected value of php type "%s" provided at offset %d of mock type "%s" collection field "%s" value.',
                            gettype($v),
                            $i,
                            $this->_name,
                            $field,
                        ));
                    }
                    // if this is a primitive or primitive container, set the initial value to be the type instance.
                    if ($primitive || $primitiveContainer) {
                        $this->_fields[$field]['value'][$i] = new $class(value: $v);
                        continue;
                    }
                    // all other types must be instances
                    throw new \InvalidArgumentException(sprintf(
                        'Must provide fully initialized instance of type "%s" to all values for mock type "%s" collection field "%s"',
                        $class,
                        $this->_name,
                        $field,
                    ));
                }

                // end collection field processing
                continue;
            }

            // ensure the appropriate XML locations are set.
            if ($primitive) {
                $this->_valueXMLLocations[$field] = <?php echo $valueXMLLocationEnum; ?>::PARENT_ATTRIBUTE;
            } else if ($primitiveContainer) {
                $this->_valueXMLLocations[$field] = <?php echo $valueXMLLocationEnum; ?>::CONTAINER_ATTRIBUTE;
            }

            // if not set or already the correct instance, move on.
            if ($value instanceof $class) {
                continue;
            }

            // non-primitives _must_ have an instance of the appropriate type set as their initial value, if one is set.
            if (!$primitive && !$primitiveContainer) {
                throw new \InvalidArgumentException(sprintf(
                    'Must provide instance of "%s" to mock type "%s" field "%s" value.',
                    $class,
                    $this->_name,
                    $field,
                ));
            }

            // this point, the value must be a scalar.
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException(sprintf(
                    'Unexpected value of php type "%s" provided to mock type "%s" field "%s" value.',
                    gettype($value),
                    $this->_name,
                    $field,
                ));
            }

            $this->_fields[$field]['value'] = new $class(value: $value);
        }
    }

    public function _getFHIRTypeName(): string
    {
        return $this->_name;
    }

    protected function _doGet(string $field, array $fieldDef, array $args): null|array|<?php echo $typeInterface; ?>

    {
        if ([] !== $args) {
            throw new \BadMethodCallException(sprintf('Method "get%s" has no parameters, but %d were provided', ucfirst($field), count($args)));
        }
        $collection = $fieldDef['collection'] ?? false;
        return $this->_fields[$field]['value'] ?? ($collection ? [] : null);
    }

    protected function _doSet(string $field, array $fieldDef, array $args): self
    {
        $class = $fieldDef['class'];
        $collection = $fieldDef['collection'] ?? false;
        $primitive = is_a($class, <?php echo $primitiveTypeInterface; ?>::class, true);

        // non-collection setters accept exactly 1 argument
        if (!$collection && 1 !== count($args)) {
            throw new \BadMethodCallException(sprintf('Method "set%s" must have exactly one argument of type %s', ucfirst($field), $class));
        }

        // if "empty" input, unset value
        if (([] === $args && $collection) || null === $args[0]) {
            unset($this->_fields[$field]['value']);
            return $this;
        }

        if ($collection) {
            $this->_fields[$field]['value'] = [];
            foreach($args as $v) {
                if ($primitive && (is_scalar($v) || $v instanceof \DateTime)) {
                    $this->_fields[$field]['value'][] = new $class($v);
                } else if (!is_a($v, $class, false)) {
                    throw new \InvalidArgumentException(sprintf('Field "%s" values must be of type "%s", saw "%s"', $field, $class, gettype($v)));
                } else {
                    $this->_fields[$field]['value'][] = $v;
                }
            }
            return $this;
        }

        if ($primitive && (is_scalar($args[0]) || $args[0] instanceof \DateTime)) {
            $this->_fields[$field] = new $class($args[0]);
        } else if (!is_a($args[0], $class, false)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" value must be of type "%s", saw "%s"', $field, $class, gettype($args[0])));
        } else {
            $this->_fields[$field]['value'] = $args[0];
        }

        return $this;
    }

    protected function _doAdd(string $field, array $fieldDef, array $args): self
    {
        $class = $fieldDef['class'];
        $collection = $fieldDef['collection'] ?? false;
        $primitive = is_a($class, <?php echo $primitiveTypeInterface; ?>::class, true);

        if (!$collection) {
            throw new \BadMethodCallException(sprintf('Method "add%s" not defined', ucfirst($field)));
        }

        // collection add methods have exactly 1 parameter.
        if (1 !== count($args)) {
            throw new \InvalidArgumentException(sprintf('Method "add%s" requires exactly 1 parameter, but %d were provided.', ucfirst($field), count($args)));
        }

        if ($primitive && (is_scalar($args[0]) || $args[0] instanceof \DateTime)) {
            if (!isset($this->_fields[$field]['value'])) {
                $this->_fields[$field]['value'] = [];
            }
            $this->_fields[$field]['value'][] = new $class($args[0]);
            return $this;
        }

        if (!is_a($args[0], $class, false)) {
            throw new \InvalidArgumentException(sprintf('Field "%s" value must be of type "%s", saw "%s"', $field, $class, gettype($args[0])));
        }

        if (!isset($this->_fields[$field]['value'])) {
            $this->_fields[$field]['value'] = [];
        }
        $this->_fields[$field]['value'][] = $args[0];

        return $this;
    }

    public function __call(string $name, array $args): null|self|iterable|<?php echo $typeInterface; ?>

    {
        $get = str_starts_with($name, 'get');
        $set = str_starts_with($name, 'set');
        $add = str_starts_with($name, 'add');

        if (!$get && !$set && !$add) {
            throw new \BadMethodCallException(sprintf('Method "%s" not defined', $name));
        }

        $field = lcfirst(substr($name, 3));
        if (!isset($this->_fields[$field])) {
            throw new \BadMethodCallException(sprintf('No field "%s" defined', $field));
        }

        return match(true) {
            $get => $this->_doGet($field, $this->_fields[$field], $args),
            $set => $this->_doSet($field, $this->_fields[$field], $args),
            $add => $this->_doAdd($field, $this->_fields[$field], $args),
        };
    }

    public static function xmlUnserialize(\SimpleXMLElement|string $element,
                                          null|<?php echo $unserializeConfig; ?> $config = null,
                                          null|<?php echo $resourceTypeInterface; ?> $type = null): <?php echo $resourceTypeInterface; ?>

    {
        throw new \BadMethodCallException('gotta do this');
    }

    public function xmlSerialize(null|<?php echo $xmlWriterClass; ?> $xw = null, null|<?php echo $serializeConfig; ?> $config = null): <?php echo $xmlWriterClass; ?>

    {
        if (null === $config) {
            $config = new <?php echo $serializeConfig; ?>();
        }
        if (null === $xw) {
            $xw = new XMLWriter($config);
        }
        if (!$xw->isOpen()) {
            $xw->openMemory();
        }
        if (!$xw->isDocStarted()) {
            $docStarted = true;
            $xw->startDocument();
        }
        if (!$xw->isRootOpen()) {
            $rootOpened = true;
            $xw->openRootNode($this->_name, $this->_getSourceXMLNS());
        }

        // define primitives as attributes
        foreach($this->_fields as $field => $def) {
            $class = $def['class'];
            $value = $def['value'] ?? null;
            $primitive = is_a($class, <?php echo $primitiveTypeInterface; ?>::class, true);

            if (!$primitive || null === $value) {
                continue;
            }

            $xw->writeAttribute($field, $value->_getValueAsString());
        }

        // define others as elements
        foreach($this->_fields as $field => $def) {
            $class = $def['class'];
            $value = $def['value'] ?? null;
            $collection = $def['collection'] ?? false;
            $primitiveContainer = is_a($class, <?php echo $primitiveContainerInterface; ?>::class, true);

            if (null === $value) {
                continue;
            }

            if ($collection) {
                foreach ($value as $v) {
                    $xw->startElement($field);
                    if ($primitiveContainer) {
                        $v->xmlSerialize($xw, $config, $this->_valueXMLLocations[$field]);
                    } else {
                        $v->xmlSerialize($xw, $config);
                    }
                    $xw->endElement();
                }
            } else if ($primitiveContainer) {
                $xw->startElement($field);
                $value->xmlSerialize($xw, $config, $this->_valueXMLLocations[$field]);
                $xw->endElement();
            } else {
                $xw->startElement($field);
                $value->xmlSerialize($xw, $config);
                $xw->endElement();
            }
        }

        if ($rootOpened ?? false) {
            $xw->endElement();
        }
        if ($docStarted ?? false) {
            $xw->endDocument();
        }
        return $xw;
    }

    public static function jsonUnserialize(string|\stdClass $json, null|<?php echo $unserializeConfig; ?> $config = null, null|<?php echo $resourceTypeInterface; ?> $type = null): <?php echo $resourceTypeInterface; ?>

    {
        throw new \BadMethodCallException('gotta do this');
    }

    public function jsonSerialize(): \stdClass
    {
        $out = new \stdClass();
        foreach($this->_fields as $field => $def) {
            $class = $def['class'];
            $value = $def['value'] ?? null;
            $primitiveContainer = is_a($class, <?php echo $primitiveContainerInterface; ?>::class, true);

            if (null === $value) {
                continue;
            }

            if (!$primitiveContainer) {
                $out->{$field} = $value;
            } else {
                $out->{$field}->getValue();
            }
        }

        return $out;
    }

    public function __toString(): string
    {
        return $this->_name;
    }
}
<?php return ob_get_clean();
