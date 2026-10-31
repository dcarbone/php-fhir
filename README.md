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

That package is kept in sync automatically: every GitHub release published from this repository
triggers a pipeline that regenerates all supported FHIR versions, runs a [Rector](https://github.com/rectorphp/rector)
cleanup pass (see [Contributing → Code Quality](https://github.com/dcarbone/php-fhir/wiki/Contributing#code-quality-rector-on-generated-output)),
and pushes a matching commit + release to `php-fhir-generated`. You never need to run the generator
yourself unless you want a custom namespace, a subset of versions, or non-default behavior.

---

## 📖 Documentation

Full documentation is maintained on the **[project wiki](https://github.com/dcarbone/php-fhir/wiki)**:

| Page                                                                                         | Description                                                   |
|----------------------------------------------------------------------------------------------|---------------------------------------------------------------|
| [Getting Started](https://github.com/dcarbone/php-fhir/wiki/Getting-Started)                 | Prerequisites, installation, and downloading FHIR schemas     |
| [CLI Usage](https://github.com/dcarbone/php-fhir/wiki/CLI-Usage)                             | Running the generator from the command line                   |
| [Configuration Reference](https://github.com/dcarbone/php-fhir/wiki/Configuration-Reference) | All `Config` and `VersionConfig` options                      |
| [Architecture](https://github.com/dcarbone/php-fhir/wiki/Architecture)                       | How the generator pipeline works internally                   |
| [Profiles](https://github.com/dcarbone/php-fhir/wiki/Profiles)                               | Generating classes for FHIR profiles such as US Core          |
| [Generated Code](https://github.com/dcarbone/php-fhir/wiki/Generated-Code)                   | Understanding the output: namespaces, types, client, encoding |
| [Testing](https://github.com/dcarbone/php-fhir/wiki/Testing)                                 | Running tests on the generator and on generated code          |
| [Contributing](https://github.com/dcarbone/php-fhir/wiki/Contributing)                       | Developer workflow and code-style notes                       |

---

## Support Matrix

| PHPFHIR Version | PHP Versions  | FHIR Versions                                     | Supported |
|-----------------|---------------|---------------------------------------------------|-----------|
| v4              | >= 8.1        | DSTU1, DSTU2, STU3, R4, R4B, R5                   | **Yes**   |
| v3              | >= 8.1, < 8.5 | DSTU1, DSTU2, STU3, R4, R4B, R5                   | No        |
| v2              | 5.4–7.4       | DSTU1, DSTU2, STU3, R4                            | No        |

R6 pre-release metadata is recognized, but R6 generation is not included in the default configuration or
the generated-package release workflow yet.

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

### 4. Profiles (optional)

Classes for FHIR profiles such as [US Core](https://hl7.org/fhir/us/core/) can be generated from local
implementation guide packages. Pass a `profilesConfig` to the version, configuring every package the guide depends
on as a package of its own and listing it under `dependencies`:

```php
new VersionConfig(
    name: 'R4',
    schemaPath: __DIR__ . '/fhir-schemas/R4',
    profilesConfig: [
        'packages' => [
            ['name' => 'hl7.fhir.r4.core', 'source' => __DIR__ . '/input/hl7.fhir.r4.core.tgz'],
            // ... the guide's other dependencies ...
            [
                'name' => 'hl7.fhir.us.core',
                'source' => __DIR__ . '/input/hl7.fhir.us.core.tgz',
                'dependencies' => ['hl7.fhir.r4.core', /* ... */],
            ],
        ],
    ],
),
```

US Core needs eight packages besides its own; [`bin/config.php`](bin/config.php) has the complete setup.

Each profile class extends the type it constrains, is nested under a `Profiles` namespace mirroring the `Types`
layout, and is checked by the regular validation system (cardinality, fixed/pattern values, bindings, slices). Set
the `profileResolution` unserialize option to `auto` to have parsing pick the profile class from `meta.profile`.
Profile generation is memory hungry; allow PHP at least 1 GB. See the
[Profiles](https://github.com/dcarbone/php-fhir/wiki/Profiles) wiki page.

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
