# Matecat Subfiltering

[![Build Status](https://app.travis-ci.com/matecat/subfiltering.svg?token=qBazxkHwP18h3EWnHjjF&branch=master)](https://app.travis-ci.com/matecat/subfiltering)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/matecat/subfiltering/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/matecat/subfiltering/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/matecat/subfiltering/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/matecat/subfiltering/?branch=master)

Subfiltering is a component used by [Matecat](https://matecat.com) and [MyMemory](https://mymemory.translated.net/)) for string conversion from database to UI layer and viceversa.

## How to use

There are two filters available (both are implementation of `AbstractFilter`):

- `MateCatFilter`
- `MyMemoryFilter`

Use `getInstance` method to instantiate these classes:

```php

use Matecat\SubFiltering\MateCatFilter;

$filter = MateCatFilter::getInstance(new FeatureSet(), 'it-IT', 'en-EN', []);
```

The first argument MUST be concrete implementation of `Matecat\SubFiltering\Contracts\FeatureSetInterface`.

The other three arguments are optional:

- `$source` (string) - The source language
- `$target` (string) - The target language
- `$dataRefMap` (array) - A map used for tag replacement (only for segments from Xliff 2.0). A full documentation for dataRef replacement is available [here](https://github.com/matecat/subfiltering/blob/master/docs/dataRef.md).

### dataRef replacement



## Basic Usage

Once `AbstractFilter` class is instantiated you can use several methods to convert strings from one layer to another one.

### MateCatFilter methods

- `fromLayer0ToLayer2`
- `fromLayer1ToLayer2`
- `fromLayer2ToLayer1`
- `fromLayer2ToLayer0`
- `fromLayer0ToLayer1`
- `fromLayer1ToLayer0`
- `fromRawXliffToLayer0`
- `fromLayer0ToRawXliff`

### MyMemoryFilter methods

- `fromLayer0ToLayer1`
- `fromLayer1ToLayer0`

Where `Layer0` is the DB layer, `Layer1` is the intermediate layer and `Layer2` is the MateCat's UI layer.

## Examples

In the `tests` folder there is an fully working example of a concrete implementation of `FeatureSetInterface` with a custom filter.

```
// tests/Mocks 
.
├── Features
│   ├── AirbnbFeature.php
│   └── BaseFeature.php
└── FeatureSet.php

```

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/matecat/subfiltering/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)
* **Domenico Lupinetti** - [github](https://github.com/ostico)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
