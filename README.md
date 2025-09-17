# Matecat Subfiltering

[![Build Status](https://app.travis-ci.com/matecat/subfiltering.svg?token=qBazxkHwP18h3EWnHjjF&branch=master)](https://app.travis-ci.com/matecat/subfiltering)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/matecat/subfiltering/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/matecat/subfiltering/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/matecat/subfiltering/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/matecat/subfiltering/?branch=master)

Subfiltering is a component used by Matecat and MyMemory for converting strings between the database, external services, and the UI layers. It provides a pipeline of filters to safely transform content across these layers while preserving XLIFF tags, HTML placeholders, and special entities.

## What it does

- Normalizes and preserves XLIFF tags across transformations.
- Encodes/decodes special characters and placeholders for safe round-trips.
- Converts between three processing layers:
    - Layer 0: Database storage format
    - Layer 1: External services (e.g., MT/TM) format
    - Layer 2: Matecat UI format
- Supports XLIFF 2.x dataRef replacement, aligning inline codes from `<originalData>` with inline tags in segments.

## Installation

Install via Composer:

```shell
bash composer require matecat/subfiltering
```

Requirements:

- PHP 7.4+
- PHPUnit 9.x for running tests (dev)

## Filters

Two concrete filters are provided (both implement `AbstractFilter`):

- `Matecat\SubFiltering\MateCatFilter`
- `Matecat\SubFiltering\MyMemoryFilter`

Create instances using the static `getInstance` factory:

```php
<?php

use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Contracts\FeatureSetInterface;
use Matecat\SubFiltering\Mocks\FeatureSet; // Example implementation lives under tests/ (use your own in production)

$featureSet = new FeatureSet(); // must implement FeatureSetInterface

// Optional parameters:
// - $source (string): source language (e.g., 'en-US')
// - $target (string): target language (e.g., 'it-IT')
// - $dataRefMap (array): map for XLIFF 2 dataRef replacement (see section below)
$filter = MateCatFilter::getInstance($featureSet, 'it-IT', 'en-US', []);
```

The first argument MUST be a concrete implementation of `Matecat\SubFiltering\Contracts\FeatureSetInterface`.

## DataRef replacement (XLIFF 2)

XLIFF 2.0/2.1 allows binding inline tags to `<originalData>` via:

- `<ph>`, `<sc>`, `<ec>` using `dataRef`
- `<pc>` using `dataRefStart` and `dataRefEnd`

This library can automatically introduce an `equiv-text` attribute (base64-encoded original value) based on a provided dataRef map, and convert `<pc>` pairs to Matecat-compatible `<ph>` placeholders for UI consumption. On the way back, it restores the original XLIFF structure.

- Full documentation and examples: docs/dataRef.md

How to provide the map:

- Build an associative array where keys are data ids from `<originalData><data id="...">value</data></originalData>`.
- Pass that array as the fourth parameter when instantiating the filter.

Example:

```php
<?php

use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Mocks\FeatureSet;

$dataRefMap = [
    'source1' => '${AMOUNT}',
    'source2' => '${RIDER}',
];

$filter = MateCatFilter::getInstance(new FeatureSet(), 'en-US', 'it-IT', $dataRefMap);

// When converting to Layer 2 (UI), the filter will:
// - add equiv-text to <ph>/<sc>/<ec> using the map
// - convert <pc> ranges to UI placeholders with originalData captured
// When converting back to Layer 1/0, it restores the original XLIFF tags.
```

Note:

- If a dataRef key exists but its value is null or empty, it is treated as the literal string `NULL`.
- If the dataRef map is empty, the component still preserves inline codes by encoding original tags as Matecat placeholders to keep them safe in the UI.

See [docs/dataRef.md](https://github.com/matecat/subfiltering/blob/master/docs/dataRef.md) for concrete before/after string examples and behavior details.

## Basic usage

Once you have a filter instance, use the methods below to convert between layers.

`MateCatFilter` methods:

- `fromLayer0ToLayer2`
- `fromLayer1ToLayer2`
- `fromLayer2ToLayer1`
- `fromLayer2ToLayer0`
- `fromLayer0ToLayer1`
- `fromLayer1ToLayer0`
- `fromRawXliffToLayer0`
- `fromLayer0ToRawXliff`

`MyMemoryFilter` methods:

- `fromLayer0ToLayer1`
- `fromLayer1ToLayer0`

Where:

- Layer 0 = Database
- Layer 1 = External services (MT/TM)
- Layer 2 = Matecat UI

### Example: DB to UI and back (with dataRef map)

```php
<?php

use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Mocks\FeatureSet;

$featureSet = new FeatureSet();

$dataRefMap = [
    'd1' => '_',
    'd2' => '**',
];

$filter = MateCatFilter::getInstance($featureSet, 'en-US', 'it-IT', $dataRefMap);

// Example Layer 0 content holding XLIFF inline codes
$layer0 = "Hi %s .";

// 1) Layer 0 -> Layer 2 (UI)
$ui = $filter->fromLayer0ToLayer2($layer0);
// 'Hi <ph id="mtc_1" ctype="x-sprintf" equiv-text="base64:JXM="/> .'

// 2) User edits happen in UI ...

// 3) Layer 2 -> Layer 0 (restore original XLIFF structure)
$backToDb = $filter->fromLayer2ToLayer0($ui);
````

### Example: External service roundtrip

```php
<?php

use Matecat\SubFiltering\MateCatFilter;use Matecat\SubFiltering\Mocks\FeatureSet;

$filter = MateCatFilter::getInstance(new FeatureSet(), 'en-US', 'de-DE', []);

$layer0 = 'Text with <ph id="1" equiv-text="&lt;br/&gt;"/> and placeholders.';

// Prepare for MT/TM
$layer1 = $filter->fromLayer0ToLayer1($layer0);
// 'Text with <ph id="mtc_1" ctype="x-original_ph" x-orig="PHBoIGlkPSIxIiBlcXVpdi10ZXh0PSImbHQ7YnIvJmd0OyIvPg==" equiv-text="base64:Jmx0O2JyLyZndDs="/> and placeholders.'

// ... send $layer1 to MT/TM and get $translatedLayer1 back ...

// Restore for DB
$layer0Restored = $filter->fromLayer1ToLayer0($layer1);

```

### Injecting custom handlers into the pipeline

Goal Show how to inject only a subset of supported injectable handlers into the transformation pipeline so they run alongside the built-in handlers.
Key points

- Handlers are classes that extend the base handler and implement a transform method.
- You do not manually construct handlers; the pipeline instantiates them and injects the Pipeline instance via setPipeline.
- You inject handlers by passing an array of class names to the filter factory method. Unknown classes are ignored. The sorter normalizes the final execution order.

Example:

```php
<?php

use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Filters\XmlToPh;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;

// Example 1: enable only a subset of supported injectable handlers.
// Only handlers known to the sorter will be kept and ordered.
$featureSet = new YourFeatureSetImplementation(); // implements FeatureSetInterface

$filter = MateCatFilter::getInstance(
    $featureSet,
    'en-US',
    'it-IT',
    [], // dataRef map
    [
        XmlToPh::class,                 // supported
        SingleCurlyBracketsToPh::class, // supported (disabled by default, but its injection is allowed and thus, enabled here)
        // any unsupported class would be ignored
    ]
);

$input = 'You have {NUM_RESULTS, plural, =0 {no results} one {1 result} other {# results}} for "{SEARCH_TERM}".';

// 'You have {NUM_RESULTS, plural, =0 {no results} one {1 result} other {# results}} for "<ph id="mtc_1" ctype="x-curly-brackets" equiv-text="base64:e1NFQVJDSF9URVJNfQ=="/>".'
$l1 = $filter->fromLayer0ToLayer1($input);

$l2 = $filter->fromLayer0ToLayer2($input);

```

### Disable all injectable handlers by passing null

Example:

```php
<?php

use Matecat\SubFiltering\MateCatFilter;

// Example 2: disable all injectable handlers by passing null.
// Only the fixed, non-injectable pipeline steps will run.
$featureSet = new YourFeatureSetImplementation(); // implements FeatureSetInterface

$filterNoInjectables = MateCatFilter::getInstance(
    $featureSet,
    'en-US',
    'it-IT',
    [],
    null // no injectable handlers
);

$input = 'Plain text without custom injectable handlers.';

$l1_no = $filterNoInjectables->fromLayer0ToLayer1($input);
$l2_no = $filterNoInjectables->fromLayer0ToLayer2($input);
````

## FeatureSet

You must provide a `FeatureSetInterface` implementation to adjust the pipeline per transformation. A simple, working example lives under the tests/ folder. In your application, implement only the features you need and register them via your FeatureSet.

## Running tests

```shell
bash composer install ./vendor/bin/phpunit
```

## Support

Please open issues and feature requests on GitHub:
https://github.com/matecat/subfiltering/issues

## Authors

- **Domenico Lupinetti** - https://github.com/ostico
- **Mauro Cassani** - https://github.com/mauretto78

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE.md) file for details
