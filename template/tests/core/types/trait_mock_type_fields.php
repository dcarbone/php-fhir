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
$imports = $coreFile->getImports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$elementTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE);
$primitiveTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_TYPE);
$primitiveContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_PRIMITIVE_CONTAINER_TYPE);
$elementTypeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE);

$valueXMLLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$serializeConfig = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);

$imports->addCoreFileImports(
    $typeInterface,
    $primitiveTypeInterface,
    $primitiveContainerInterface,
    $elementTypeInterface,

    $valueXMLLocationEnum,
    $xmlWriterClass,
    $serializeConfig,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

trait <?php echo $coreFile; ?>

{
    protected array $_fields = [];

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

    public function __isset(string $name): bool
    {
        return isset($this->_fields[$name]);
    }

    public function __get(string $name): array|<?php echo $typeInterface; ?>

    {
        return $this->_fields[$name]['value'];
    }

    public function current(): mixed
    {
        $def = current($this->_fields);
        return $def['value'] ?? null;
    }

    public function key(): mixed
    {
        return key($this->_fields);
    }

    public function next(): void
    {
        next($this->_fields);
    }

    public function rewind(): void
    {
        reset($this->_fields);
    }

    public function valid(): bool
    {
        return null !== key($this->_fields);
    }

    /**
     * Process any / all field definitions for the mock type, ensuring they're probably sane.
     *
     * @param array $fields
     */
    protected function _processFields(array $fields): void
    {
        // compute this once.
        $mockElement = ($this instanceof <?php echo $elementTypeInterface; ?>);

        // process field declarations, performing some basic value validation and processing.
        foreach($fields as $field => $def) {
            // lazy check for "sane" field name, probably not good enough.
            if (!preg_match('{^[a-zA-Z0-9]+}', $field)) {
                throw new \DomainException(sprintf('Field name "%s" is not valid.', $field));
            }

            // must have a class set.
            if (!isset($def['class'])) {
                throw new \LogicException(sprintf('Field "%s" definition must contain "class" key', $field));
            }

            // all fields must implement the base type interface.
            if (!is_string($def['class']) || !is_a($def['class'], <?php echo $typeInterface; ?>::class, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Type "%s" field "%s" class "%s" does not implement required interface "%s"',
                    $this->_name,
                    $field,
                    is_string($def['class']) ? $def['class'] : gettype($def['class']),
                    <?php echo $typeInterface; ?>::class,
                ));
            }

            // localize a few things to make life easier.
            $class = $def['class'];
            $value = $def['value'] ?? null;
            $collection = $def['collection'] ?? false;

            // check for field types.
            $primitive = is_a($class, <?php echo $primitiveTypeInterface; ?>::class, true);
            $element = is_a($class, <?php echo $elementTypeInterface; ?>::class, true);
            $primitiveContainer = is_a($class, <?php echo $primitiveContainerInterface; ?>::class, true);

            // element types may only have other element types or primitives as field types.
            if ($mockElement && !$element && !$primitive) {
                throw new \InvalidArgumentException(sprintf(
                    'Mock element type "%s" may only have other elements or primitives field types but field "%s" has class "%s"',
                    $this->_name,
                    $field,
                    $class,
                ));
            }

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
                        $fields[$field]['value'][$i] = new $class(value: $v);
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

            $fields[$field]['value'] = new $class(value: $value);
        }

        // finally set fields
        $this->_fields = $fields;
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

    protected function _xmlSerialize(<?php echo $xmlWriterClass; ?> $xw,
                                     <?php echo $serializeConfig; ?> $config,
                                     null|<?php echo $valueXMLLocationEnum; ?> $valueLocation = null): void
    {
        $mockPrimitiveContainer = ($this instanceof <?php echo $primitiveContainerInterface; ?>);

        // if this is a mock primitve container, we need to handle locations a lil' different
        if ($mockPrimitiveContainer) {
            $valueLocation = $valueLocation ?? $this->_valueXMLLocations['value'];
        }

        // handle attribute serialization
        foreach($this->_fields as $field => $def) {
            $class = $def['class'];
            $value = $def['value'] ?? null;
            $primitive = is_a($class, <?php echo $primitiveTypeInterface; ?>::class, true);
            $primitiveContainer = is_a($class, <?php echo $primitiveContainerInterface; ?>::class, true);

            if (null === $value || (!$primitive && !$primitiveContainer)) {
                continue;
            }

            // primitive containers may only have their value field as an attribute
            if ($mockPrimitiveContainer) {
                if ('value' !== $field || <?php echo $valueXMLLocationEnum; ?>::CONTAINER_ATTRIBUTE !== $valueLocation) {
                    continue;
                }
            } else {
                if ($primitiveContainer && <?php echo  $valueXMLLocationEnum; ?>::PARENT_ATTRIBUTE === $this->_valueXMLLocations[$field]) {
                    continue;
                }
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
            } else if ($mockPrimitiveContainer && 'value' === $field) {
                if (<?php echo $valueXMLLocationEnum; ?>::CONTAINER_VALUE === $valueLocation) {
                    $xw->text((string)$this->getValue());
                } else if (<?php echo $valueXMLLocationEnum; ?>::ELEMENT_ATTRIBUTE === $valueLocation) {
                    $xw->startElement('value');
                    $xw->writeAttribute('value', (string)$this->getValue());
                    $xw->endElement();
                } else if (<?php echo $valueXMLLocationEnum; ?>::ELEMENT_VALUE === $valueLocation) {
                    $xw->writeElement('value', (string)$this->getValue());
                }
            } else if ($primitiveContainer) {
                if ($value->_nonValueFieldDefined() || <?php echo  $valueXMLLocationEnum; ?>::PARENT_ATTRIBUTE !== $this->_valueXMLLocations[$field]) {
                    $xw->startElement($field);
                    $value->xmlSerialize($xw, $config, $this->_valueXMLLocations[$field]);
                    $xw->endElement();
                }
            } else {
                $xw->startElement($field);
                $value->xmlSerialize($xw, $config);
                $xw->endElement();
            }
        }
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
}
<?php return ob_get_clean();
