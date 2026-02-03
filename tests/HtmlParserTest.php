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
use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\Html\HtmlParser;
use Matecat\SubFiltering\Filters\MarkupToPh;
use Matecat\SubFiltering\Filters\MateCatCustomPHToOriginalValue;
use PHPUnit\Framework\TestCase;

class HtmlParserTest extends TestCase
{
    public function testRegisterInvalidCallbacksHandlerThrowsException()
    {
        // Expect the specific exception to be thrown
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Class passed to Matecat\SubFiltering\Filters\Html\HtmlParser::registerCallbacksHandler must use Matecat\SubFiltering\Filters\Html\CallbacksHandler trait.'
        );

        // Create an instance of the parser
        $parser = new HtmlParser();

        // Create an anonymous class instance that extends AbstractHandler
        // but does NOT use the CallbacksHandler trait.
        $invalidHandler = new class () extends AbstractHandler {
            public function transform(string $segment): string
            {
                return $segment; // Fake implementation
            }
        };

        // This call should trigger the RuntimeException
        $parser->registerCallbacksHandler($invalidHandler);
    }

    /**
     * @throws Exception
     */
    public function test4()
    {
        // This HTML segment comes from the previous layers.
        // We must extract and lock HTML inside ph tags AS IS
        // WARNING the href attribute MUST NOT BE encoded because we want to only extract HTML
        // WARNING the text node inside HTML must remain untouched
        $segment = "<p> Airbnb &amp;amp; Co. &amp;lt; <strong>Use professional tools</strong> in your <a href=\"/users/settings?test=123&amp;amp;ciccio=1\" target=\"_blank\">";
        $expected = "<ph id=\"mtc_1\" ctype=\"" . CTypeEnum::HTML . "\" equiv-text=\"base64:Jmx0O3AmZ3Q7\"/> Airbnb &amp;amp; Co. &amp;lt; <ph id=\"mtc_2\" ctype=\"" . CTypeEnum::HTML . "\" equiv-text=\"base64:Jmx0O3N0cm9uZyZndDs=\"/>Use professional tools<ph id=\"mtc_3\" ctype=\"" . CTypeEnum::HTML . "\" equiv-text=\"base64:Jmx0Oy9zdHJvbmcmZ3Q7\"/> in your <ph id=\"mtc_4\" ctype=\"" . CTypeEnum::HTML . "\" equiv-text=\"base64:Jmx0O2EgaHJlZj0iL3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDthbXA7Y2ljY2lvPTEiIHRhcmdldD0iX2JsYW5rIiZndDs=\"/>";

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);
        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    /**
     * Expect base64 decoded as
     * <code>
     *    &lt;style&gt;body{background-color:powderblue;}
     *    h1 {color:blue;}p{color:red;}
     *    &lt;/style&gt;
     * </code>
     */
    public function testValidCSS()
    {
        $segment = "<style>body{background-color:powderblue;} 
h1 {color:blue;}p{color:red;}
</style>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3N0eWxlJmd0O2JvZHl7YmFja2dyb3VuZC1jb2xvcjpwb3dkZXJibHVlO30gCmgxIHtjb2xvcjpibHVlO31we2NvbG9yOnJlZDt9CiZsdDsvc3R5bGUmZ3Q7"/>';

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    /**
     * In this test style tags cannot be locked in a PH tag because there is not a closing </style> tag. It will be expressed as plain encode
     */
    public function testNotValidCSS()
    {
        $segment = "<style>body{background-color:powderblue;} h1 {color:blue;}p{color:red;}";

        $expected = "&lt;style&gt;body{background-color:powderblue;} h1 {color:blue;}p{color:red;}";

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    /**
     * These should be treated as simple tags with no special meaning
     */
    public function testStyleLikeTags()
    {
        $segment = "<style0>this is a test text inside a custom XML tag similar to a style HTML tag</style0>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3N0eWxlMCZndDs="/>this is a test text inside a custom XML tag similar to a style HTML tag<ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9zdHlsZTAmZ3Q7"/>';

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    /**
     * Expect base64 decoded as
     * <code>
     * &lt;script&gt;
     * let elements = document.getElementsByClassName('note');
     * &lt;/script&gt;
     * </code>
     */
    public function testJS()
    {
        $segment = "<script>
let elements = document.getElementsByClassName('note');
</script>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3NjcmlwdCZndDsKbGV0IGVsZW1lbnRzID0gZG9jdW1lbnQuZ2V0RWxlbWVudHNCeUNsYXNzTmFtZSgnbm90ZScpOwombHQ7L3NjcmlwdCZndDs="/>';

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    /**
     * These should be treated as simple tags with no special meaning
     */
    public function testScriptLikeTags()
    {
        $segment = "<scripting>let elements = document.getElementsByClassName('note');</scripting>";

        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3NjcmlwdGluZyZndDs="/>let elements = document.getElementsByClassName(\'note\');<ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9zY3JpcHRpbmcmZ3Q7"/>';

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    public function testWithDoublePoints()
    {
        $segment = "<l:style1>test</l:style1>";
        $expected = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O2w6c3R5bGUxJmd0Ow=="/>test<ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9sOnN0eWxlMSZndDs="/>';

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    /**
     * @test
     * @throws Exception
     */
    public function testTransformWithComplexStringForAllBranches()
    {
        // This string is designed to exercise as many conditional branches
        // of the HtmlParser::transform method as possible in a single run.
        $segment = "A&gt;B plain text. &lt;p class='c1\"c2'&gt;valid tag&lt;/p&gt; &lt;!-- comment --&gt;&lt;script&gt;js&lt;/script&gt;&lt;style&gt;css&lt;/style&gt; &lt; invalid-tag&gt; &lt;a&lt;b and finally &lt;u";

        $pipeline = new Pipeline();
        // MarkupToPh uses HtmlParser internally and provides the necessary callbacks.
        $pipeline->addLast(MarkupToPh::class);

        $transformed = $pipeline->transform($segment);

        // Manually build the expected output by tracing the parser's logic with the MarkupToPh handler.
        $expected =
            // 1. Plain text with a stray '>', which gets encoded.
            'A&gt;B plain text. ' .

            // 2. A valid opening <p> tag, which becomes a placeholder.
            '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:' . base64_encode(
                htmlentities("<p class='c1\"c2'>", ENT_NOQUOTES | 16)
            ) . '"/>' .

            // 3. Plain text content of the tag.
            'valid tag' .

            // 4. A valid closing </p> tag.
            '<ph id="mtc_2" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:' . base64_encode(
                htmlentities("</p>", ENT_NOQUOTES | 16)
            ) . '"/>' .

            // 5. A space.
            ' ' .

            // 6. A comment block, which becomes a placeholder.
            '<ph id="mtc_3" ctype="' . CTypeEnum::XML . '" equiv-text="base64:' . base64_encode(
                htmlentities("<!-- comment -->", ENT_NOQUOTES | 16)
            ) . '"/>' .

            // 7. A script block.
            '<ph id="mtc_4" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:' . base64_encode(
                htmlentities("<script>js</script>", ENT_NOQUOTES | 16)
            ) . '"/>' .

            // 8. A style block.
            '<ph id="mtc_5" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:' . base64_encode(
                htmlentities("<style>css</style>", ENT_NOQUOTES | 16)
            ) . '"/>' .

            // 9. Invalid tag start ('< '), invalid tag content, and stray '>'. All are encoded.
            ' &lt; invalid-tag&gt; ' .

            // 10. A nested '<' and an unclosed tag are encoded.
            '&lt;a&lt;b' .

            // 11. Plain text.
            ' and finally ' .

            // 12. The final unclosed tag at the end of the string is encoded.
            '&lt;u';

        $this->assertEquals($expected, $transformed);

        $pipeline = new Pipeline();
        // MarkupToPh uses HtmlParser internally and provides the necessary callbacks.
        $pipeline->addLast(MateCatCustomPHToOriginalValue::class); // Restore original PH values
        $backTransformed = $pipeline->transform($expected);

        $this->assertEquals($segment, $backTransformed);
    }

    /**
     * @test
     */
    public function testTransformWithEmptyString()
    {
        $segment = '';
        $expected = '';

        $pipeline = new Pipeline();
        $pipeline->addLast(MarkupToPh::class);

        $str = $pipeline->transform($segment);

        $this->assertEquals($expected, $str);
    }

    public function testNotXMLContentShouldRemainUntouched()
    {
        $segment = 'Test for the &lt;original shipment date&gt; which is not a valid XML content';
        $pipeline = new Pipeline();
        // MarkupToPh uses HtmlParser internally and provides the necessary callbacks.
        $pipeline->addLast(MarkupToPh::class);

        $transformed = $pipeline->transform($segment);

        $this->assertEquals($segment, $transformed);
    }

}
