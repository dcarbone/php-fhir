# php-fhir
Tools for consuming data from a FHIR server with PHP

# Installation

This library requires the use of [Composer](https://getcomposer.org/)

Require entry:
```json
    "php-fhir": "0.1.*"
```

# Basic Workflow

The first step is to determine the version of the FHIR spec your implementation supports.  Once done, download
the appropriate class definition XSDs from [http://hl7.org/fhir/directory.html](http://hl7.org/fhir/directory.html).

Uncompress the XSD's and place them in a directory that is readable by PHP's runtime user.

Next comes the fun:

## Class Generation

The class generator utility included with this library is designed to parse the XSD's provided by the FHIR
group into PHP classes, complete with markup and type hinting.

There are 2 important things to note with this section:

1. Your exact implementation will probably vary, don't hesitate to ask if you need some help
2. The class generation should be run ONCE per FHIR version.  Once the classes
have been generated they should only ever be re-generated if your server switches to a new FHIR spec

### Example:

```php
require __DIR__.'/vendor/autoload.php';

$xsdPath = 'path to wherever you un-zipped the xsd files';

$generator = new \DCarbone\PHPFHIR\Generator($xsdPath);

$generator->generate();
```

Using the above code will generate class files under the included [output](./output) directory, under the namespace
` PHPFHIRGenerated `

If you wish the files to be placed under a different directory, pass the path in as the 2nd argument in the
generator constructor.

If you wish the classes to have a different base namespace, pass the desired NS name in as the 3rd argument in the
generator constructor.

## Data Querying

There are a plethora of good HTTP clients you can use to get data out of a FHIR server, so I leave that up to you.

## Response Parsing

This library provides 2 parser classes:

- [XML](./src/Parser/SimpleXMLResponseParser.php)
- [Json](./src/Parser/JsonResponseParser.php)

The one you use is determined by the response data type, but both are used in the same way.

### Example:

```php
$parser = new \DCarbone\PHPFHIR\Parser\YourTypeParser();

$data = $parser->parse($yourResponseData);
```

If you defined a custom output directory or custom base namespace you will need to pass those in as the 1st and 2nd
constructor arguments respectively.

## Testing

Currently this library is being tested against v0.0.82 and v1.0.2. using the open server available here:
http://fhir2.healthintersections.com.au/open/ .

## TODO

- Implement event or pull-based XML parsing for large responses
- Tests...

## Suggestions and help

If you have some suggestions for how this lib could be made more useful, more applicable, easier to use, etc, please
let me know.
