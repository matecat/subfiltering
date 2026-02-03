<?php

namespace Matecat\SubFiltering\Enum;

class ConstantEnum
{
    public const INTERNAL_ATTR_ID_PREFIX = '__mtc_';
    public const LTPLACEHOLDER = "##LESSTHAN##";
    public const GTPLACEHOLDER = "##GREATERTHAN##";
    public const AMPPLACEHOLDER = "##AMPPLACEHOLDER##";

    public const lfPlaceholder = '##$_0A$##';
    public const crPlaceholder = '##$_0D$##';
    public const crlfPlaceholder = '##$_0D0A$##';
    public const tabPlaceholder = '##$_09$##';
    public const nbspPlaceholder = '##$_A0$##';

    public const splitPlaceHolder = '##$_SPLIT$##';

    public const xliffInXliffStartPlaceHolder = '##XLIFFTAGPLACEHOLDER_START##';
    public const xliffInXliffEndPlaceHolder = '##XLIFFTAGPLACEHOLDER_END##';

}
