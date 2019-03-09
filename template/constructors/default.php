<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Property[] $sortedProperties */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */
/** @var string $typeClassName */

ob_start(); ?>
    /**
     * <?php echo $typeClassName; ?> Constructor
     * @param null|array $data
     */
    public function __construct($data = null)
    {
        if (null === $data || [] === $data) {
            return;
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException(sprintf(
                '<?php echo $typeClassName; ?>::_construct - $data expected to be null or array, %s seen',
                gettype($data)
            ));
        }<?php if ($parentType) : ?>

        parent::__construct($data);<?php endif; ?>

<?php foreach($sortedProperties as $property) :
    echo require PHPFHIR_TEMPLATE_CONSTRUCTORS_DIR . '/property_setter_call.php';
endforeach; ?>
    }
<?php return ob_get_clean();