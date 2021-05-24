# Matecat Subfiltering

Subfiltering is a [Matecat](https://matecat.com) component used for string conversion from database to UI layer and viceversa.

## How to use

To instantiate `Filter` class do the following:

```php

use Matecat\SubFiltering\Filter;

$filter = Filter::getInstance(new FeatureSet());
```

The only required argument is a valid instance of `Matecat\SubFiltering\Contracts\FeatureSetInterface`.

There are three more arguments you can pass:

- `$source` (string) - The source language
- `$target` (string) - The target language
- `$dataRefMap` (array) - Dataref map (only for segments from Xliff 2.0)

## Basic Usage

Once `Filter` class is instantiated you can use the following methods to convert strings from one layer to another one:

- `fromLayer0ToLayer2`
- `fromLayer1ToLayer2`
- `fromLayer2ToLayer1`
- `fromLayer2ToLayer0`
- `fromLayer0ToLayer1`
- `fromLayer1ToLayer0`
- `fromRawXliffToLayer0`
- `fromLayer0ToRawXliff`

Where `Layer0` is the DB layer, `Layer1` is the intermediate layer and `Layer2` is the UI layer.

## Support

If you found an issue or had an idea please refer [to this section](https://github.com/matecat/subfiltering/issues).

## Authors

* **Mauro Cassani** - [github](https://github.com/mauretto78)
* **Domenico Lupinetti** - [github](https://github.com/ostico)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details