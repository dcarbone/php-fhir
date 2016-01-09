# php-fhir
Tools for creating FHIR classes in PHP for use by a client.

This library is a work in progress.

## Basic Usage

After installation via Composer, you will need to procure XSD's for your desired FHIR spec version.  See
[http://hl7.org/fhir/directory.html](http://hl7.org/fhir/directory.html) for a version list.

Once this directory is downloaded and un-zipped, a simple script to build classes from the above XSD's is as follows:

```php
<?php
require __DIR__.'/vendor/autoload.php';

$xsdPath = 'path to wherever you un-zipped the xsd's';

$generator = new \PHPFHIR\Generator($xsdPath);

$generator->generate();
```

The generated classes will be placed under ` php-fhir/output/ `.

## Known Things

- Implementation of value-restricted properties and objects
- Optional value validity checking

## Testing

Currently this library is being tested against v0.0.82 and v1.0.2.  For a version history of FHIR, see here:
[http://hl7.org/fhir/directory.html](http://hl7.org/fhir/directory.html)

## Suggestions and help

If you have some suggestions for how this lib could be made more useful, more applicable, easier to use, etc, please
let me know.