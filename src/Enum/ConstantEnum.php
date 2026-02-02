<?php

namespace Matecat\SubFiltering\Enum;


class ConstantEnum
{

    const INTERNAL_ATTR_ID_PREFIX = '__mtc_';
    const LTPLACEHOLDER = "##LESSTHAN##";
    const GTPLACEHOLDER = "##GREATERTHAN##";
    const AMPPLACEHOLDER = "##AMPPLACEHOLDER##";

    const lfPlaceholder = '##$_0A$##';
    const crPlaceholder = '##$_0D$##';
    const crlfPlaceholder = '##$_0D0A$##';
    const tabPlaceholder = '##$_09$##';
    const nbspPlaceholder = '##$_A0$##';

    const splitPlaceHolder = '##$_SPLIT$##';

    const xliffInXliffStartPlaceHolder = '##XLIFFTAGPLACEHOLDER_START##';
    const xliffInXliffEndPlaceHolder = '##XLIFFTAGPLACEHOLDER_END##';

}