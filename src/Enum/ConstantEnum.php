<?php

namespace Matecat\SubFiltering\Enum;


enum ConstantEnum: string
{
    case INTERNAL_ATTR_ID_PREFIX = '__mtc_';
    case LTPLACEHOLDER = '##LESSTHAN##';
    case GTPLACEHOLDER = '##GREATERTHAN##';
    case AMPPLACEHOLDER = '##AMPPLACEHOLDER##';

    case lfPlaceholder = '##$_0A$##';
    case crPlaceholder = '##$_0D$##';
    case crlfPlaceholder = '##$_0D0A$##';
    case tabPlaceholder = '##$_09$##';
    case nbspPlaceholder = '##$_A0$##';

    case splitPlaceHolder = '##$_SPLIT$##';

    case xliffInXliffStartPlaceHolder = '##XLIFFTAGPLACEHOLDER_START##';
    case xliffInXliffEndPlaceHolder = '##XLIFFTAGPLACEHOLDER_END##';
}