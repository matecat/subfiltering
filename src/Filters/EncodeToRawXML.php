<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Utils\Utils;

class EncodeToRawXML extends AbstractHandler
{
    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        // handling &#10; (line feed)
        // prevent to convert it to \n
        $segment = preg_replace('/&(#10;|#x0A;)|\n/', '##_ent_0A_##', $segment);

        // handling &#13; (carriage return)
        // prevent to convert it to \r
        $segment = preg_replace('/&(#13;|#x0D;)|\r/', '##_ent_0D_##', $segment);

        // handling &#09; (tab)
        // prevent to convert it to \t
        $segment = preg_replace('/&#09;|\t/', '##_ent_09_##', $segment);

        //Substitute 4(+)-byte characters from a UTF-8 string to htmlentities
        $segment = preg_replace_callback('/([\xF0-\xF7]...)/s', [Utils::class, 'htmlentitiesFromUnicode'], $segment);

        // handling &#10;
        if (str_contains($segment, '##_ent_0D_##')) {
            $segment = str_replace('##_ent_0D_##', '&#13;', $segment);
        }

        // handling &#13;
        if (str_contains($segment, '##_ent_0A_##')) {
            $segment = str_replace('##_ent_0A_##', '&#10;', $segment);
        }

        // handling &#09; (tab)
        // prevent to convert it to \t
        if (str_contains($segment, '##_ent_09_##')) {
            $segment = str_replace('##_ent_09_##', '&#09;', $segment);
        }


        //encode all not valid XML entities
        return preg_replace('/&(?!lt;|gt;|amp;|quot;|apos;|#x?[0-9A-F]{1,7};)/', '&amp;', $segment);
    }
}
