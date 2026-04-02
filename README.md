# php-fhir

**Tools for generating PHP classes from the [HL7 FHIR](https://hl7.org/fhir/) specification.**

php-fhir reads the official FHIR XSD schema files and produces a fully-typed PHP library — complete
with models for every FHIR type, JSON & XML (de)serialization, validation, and a ready-to-use FHIR
REST client.

---

## 🚀 Just Want the Generated Code?

If you don't need to customise the generation process and just want usable FHIR models in your PHP
project, grab the pre-built package:

**➡️ [dcarbone/php-fhir-generated](https://github.com/dcarbone/php-fhir-generated)**

---

## 📖 Documentation

Full documentation is maintained on the **[project wiki](https://github.com/dcarbone/php-fhir/wiki)**:

| Page                                                                                         | Description                                                   |
|----------------------------------------------------------------------------------------------|---------------------------------------------------------------|
| [Getting Started](https://github.com/dcarbone/php-fhir/wiki/Getting-Started)                 | Prerequisites, installation, and downloading FHIR schemas     |
| [CLI Usage](https://github.com/dcarbone/php-fhir/wiki/CLI-Usage)                             | Running the generator from the command line                   |
| [Configuration Reference](https://github.com/dcarbone/php-fhir/wiki/Configuration-Reference) | All `Config` and `VersionConfig` options                      |
| [Architecture](https://github.com/dcarbone/php-fhir/wiki/Architecture)                       | How the generator pipeline works internally                   |
| [Generated Code](https://github.com/dcarbone/php-fhir/wiki/Generated-Code)                   | Understanding the output: namespaces, types, client, encoding |
| [Testing](https://github.com/dcarbone/php-fhir/wiki/Testing)                                 | Running tests on the generator and on generated code          |
| [Contributing](https://github.com/dcarbone/php-fhir/wiki/Contributing)                       | Developer workflow and code-style notes                       |

---

## Support Matrix

| PHPFHIR Version | PHP Versions | FHIR Versions                   | Supported |
|-----------------|--------------|---------------------------------|-----------|
| v4              | 8.1+         | DSTU1, DSTU2, STU3, R4, R4B, R5 | **Yes**   |
| v3              | 8.1–8.4      | DSTU1, DSTU2, STU3, R4, R4B, R5 | No        |
| v2              | 5.4–7.4      | DSTU1, DSTU2, STU3, R4          | No        |

---

## Requirements

- **PHP** 8.1 or newer
- **Composer** — [getcomposer.org](https://getcomposer.org/)
- **PHP extensions:** `ctype`, `curl`, `dom`, `json`, `libxml`, `simplexml`, `xmlreader`, `xmlwriter`
- **FHIR schemas:** extracted XSD bundles for each version you wish to generate
  (see the [Getting Started](https://github.com/dcarbone/php-fhir/wiki/Getting-Started) wiki page for download links)

---

## Quick Start

### 1. Install

```bash
# Standalone
git clone https://github.com/dcarbone/php-fhir.git
cd php-fhir && composer install

# — or as a Composer dependency in your own project —
composer require dcarbone/php-fhir
```

### 2. Download FHIR Schemas

```bash
mkdir -p fhir-schemas/R4
curl -Lo fhir-schemas/R4.zip https://hl7.org/fhir/R4/fhir-codegen-xsd.zip
unzip fhir-schemas/R4.zip -d fhir-schemas/R4
```

### 3. Generate

**Option A — PHP script:**

```php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use DCarbone\PHPFHIR\Builder;
use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Config\VersionConfig;

$config = new Config(
    libraryPath: __DIR__ . '/output/src',
    versions: [
        new VersionConfig(name: 'R4', schemaPath: __DIR__ . '/fhir-schemas/R4'),
    ],
    testsPath: __DIR__ . '/output/tests',   // optional
);

$builder = new Builder($config);
$builder->render();
```

**Option B — CLI (standalone checkout only):**

```bash
./bin/generate.sh --versions R4 --logLevel info
```

See the [CLI Usage](https://github.com/dcarbone/php-fhir/wiki/CLI-Usage) and
[Configuration Reference](https://github.com/dcarbone/php-fhir/wiki/Configuration-Reference)
wiki pages for full details.

---

## Links

|                        |                                                  |
|------------------------|--------------------------------------------------|
| **Source**             | <https://github.com/dcarbone/php-fhir>           |
| **Pre-generated code** | <https://github.com/dcarbone/php-fhir-generated> |
| **Wiki / Docs**        | <https://github.com/dcarbone/php-fhir/wiki>      |
| **HL7 FHIR**           | <https://hl7.org/fhir/>                          |

---

## License

[Apache License 2.0](LICENSE)
