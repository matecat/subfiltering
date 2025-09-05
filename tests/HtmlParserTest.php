<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 15/01/20
 * Time: 15:18
 *
 */

namespace Matecat\SubFiltering\Tests;

use Exception;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\XmlToPh;
use PHPUnit\Framework\TestCase;

class HtmlParserTest extends TestCase {

    /**
     * @throws Exception
     */
    public function test4() {

        // This HTML segment comes from the previous layers.
        // We must extract and lock HTML inside ph tags AS IS
        // WARNING the href attribute MUST NOT BE encoded because we want to only extract HTML
        // WARNING the text node inside HTML must remain untouched
        $segment  = "<p> Airbnb &amp;amp; Co. &amp;lt; <strong>Use professional tools</strong> in your <a href=\"/users/settings?test=123&amp;amp;ciccio=1\" target=\"_blank\">";
        $expected = "<ph id=\"mtc_1\" ctype=\"" . CTypeEnum::XML . "\" equiv-text=\"base64:Jmx0O3AmZ3Q7\"/> Airbnb &amp;amp; Co. &amp;lt; <ph id=\"mtc_2\" ctype=\"" . CTypeEnum::XML . "\" equiv-text=\"base64:Jmx0O3N0cm9uZyZndDs=\"/>Use professional tools<ph id=\"mtc_3\" ctype=\"" . CTypeEnum::XML . "\" equiv-text=\"base64:Jmx0Oy9zdHJvbmcmZ3Q7\"/> in your <ph id=\"mtc_4\" ctype=\"" . CTypeEnum::XML . "\" equiv-text=\"base64:Jmx0O2EgaHJlZj0iL3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDthbXA7Y2ljY2lvPTEiIHRhcmdldD0iX2JsYW5rIiZndDs=\"/>";

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );
        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );

    }

    /**
     * Expect base64 decoded as
     * <code>
     *    &lt;style&gt;body{background-color:powderblue;}
     *    h1 {color:blue;}p{color:red;}
     *    &lt;/style&gt;
     * </code>
     */
    public function testValidCSS() {
        $segment = "<style>body{background-color:powderblue;} 
h1 {color:blue;}p{color:red;}
</style>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3N0eWxlJmd0O2JvZHl7YmFja2dyb3VuZC1jb2xvcjpwb3dkZXJibHVlO30gCmgxIHtjb2xvcjpibHVlO31we2NvbG9yOnJlZDt9CiZsdDsvc3R5bGUmZ3Q7"/>';

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );

        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );
    }

    /**
     * In this test style tags cannot be locked in a PH tag because there is not a closing </style> tag. It will be expressed as plain encode
     */
    public function testNotValidCSS() {
        $segment = "<style>body{background-color:powderblue;} h1 {color:blue;}p{color:red;}";

        $expected = "&lt;style&gt;body{background-color:powderblue;} h1 {color:blue;}p{color:red;}";

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );

        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );
    }

    /**
     * These should be treated as simple tags with no special meaning
     */
    public function testStyleLikeTags() {

        $segment = "<style0>this is a test text inside a custom XML tag similar to a style HTML tag</style0>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3N0eWxlMCZndDs="/>this is a test text inside a custom XML tag similar to a style HTML tag<ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9zdHlsZTAmZ3Q7"/>';

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );

        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );
    }

    /**
     * Expect base64 decoded as
     * <code>
     * &lt;script&gt;
     * let elements = document.getElementsByClassName('note');
     * &lt;/script&gt;
     * </code>
     */
    public function testJS() {

        $segment = "<script>
let elements = document.getElementsByClassName('note');
</script>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3NjcmlwdCZndDsKbGV0IGVsZW1lbnRzID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnbm90ZScpOwombHQ7L3NjcmlwdCZndDs="/>';

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );

        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );
    }

    /**
     * These should be treated as simple tags with no special meaning
     */
    public function testScriptLikeTags() {

        $segment = "<scripting>let elements = document.getElementsByClassName('note');</scripting>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3NjcmlwdGluZyZndDs="/>let elements = document.getElementsByClassName(\'note\');<ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9zY3JpcHRpbmcmZ3Q7"/>';

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );

        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );
    }

    public function testWithDoublePoints() {
        $segment  = "<l:style1>test</l:style1>";
        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O2w6c3R5bGUxJmd0Ow=="/>test<ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9sOnN0eWxlMSZndDs="/>';

        $pipeline = new Pipeline();
        $pipeline->addLast( XmlToPh::class );

        $str = $pipeline->transform( $segment );

        $this->assertEquals( $expected, $str );
    }

}