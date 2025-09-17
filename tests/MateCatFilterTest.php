<?php

namespace Matecat\SubFiltering\Tests;

use Exception;
use Matecat\SubFiltering\AbstractFilter;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Filters\EquivTextToBase64;
use Matecat\SubFiltering\Filters\PlaceHoldXliffTags;
use Matecat\SubFiltering\Filters\RestorePlaceHoldersToXLIFFLtGt;
use Matecat\SubFiltering\Filters\RestoreXliffTagsContent;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\SmartCounts;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\StandardPHToMateCatCustomPH;
use Matecat\SubFiltering\Filters\StandardXEquivTextToMateCatCustomPH;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\XmlToPh;
use Matecat\SubFiltering\HandlersSorter;
use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Tests\Mocks\Features\AirbnbFeature;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MateCatFilterTest extends TestCase {
    /**
     * @param array $data_ref_map
     *
     * @return MateCatFilter
     */
    private function getFilterInstance( array $data_ref_map = [] ): MateCatFilter {

        /** @var $filter MateCatFilter */
        $filter = MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', $data_ref_map );

        return $filter;

    }

    /**
     * @test
     * Validates the default behavior of the MateCatFilter::getInstance method when instantiated
     * with an empty array of handler class names for the transition from Layer0 to Layer1.
     * Checks that the default handlers are correctly loaded and ordered.
     *
     * @return void
     * @throws Exception
     */
    public function testGetInstanceWithDefaultHandlers() {
        // default $handlerClassNamesForLayer0ToLayer1Transition is an empty array
        $filter = MateCatFilter::getInstance( new FeatureSet() );

        $reflection              = new ReflectionClass( AbstractFilter::class );
        $orderedHandlersProperty = $reflection->getProperty( 'orderedHandlersForLayer0ToLayer1Transition' );
        $orderedHandlersProperty->setAccessible( true );
        $orderedHandlers = $orderedHandlersProperty->getValue( $filter );

        // check that default handlers are loaded
        $defaultHandlers = array_keys( HandlersSorter::getDefaultInjectedHandlers() );
        $this->assertEquals( $defaultHandlers, $orderedHandlers );
    }

    /**
     * Tests the behavior of the MateCatFilter::getInstance method when null is passed
     * as the handlers' parameter for the layer0 to layer1 transition.
     *
     * @return void
     */
    public function testGetInstanceWithNullHandlers() {
        // Pass null for $handlerClassNamesForLayer0ToLayer1Transition
        $filter = MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', [], null );

        $reflection              = new ReflectionClass( AbstractFilter::class );
        $orderedHandlersProperty = $reflection->getProperty( 'orderedHandlersForLayer0ToLayer1Transition' );
        $orderedHandlersProperty->setAccessible( true );
        $orderedHandlers = $orderedHandlersProperty->getValue( $filter );

        // check that no injectable handlers are loaded
        $this->assertEmpty( $orderedHandlers );
    }

    /**
     * Tests that the filter's pipeline is correctly configured with custom handlers.
     *
     * This test verifies that when MateCatFilter is instantiated with a custom set of
     * handlers, the underlying pipeline configuration correctly includes those handlers
     * in the expected order. It uses a mock pipeline to capture arguments and verify
     * the behavior without inspecting private properties.
     *
     * @return void
     * @throws Exception
     */
    public function testGetInstanceWithCustomHandlers() {
        // Arrange: Define a custom handler and instantiate the filter.
        $customHandlers = [ XmlToPh::class, SingleCurlyBracketsToPh::class ];
        $filter         = MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', [], $customHandlers );

        // Arrange: Create a mock Pipeline to capture calls to addLast.
        $pipelineMock  = $this->createMock( Pipeline::class );
        $addedHandlers = [];
        $pipelineMock->expects( $this->exactly( 8 ) )
                ->method( 'addLast' )
                ->willReturnCallback(
                        function ( $handlerClass ) use ( &$addedHandlers, $pipelineMock ) {
                            $addedHandlers[] = $handlerClass;

                            return $pipelineMock;
                        }
                );

        // Act: Invoke the protected method that configures the pipeline.
        $reflection = new ReflectionClass( AbstractFilter::class );
        $method     = $reflection->getMethod( 'configureFromLayer0ToLayer1Pipeline' );
        $method->setAccessible( true );
        $method->invoke( $filter, $pipelineMock );

        // Assert: Verify the correct handlers were added in the expected order.
        $expectedHandlers = [
                StandardPHToMateCatCustomPH::class,
                StandardXEquivTextToMateCatCustomPH::class,
                PlaceHoldXliffTags::class,
                XmlToPh::class, // Verifies our custom handler is included
                SingleCurlyBracketsToPh::class, // Verifies our custom handler is included even if it is not default
                RestoreXliffTagsContent::class,
                RestorePlaceHoldersToXLIFFLtGt::class,
                EquivTextToBase64::class,
        ];
        $this->assertSame( $expectedHandlers, $addedHandlers );

        // And assert that a handler not in the custom list is excluded
        $this->assertNotContains( SprintfToPH::class, $addedHandlers );
    }

    public function testFromLayer0ToLayer1WithNoHandlers() {
        $filter = MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', [], null );

        $string    = 'This is &lt;b&gt;bold&lt;/b&gt; text.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $string );
        $this->assertEquals( $string, $segmentL1 );

        // test 2
        $string    = 'Text with <ph id="1" equiv-text="&lt;br/&gt;"/> and placeholders &lt;b&gt;.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $string );
        $this->assertEquals( 'Text with <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="PHBoIGlkPSIxIiBlcXVpdi10ZXh0PSImbHQ7YnIvJmd0OyIvPg==" equiv-text="base64:Jmx0O2JyLyZndDs="/> and placeholders &lt;b&gt;.', $segmentL1 );

    }

    /**
     *
     * @throws Exception
     */
    public function testICUString() {

        /** @var $filter MateCatFilter */
        $filter = MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', null, [ SingleCurlyBracketsToPh::class, XmlToPh::class, SprintfToPH::class ] );

        $segment   = 'You have {NUM_RESULTS, plural, =0 {no results} one {1 result} other {# results}} for "{SEARCH_TERM}".';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );

        $this->assertEquals( 'You have {NUM_RESULTS, plural, =0 {no results} one {1 result} other {# results}} for "<ph id="mtc_1" ctype="' . CTypeEnum::CURLY_BRACKETS . '" equiv-text="base64:e1NFQVJDSF9URVJNfQ=="/>".', $segmentL1 );

        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );
    }

    /**
     * @test
     * @dataProvider fromLayer0ToRawXliffProvider
     *
     * @param string $layer0_segment
     * @param string $expected_raw_xliff
     *
     * @throws Exception
     */
    public function testFromLayer0ToRawXliff( string $layer0_segment, string $expected_raw_xliff ) {
        $filter = $this->getFilterInstance();
        $this->assertEquals( $expected_raw_xliff, $filter->fromLayer0ToRawXliff( $layer0_segment ) );
    }

    /**
     * @return array
     */
    public function fromLayer0ToRawXliffProvider(): array {
        return [
                'no tags or special characters'     => [
                        'segment'  => 'This is a simple sentence.',
                        'expected' => 'This is a simple sentence.',
                ],
                'with stray < and > characters'     => [
                        'segment'  => 'A > B and C < D.',
                        'expected' => 'A &gt; B and C &lt; D.',
                ],
                'with a g-tag and stray characters' => [
                        'segment'  => 'This is a <g id="1">g-tag</g>, and this is a stray < char.',
                        'expected' => 'This is a &lt;g id="1"&gt;g-tag&lt;/g&gt;, and this is a stray &lt; char.',
                ],
                'with already encoded HTML'         => [
                        'segment'  => 'This is &lt;b&gt;bold&lt;/b&gt; text.',
                        'expected' => 'This is &lt;b&gt;bold&lt;/b&gt; text.', // Should not be double-encoded
                ],
                'with dangerous control characters' => [
                        'segment'  => "This is a dangerous char: \x07 and a valid <g id='1'>tag</g>.",
                        'expected' => "This is a dangerous char:  and a valid &lt;g id='1'&gt;tag&lt;/g&gt;.", // The BEL char should be removed
                ],
        ];
    }


    /**
     * Tests the initialization of a filter instance with a null data reference map.
     * Ensures that the dataRefMap property of the filter instance is set to an empty array.
     *
     * @return void
     * @throws Exception
     */
    public function testGetInstanceWithNullDataRefMap() {
        $filter = MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', null );

        $reflection         = new ReflectionClass( AbstractFilter::class );
        $dataRefMapProperty = $reflection->getProperty( 'dataRefMap' );
        $dataRefMapProperty->setAccessible( true );

        $this->assertSame( [], $dataRefMapProperty->getValue( $filter ) );
    }


    /**
     * Test realignIDInLayer1 when source and target have matching tags
     */
    public function testRealignIDInLayer1WithMatchingTags(): void {

        $mateCatFilter = new MateCatFilter();

        $source = '<ph id="mtc_1" equiv-text="base64:JTEkcw=="/> <ph id="mtc_2" equiv-text="base64:JTEkcw=="/>';
        $target = '<ph id="mtc_5" equiv-text="base64:JTEkcw=="/> <ph id="mtc_6" equiv-text="base64:JTEkcw=="/>';

        $expected = '<ph id="mtc_1" equiv-text="base64:JTEkcw=="/> <ph id="mtc_2" equiv-text="base64:JTEkcw=="/>';
        $result   = $mateCatFilter->realignIDInLayer1( $source, $target );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test realignIDInLayer1 when tags in source and target do not match in number
     */
    public function testRealignIDInLayer1WithMismatchedTagCount(): void {

        $mateCatFilter = new MateCatFilter();

        $source = '<ph id="mtc_1" equiv-text="base64:JTEkcw=="/>';
        $target = '<ph id="mtc_5" equiv-text="base64:JTEkcw=="/> <ph id="mtc_6" equiv-text="base64:JTEkcw=="/>';

        $expected = $target; // When mismatched, target should remain unchanged
        $result   = $mateCatFilter->realignIDInLayer1( $source, $target );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test realignIDInLayer1 when some tags in target do not match source tags
     */
    public function testRealignIDInLayer1WithNonMatchingTags(): void {

        $mateCatFilter = new MateCatFilter();

        $source = '<ph id="mtc_1" equiv-text="base64:JTEkcw=="/> <ph id="mtc_2" equiv-text="base64:abc123=="/>';
        $target = '<ph id="mtc_5" equiv-text="base64:JTEkcw=="/> <ph id="mtc_6" equiv-text="base64:xyz789=="/>';

        $expected = '<ph id="mtc_1" equiv-text="base64:JTEkcw=="/> <ph id="mtc_6" equiv-text="base64:xyz789=="/>';
        $result   = $mateCatFilter->realignIDInLayer1( $source, $target );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test realignIDInLayer1 with completely mismatched tags
     */
    public function testRealignIDInLayer1WithCompletelyMismatchedTags(): void {

        $mateCatFilter = new MateCatFilter();

        $source = '<ph id="mtc_1" equiv-text="base64:abc123=="/> <ph id="mtc_2" equiv-text="base64:xyz456=="/>';
        $target = '<ph id="mtc_5" equiv-text="base64:zzz999=="/> <ph id="mtc_6" equiv-text="base64:yyy888=="/>';

        $expected = $target; // Completely mismatched tags, so target remains unchanged
        $result   = $mateCatFilter->realignIDInLayer1( $source, $target );

        $this->assertEquals( $expected, $result );
    }

    /**
     * Test realignIDInLayer1 with no tags in source and target
     */
    public function testRealignIDInLayer1WithNoTags(): void {

        $mateCatFilter = new MateCatFilter();

        $source = 'This is a plain text without tags.';
        $target = 'This is another plain text without tags.';

        $expected = $target; // No tags present, target remains unchanged
        $result   = $mateCatFilter->realignIDInLayer1( $source, $target );

        $this->assertEquals( $expected, $result );
    }


    /**
     * @throws Exception
     */
    public function testSimpleString() {
        $filter = $this->getFilterInstance();

        $segment   = "The house is red.";
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );
    }

    public function testHTMLStringWithApostrophe() {
        $filter = $this->getFilterInstance();

        $segment   = "&lt;Value&gt; &lt;![CDATA[Visitez Singapour et détendez-vous sur l'île de Langkawi]]&gt; &lt;/Value&gt;";
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
    }

    /**
     * @throws Exception
     */
    public function testHtmlInXML() {
        $filter = $this->getFilterInstance();

        $segment   = '&lt;p&gt; Airbnb &amp;amp; Co. &amp;lt; <x id="1"> &lt;strong&gt;Use professional tools&lt;/strong&gt; in your &lt;a href="/users/settings?test=123&amp;amp;ciccio=1" target="_blank"&gt;';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
    }

    /**
     * @throws Exception
     */
    public function testUIHtmlInXML() {
        $filter = $this->getFilterInstance();

        $segment   = '&lt;p&gt; Airbnb &amp;amp; Co. &amp;lt; &lt;strong&gt;Use professional tools&lt;/strong&gt; in your &lt;a href="/users/settings?test=123&amp;amp;ciccio=1" target="_blank"&gt;';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        //Start test
        $string_from_UI = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3AmZ3Q7"/> Airbnb &amp;amp; Co. &amp;lt; <ph id="mtc_2" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3N0cm9uZyZndDs="/>Use professional tools<ph id="mtc_3" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0Oy9zdHJvbmcmZ3Q7"/> in your <ph id="mtc_4" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O2EgaHJlZj0iL3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDthbXA7Y2ljY2lvPTEiIHRhcmdldD0iX2JsYW5rIiZndDs="/>';

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testComplexUrls() {
        $filter = $this->getFilterInstance();

        $fromUi       = '<ph id="mtc_14" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O2EgaHJlZj0iaHR0cHM6Ly9hdXRoLnViZXIuY29tL2xvZ2luLz9icmVlemVfbG9jYWxfem9uZT1kY2ExJmFtcDthbXA7bmV4dF91cmw9aHR0cHMlM0ElMkYlMkZkcml2ZXJzLnViZXIuY29tJTJGcDMlMkYmYW1wO2FtcDtzdGF0ZT00MElLeF9YR0N1OXRobEtrSUkxUmRCOFlhUVRVY0g1aE1uVnllWXJCN0lBJTNEIiZndDs="/>Partner Dashboard<ph id="mtc_15" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0Oy9hJmd0Ow=="/> to match the payment document you uploaded';
        $expectedToDb = '&lt;a href="https://auth.uber.com/login/?breeze_local_zone=dca1&amp;amp;next_url=https%3A%2F%2Fdrivers.uber.com%2Fp3%2F&amp;amp;state=40IKx_XGCu9thlKkII1RdB8YaQTUcH5hMnVyeYrB7IA%3D"&gt;Partner Dashboard&lt;/a&gt; to match the payment document you uploaded';
        $toDb         = $filter->fromLayer1ToLayer0( $fromUi );

        $this->assertEquals( $toDb, $expectedToDb );
    }

    /**
     * @throws Exception
     */
    public function testComplexXML() {
        $filter = $this->getFilterInstance();

        $segment   = '&lt;p&gt; Airbnb &amp;amp; Co. &amp;amp; <ph id="PlaceHolder1" equiv-text="{0}"/> &amp;quot; &amp;apos;<ph id="PlaceHolder2" equiv-text="/users/settings?test=123&amp;ciccio=1"/> &lt;a href="/users/settings?test=123&amp;amp;ciccio=1" target="_blank"&gt;';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3AmZ3Q7"/> Airbnb &amp;amp; Co. &amp;amp; <ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="PHBoIGlkPSJQbGFjZUhvbGRlcjEiIGVxdWl2LXRleHQ9InswfSIvPg==" equiv-text="base64:ezB9"/> &amp;quot; &amp;apos;<ph id="mtc_3" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="PHBoIGlkPSJQbGFjZUhvbGRlcjIiIGVxdWl2LXRleHQ9Ii91c2Vycy9zZXR0aW5ncz90ZXN0PTEyMyZhbXA7Y2ljY2lvPTEiLz4=" equiv-text="base64:L3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDtjaWNjaW89MQ=="/> <ph id="mtc_4" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O2EgaHJlZj0iL3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDthbXA7Y2ljY2lvPTEiIHRhcmdldD0iX2JsYW5rIiZndDs="/>';

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @return void
     * @throws Exception
     */
    public function testOriginalPhContent() {

        $filter = $this->getFilterInstance();

        $segment   = 'Test <ph id="PlaceHolder1">Airbnb &amp;amp; Co. &amp;amp;</ph> locked.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = 'Test <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PH_CONTENT . '" x-orig="PHBoIGlkPSJQbGFjZUhvbGRlcjEiPkFpcmJuYiAmYW1wO2FtcDsgQ28uICZhbXA7YW1wOzwvcGg+" equiv-text="base64:QWlyYm5iICZhbXA7YW1wO2FtcDsgQ28uICZhbXA7YW1wO2FtcDs="/> locked.';

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testGTagsWithXidAttributes() {
        $filter = $this->getFilterInstance();

        $segment        = 'This is a <g id="43">test</g> (with a <g xid="068cd98d-103c-49fe-92e1-76e863f93bba" equiv-text="test" id="44">g tag with xid attribute</g>).';
        $string_from_UI = 'This is a <g id="43">test</g> (with a <g xid="068cd98d-103c-49fe-92e1-76e863f93bba" equiv-text="base64:dGVzdA==" id="44">g tag with xid attribute</g>).';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $string_from_UI, $segmentL1 );
        $this->assertEquals( $string_from_UI, $segmentL2 );

        $filter->fromLayer2ToLayer0( $string_from_UI );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $string_from_UI ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );
    }

    /**
     * @throws Exception
     */
    public function testComplexHtmlFilledWithXML() {

        $filter = $this->getFilterInstance();

        $segment   = '<g id="1">To: </g><g id="2">No-foo, Farmaco (Gen) <g id="3">&lt;fa</g><g id="4">foo.bar@foo.com&gt;</g></g>';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $string_from_UI = '<g id="1">To: </g><g id="2">No-foo, Farmaco (Gen) <g id="3">&lt;fa</g><g id="4">foo.bar@foo.com&gt;</g></g>';
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testPlainTextInXMLWithNewLineFeed() {
        $filter = $this->getFilterInstance();

        // 20 Aug 2019
        // ---------------------------
        // Originally we save new lines on DB ("level 0") without any encoding.
        // This of course generates a wrong XML, because in XML the new lines does not make sense.
        // Now we store them as "&#13;" entity in the DB, and return them as "##$_0A$##" for the view level ("level 2"")

        // this was the segment from the original test
//        $original_segment = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand
//
//is &lt; 70 dB(A).';
        $segment         = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand&#10;&#10;is &lt; 70 dB(A).';
        $expectedL1      = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand&#10;&#10;is &lt; 70 dB(A).';
        $expected_fromUI = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand##$_0A$####$_0A$##is &lt; 70 dB(A).';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $this->assertEquals( $segmentL1, $expectedL1 );

        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );
        $this->assertEquals( $expected_fromUI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $expected_fromUI ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $expected_fromUI ) );
    }

    /**
     * @throws Exception
     */
    public function testPlainTextInXMLWithCarriageReturn() {
        $filter = $this->getFilterInstance();

        $segment    = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand&#13;&#13;is &lt; 70 dB(A).';
        $expectedL1 = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand&#13;&#13;is &lt; 70 dB(A).';
        $expectedL2 = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand##$_0D$####$_0D$##is &lt; 70 dB(A).';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segmentL1, $expectedL1 );
        $this->assertEquals( $segmentL2, $expectedL2 );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $string_from_UI = 'The energetically averaged emission sound level of the pressure load cycling and bursting test stand##$_0D$####$_0D$##is &lt; 70 dB(A).';

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );
    }

    /**
     * @throws Exception
     */
    public function test_2_HtmlInXML() {
        $filter = $this->getFilterInstance();

        //DB segment
        $segment   = '&lt;b&gt;de %1$s, &lt;/b&gt;que';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

    }

    public function test_3_HandlingNBSP() {
        $filter = $this->getFilterInstance();

        $segment       = $expectedL1 = '5 tips for creating a great   guide';
        $segment_to_UI = $string_from_UI = '5 tips for creating a great ' . ConstantEnum::nbspPlaceholder . ' guide';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segmentL1, $expectedL1 );
        $this->assertEquals( $segmentL2, $segment_to_UI );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testHTMLFromLayer2() {
        $filter           = $this->getFilterInstance();
        $expected_segment = '&lt;b&gt;de %1$s, &lt;/b&gt;que';

        //Start test
        $string_from_UI = '&lt;b&gt;de <ph id="mtc_1" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:JTEkcw=="/>, &lt;/b&gt;que';
        $this->assertEquals( $expected_segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );

        $string_in_layer1 = '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O2ImZ3Q7"/>de <ph id="mtc_2" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:JTEkcw=="/>, <ph id="mtc_3" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9iJmd0Ow=="/>que';
        $this->assertEquals( $expected_segment, $filter->fromLayer1ToLayer0( $string_in_layer1 ) );

    }

    /**
     **************************
     * NBSP
     **************************
     */

    public function testNbsp() {
        $filter = $this->getFilterInstance();

        $expected_segment = '   Test';
        $string_from_UI   = ConstantEnum::nbspPlaceholder . ConstantEnum::nbspPlaceholder . ConstantEnum::nbspPlaceholder . 'Test';

        $this->assertEquals( $expected_segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );
        $this->assertEquals( $string_from_UI, $filter->fromLayer0ToLayer2( $expected_segment ) );
    }

    public function testNbspAsString() {
        $filter = $this->getFilterInstance();

        // &lt;/x&gt; is a html snippet sent as text and encoded inside a xliff
        // &amp;lt;/i&amp;gt; - &amp;nbsp; is html sent as encoded string like a lesson of html on a web page
        $database_segment = '&lt;/a&gt; - &amp;lt;/i&amp;gt; - &amp;nbsp; -      Text <g id="1">pippo</g>';
        $string_from_UI   = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0Oy9hJmd0Ow=="/> - &amp;lt;/i&amp;gt; - &amp;nbsp; - ##$_A0$##    Text <g id="1">pippo</g>';


        $this->assertEquals( $string_from_UI, $filter->fromLayer0ToLayer2( $database_segment ) );
        $this->assertEquals( $database_segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );
    }

    /**
     **************************
     * Sprintf
     **************************
     */

    public function testSprintf() {
        $channel = new Pipeline( 'hu-HU', 'az-AZ' );
        $channel->addLast( SprintfToPH::class );

        $segment         = 'Legalább 10%-os befejezett foglalás 20%-dir VAGY';
        $seg_transformed = $channel->transform( $segment );

        $this->assertEquals( $segment, $seg_transformed );

        $segment         = 'Legalább 10%-aaa befejezett foglalás 20%-bbb VAGY';
        $seg_transformed = $channel->transform( $segment );

        $this->assertEquals( $segment, $seg_transformed );

        $channel = new Pipeline( 'hu-HU', 'it-IT' );
        $channel->addLast( SprintfToPH::class );

        $segment         = 'Legalább 10%-aaa befejezett foglalás 20%-bbb VAGY';
        $seg_transformed = $channel->transform( $segment );

        $this->assertEquals( $segment, $seg_transformed );
    }

    /**
     **************************
     * Tag XLIFF inside a XLIFF
     **************************
     */

    public function testXliffTagsInsideAXliffFile() {

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $xliffTags = [
                [
                        'db_segment'          => '&lt;g id="1"&gt;',
                        'expected_l1_segment' => '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O2cgaWQ9IjEiJmd0Ow=="/>',
                ],
                [
                        'db_segment'          => '&lt;x id="1"/&gt;',
                        'expected_l1_segment' => '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3ggaWQ9IjEiLyZndDs="/>',
                ],
                [
                        'db_segment'          => '&lt;pc id="1"&gt;',
                        'expected_l1_segment' => '<ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O3BjIGlkPSIxIiZndDs="/>',
                ],
        ];

        foreach ( $xliffTags as $xliffTag ) {
            $db_segment          = $xliffTag[ 'db_segment' ];
            $expected_l1_segment = $xliffTag[ 'expected_l1_segment' ];
            $expected_l2_segment = $xliffTag[ 'expected_l1_segment' ];

            $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
            $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

            $this->assertEquals( $l1_segment, $expected_l1_segment );
            $this->assertEquals( $l2_segment, $expected_l2_segment );

            $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

            $this->assertEquals( $db_segment, $back_to_db );
        }
    }

    /**
     **************************
     * TWIG
     **************************
     */

    public function testTwigFilterWithLessThan() {
        // less than %lt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &lt; 3 %}';
        $expected_l1_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAzICV9"/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l1_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithLessThanAttachedToANumber() {
        // less than %lt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &lt;3 %}';
        $expected_l1_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:eyUgaWYgY291bnQgJmx0OzMgJX0="/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l1_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithGreaterThan() {
        // less than %gt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &gt; 3 %}';
        $expected_l1_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:eyUgaWYgY291bnQgJmd0OyAzICV9"/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l1_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithLessThanAndGreaterThan() {
        // less than %lt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &lt; 10 and &gt; 3 %}';
        $expected_l1_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAxMCBhbmQgJmd0OyAzICV9"/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l1_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithSingleBrackets() {
        $segment  = 'Hi {this strings would not be escaped}. Instead {{this one}} is a valid twig expression. Also {%ciao%} is valid!';
        $expected = 'Hi {this strings would not be escaped}. Instead <ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:e3t0aGlzIG9uZX19"/> is a valid twig expression. Also <ph id="mtc_2" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:eyVjaWFvJX0="/> is valid!';

        $channel = new Pipeline();
        $channel->addLast( TwigToPh::class );
        $seg_transformed = $channel->transform( $segment );
        $this->assertEquals( $expected, $seg_transformed );
    }

    public function testTwigUngreedy() {
        $segment  = 'Dear {{customer.first_name}}, This is {{agent.alias}} with Airbnb.';
        $expected = 'Dear <ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:e3tjdXN0b21lci5maXJzdF9uYW1lfX0="/>, This is <ph id="mtc_2" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:e3thZ2VudC5hbGlhc319"/> with Airbnb.';

        $channel = new Pipeline();
        $channel->addLast( TwigToPh::class );
        $seg_transformed = $channel->transform( $segment );
        $this->assertEquals( $expected, $seg_transformed );
    }

    /**
     **************************
     * <ph> tags test (xliff 2.0)
     **************************
     */

    public function testPhWithoutDataRef() {
        $db_segment = 'We can control who sees %s content when with <ph id="source1" dataRef="source1"/>Visibility Constraints.';
        $Filter     = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET' );

        $expected_l1_segment = 'We can control who sees <ph id="mtc_1" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:JXM="/> content when with <ph id="source1" dataRef="source1"/>Visibility Constraints.';
        $expected_l2_segment = 'We can control who sees <ph id="mtc_1" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:JXM="/> content when with <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF . '" equiv-text="base64:PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>Visibility Constraints.';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $expected_l1_segment, $l1_segment );
        $this->assertEquals( $expected_l2_segment, $l2_segment );

        // Persistance test
        $from_UI              = 'Saame nähtavuse piirangutega kontrollida, <ph id="mtc_1" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:JXM="/> kes sisu näeb .<ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF . '" equiv-text="base64:PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>';
        $exptected_db_segment = 'Saame nähtavuse piirangutega kontrollida, %s kes sisu näeb .<ph id="source1" dataRef="source1"/>';
        $back_to_db_segment   = $Filter->fromLayer2ToLayer0( $from_UI );

        $this->assertEquals( $back_to_db_segment, $exptected_db_segment );
    }

    /**
     * @throws Exception
     */
    public function testsPHPlaceholderWithDataRefForAirbnb() {
        $data_ref_map = [
                'source3' => '&lt;/a&gt;',
                'source4' => '&lt;br&gt;',
                'source5' => '&lt;br&gt;',
                'source1' => '&lt;br&gt;',
                'source2' => '&lt;a href=%s&gt;',
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment              = "Hi %s .";
        $db_translation          = "Tere %s .";
        $expected_l1_segment     = "Hi <ph id=\"mtc_1\" ctype=\"x-sprintf\" equiv-text=\"base64:JXM=\"/> .";
        $expected_l1_translation = "Tere <ph id=\"mtc_1\" ctype=\"x-sprintf\" equiv-text=\"base64:JXM=\"/> .";

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $l2_segment, $expected_l1_segment );
        $this->assertEquals( $l2_translation, $expected_l1_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     * @throws Exception
     */
    public function testPHPlaceholderWithDataRef() {
        $data_ref_map = [
                'source1' => '&lt;br&gt;',
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment              = 'Frase semplice: <ph id="source1" dataRef="source1"/>.';
        $db_translation          = 'Simple sentence: <ph id="source1" dataRef="source1"/>.';
        $expected_l1_segment     = 'Frase semplice: <ph id="source1" dataRef="source1"/>.';
        $expected_l1_translation = 'Simple sentence: <ph id="source1" dataRef="source1"/>.';
        $expected_l2_segment     = 'Frase semplice: <ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyJmd0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>.';
        $expected_l2_translation = 'Simple sentence: <ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyJmd0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $expected_l2_segment, $l2_segment );
        $this->assertEquals( $expected_l2_translation, $l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     **************************
     * <pc> tags test (xliff 2.0)
     **************************
     */

    public function testWithTwoPCTagsWithLessThanBetweenThem() {
        $data_ref_map = [
                "source1" => "<br>",
                "source2" => "<hr>",
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment          = '<pc id="source1" dataRefStart="source1">&lt;<pc id="source2" dataRefStart="source2">Rider /&gt;</pc></pc>';
        $expected_l1_segment = '<pc id="source1" dataRefStart="source1">&lt;<pc id="source2" dataRefStart="source2">Rider /&gt;</pc></pc>';
        $expected_l2_segment = '<ph id="source1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGJyPg==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>&lt;<ph id="source2_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGhyPg==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/>Rider /&gt;<ph id="source2_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGhyPg==" x-orig="PC9wYz4="/><ph id="source1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGJyPg==" x-orig="PC9wYz4="/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $expected_l1_segment, $l1_segment );
        $this->assertEquals( $expected_l2_segment, $l2_segment );
    }

    public function testPCWithComplexDataRefMap() {
        $data_ref_map = [
                "source3" => "<g id=\"jcP-TFFSO2CSsuLt\" ctype=\"x-html-strong\" \/>",
                "source4" => "<g id=\"5StCYYRvqMc0UAz4\" ctype=\"x-html-ul\" \/>",
                "source5" => "<g id=\"99phhJcEQDLHBjeU\" ctype=\"x-html-li\" \/>",
                "source1" => "<g id=\"lpuxniQlIW3KrUyw\" ctype=\"x-html-p\" \/>",
                "source6" => "<g id=\"0HZug1d3LkXJU04E\" ctype=\"x-html-li\" \/>",
                "source2" => "<g id=\"d3TlPtomlUt0Ej1k\" ctype=\"x-html-p\" \/>",
                "source7" => "<g id=\"oZ3oW_0KaicFXFDS\" ctype=\"x-html-li\" \/>"
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment          = '<pc id="source1" dataRefStart="source1">Click the image on the left, read the information and then select the contact type that would replace the red question mark.</pc><pc id="source2" dataRefStart="source2"><pc id="source3" dataRefStart="source3">Things to consider:</pc></pc><pc id="source4" dataRefStart="source4"><pc id="source5" dataRefStart="source5">The rider stated the car had a different tag from another state.</pc><pc id="source6" dataRefStart="source6">The rider stated the car had a color from the one registered in Bliss.</pc><pc id="source7" dataRefStart="source7">The rider can’t tell if the driver matched the profile picture.</pc></pc>';
        $expected_l1_segment = '<pc id="source1" dataRefStart="source1">Click the image on the left, read the information and then select the contact type that would replace the red question mark.</pc><pc id="source2" dataRefStart="source2"><pc id="source3" dataRefStart="source3">Things to consider:</pc></pc><pc id="source4" dataRefStart="source4"><pc id="source5" dataRefStart="source5">The rider stated the car had a different tag from another state.</pc><pc id="source6" dataRefStart="source6">The rider stated the car had a color from the one registered in Bliss.</pc><pc id="source7" dataRefStart="source7">The rider can’t tell if the driver matched the profile picture.</pc></pc>';
        $expected_l2_segment = '<ph id="source1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9ImxwdXhuaVFsSVczS3JVeXciIGN0eXBlPSJ4LWh0bWwtcCIgXC8+" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>Click the image on the left, read the information and then select the contact type that would replace the red question mark.<ph id="source1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9ImxwdXhuaVFsSVczS3JVeXciIGN0eXBlPSJ4LWh0bWwtcCIgXC8+" x-orig="PC9wYz4="/><ph id="source2_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9ImQzVGxQdG9tbFV0MEVqMWsiIGN0eXBlPSJ4LWh0bWwtcCIgXC8+" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/><ph id="source3_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9ImpjUC1URkZTTzJDU3N1THQiIGN0eXBlPSJ4LWh0bWwtc3Ryb25nIiBcLz4=" x-orig="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg=="/>Things to consider:<ph id="source3_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9ImpjUC1URkZTTzJDU3N1THQiIGN0eXBlPSJ4LWh0bWwtc3Ryb25nIiBcLz4=" x-orig="PC9wYz4="/><ph id="source2_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9ImQzVGxQdG9tbFV0MEVqMWsiIGN0eXBlPSJ4LWh0bWwtcCIgXC8+" x-orig="PC9wYz4="/><ph id="source4_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg==" x-orig="PHBjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiPg=="/><ph id="source5_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9Ijk5cGhoSmNFUURMSEJqZVUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg==" x-orig="PHBjIGlkPSJzb3VyY2U1IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTUiPg=="/>The rider stated the car had a different tag from another state.<ph id="source5_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9Ijk5cGhoSmNFUURMSEJqZVUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg==" x-orig="PC9wYz4="/><ph id="source6_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9IjBIWnVnMWQzTGtYSlUwNEUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg==" x-orig="PHBjIGlkPSJzb3VyY2U2IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTYiPg=="/>The rider stated the car had a color from the one registered in Bliss.<ph id="source6_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9IjBIWnVnMWQzTGtYSlUwNEUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg==" x-orig="PC9wYz4="/><ph id="source7_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9Im9aM29XXzBLYWljRlhGRFMiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg==" x-orig="PHBjIGlkPSJzb3VyY2U3IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTciPg=="/>The rider can’t tell if the driver matched the profile picture.<ph id="source7_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9Im9aM29XXzBLYWljRlhGRFMiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg==" x-orig="PC9wYz4="/><ph id="source4_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg==" x-orig="PC9wYz4="/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $expected_l2_segment, $l2_segment );

        $back_to_db_segment_from_l1 = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment_from_l1, $db_segment );

        $back_to_db_segment_from_l2 = $Filter->fromLayer2ToLayer0( $l2_segment );
        $this->assertEquals( $back_to_db_segment_from_l2, $db_segment );

    }

    public function testPCWithoutAnyDataRefMap() {
        $data_ref_map = [];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment          = 'Practice using <pc id="1b" type="fmt" subType="m:b">coaching frameworks</pc> and skills with peers and coaches in a safe learning environment.';
        $expected_l1_segment = 'Practice using <pc id="1b" type="fmt" subType="m:b">coaching frameworks</pc> and skills with peers and coaches in a safe learning environment.';
        $expected_l2_segment = 'Practice using <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PC_OPEN_NO_DATA_REF . '" equiv-text="base64:PHBjIGlkPSIxYiIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOmIiPg=="/>coaching frameworks<ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_PC_CLOSE_NO_DATA_REF . '" equiv-text="base64:PC9wYz4="/> and skills with peers and coaches in a safe learning environment.';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db_segment_from_l1 = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment_from_l1, $db_segment );
    }


    public function testMostSimpleCaseOfPC() {
        $data_ref_map = [
                'd1' => '_',
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment              = 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.';
        $db_translation          = 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">curvise</pc>.';
        $expected_l1_segment     = 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>.';
        $expected_l1_translation = 'Free text containing <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">curvise</pc>.';
        $expected_l2_segment     = 'Testo libero contenente <ph id="1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>corsivo<ph id="1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PC9wYz4="/>.';
        $expected_l2_translation = 'Free text containing <ph id="1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>curvise<ph id="1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PC9wYz4="/>.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $expected_l2_segment, $l2_segment );
        $this->assertEquals( $expected_l2_translation, $l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     * @throws Exception
     */
    public function testDoublePCPlaceholderWithDataRef() {
        $data_ref_map = [
                'd1' => '[',
                'd2' => '](http://repubblica.it)',
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment              = 'Link semplice: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';
        $db_translation          = 'Simple link: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';
        $expected_l1_segment     = 'Link semplice: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';
        $expected_l1_translation = 'Simple link: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';
        $expected_l2_segment     = 'Link semplice: <ph id="1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>La Repubblica<ph id="1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/>.';
        $expected_l2_translation = 'Simple link: <ph id="1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>La Repubblica<ph id="1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/>.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $expected_l2_segment, $l2_segment );
        $this->assertEquals( $expected_l2_translation, $l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     * @throws Exception
     */
    public function testWithPCTagsWithAndWithoutDataRefInTheSameSegment() {

        $data_ref_map = [
                'source1' => 'x',
        ];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment              = 'Text <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><pc id="1u" type="fmt" subType="m:u">link</pc></pc>.';
        $db_translation          = 'Testo <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><pc id="1u" type="fmt" subType="m:u">link</pc></pc>.';
        $expected_l1_segment     = 'Text <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><pc id="1u" type="fmt" subType="m:u">link</pc></pc>.';
        $expected_l1_translation = 'Testo <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><pc id="1u" type="fmt" subType="m:u">link</pc></pc>.';
        $expected_l2_segment     = 'Text <ph id="source1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eA==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiPg=="/><ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PC_OPEN_NO_DATA_REF . '" equiv-text="base64:PHBjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiPg=="/>link<ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_PC_CLOSE_NO_DATA_REF . '" equiv-text="base64:PC9wYz4="/><ph id="source1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eA==" x-orig="PC9wYz4="/>.';
        $expected_l2_translation = 'Testo <ph id="source1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eA==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiPg=="/><ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PC_OPEN_NO_DATA_REF . '" equiv-text="base64:PHBjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiPg=="/>link<ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_PC_CLOSE_NO_DATA_REF . '" equiv-text="base64:PC9wYz4="/><ph id="source1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eA==" x-orig="PC9wYz4="/>.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $expected_l2_segment, $l2_segment );
        $this->assertEquals( $expected_l2_translation, $l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    public function testDontTouchAlreadyParsedPhTags() {
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $segment    = 'Frase semplice: <ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>.';
        $expected   = 'Frase semplice: <ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>.';
        $l2_segment = $Filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $expected, $l2_segment );
    }

    public function testHtmlStringsWithDataTypeAttribute() {
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

//        $db_segment          = '&lt;span data-type="hotspot" class="hotspotOnImage" style="position: relative;display: inline-block;max-width: 100%"&gt;&lt;img src="https://files-storage.easygenerator.com/image/a59cc702-b609-483d-89bd-d65084cde0ed.png" alt="" style="max-width:100%"&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 608px; height: 373px; top: 22px; left: 15px;" data-text="Fysische besmetting" data-id="b0d02fa9-a022-4258-d0a9-b9b1b5deacc0"&gt;&lt;/span&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 591px; height: 340px; top: 55px; left: 675px;" data-text="Besmetting met allergenen" data-id="04e17f73-f836-485d-e2c5-293b0f4ec4ff"&gt;&lt;/span&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 601px; height: 357px; top: 479px; left: 26px;" data-text="Microbiologische besmetting" data-id="6afa3766-4d97-4d08-c3d5-ce9281728d01"&gt;&lt;/span&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 590px; height: 362px; top: 478px; left: 679px;" data-text="Chemische besmetting" data-id="2918ea16-fb49-409e-d33d-4f2bbcbd4d53"&gt;&lt;/span&gt;&lt;/span&gt;';
//        $expected_l1_segment = '<ph id="mtc_1" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gZGF0YS10eXBlPSJob3RzcG90IiBjbGFzcz0iaG90c3BvdE9uSW1hZ2UiIHN0eWxlPSJwb3NpdGlvbjogcmVsYXRpdmU7ZGlzcGxheTogaW5saW5lLWJsb2NrO21heC13aWR0aDogMTAwJSImZ3Q7"/><ph id="mtc_2" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O2ltZyBzcmM9Imh0dHBzOi8vZmlsZXMtc3RvcmFnZS5lYXN5Z2VuZXJhdG9yLmNvbS9pbWFnZS9hNTljYzcwMi1iNjA5LTQ4M2QtODliZC1kNjUwODRjZGUwZWQucG5nIiBhbHQ9IiIgc3R5bGU9Im1heC13aWR0aDoxMDAlIiZndDs="/><ph id="mtc_3" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwOHB4OyBoZWlnaHQ6IDM3M3B4OyB0b3A6IDIycHg7IGxlZnQ6IDE1cHg7IiBkYXRhLXRleHQ9IkZ5c2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9ImIwZDAyZmE5LWEwMjItNDI1OC1kMGE5LWI5YjFiNWRlYWNjMCImZ3Q7"/><ph id="mtc_4" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_5" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MXB4OyBoZWlnaHQ6IDM0MHB4OyB0b3A6IDU1cHg7IGxlZnQ6IDY3NXB4OyIgZGF0YS10ZXh0PSJCZXNtZXR0aW5nIG1ldCBhbGxlcmdlbmVuIiBkYXRhLWlkPSIwNGUxN2Y3My1mODM2LTQ4NWQtZTJjNS0yOTNiMGY0ZWM0ZmYiJmd0Ow=="/><ph id="mtc_6" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_7" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwMXB4OyBoZWlnaHQ6IDM1N3B4OyB0b3A6IDQ3OXB4OyBsZWZ0OiAyNnB4OyIgZGF0YS10ZXh0PSJNaWNyb2Jpb2xvZ2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjZhZmEzNzY2LTRkOTctNGQwOC1jM2Q1LWNlOTI4MTcyOGQwMSImZ3Q7"/><ph id="mtc_8" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_9" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MHB4OyBoZWlnaHQ6IDM2MnB4OyB0b3A6IDQ3OHB4OyBsZWZ0OiA2NzlweDsiIGRhdGEtdGV4dD0iQ2hlbWlzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjI5MThlYTE2LWZiNDktNDA5ZS1kMzNkLTRmMmJiY2JkNGQ1MyImZ3Q7"/><ph id="mtc_10" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_11" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/>';
//        $expected_l2_segment = '&lt;ph id="mtc_1" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gZGF0YS10eXBlPSJob3RzcG90IiBjbGFzcz0iaG90c3BvdE9uSW1hZ2UiIHN0eWxlPSJwb3NpdGlvbjogcmVsYXRpdmU7ZGlzcGxheTogaW5saW5lLWJsb2NrO21heC13aWR0aDogMTAwJSImZ3Q7"/&gt;&lt;ph id="mtc_2" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O2ltZyBzcmM9Imh0dHBzOi8vZmlsZXMtc3RvcmFnZS5lYXN5Z2VuZXJhdG9yLmNvbS9pbWFnZS9hNTljYzcwMi1iNjA5LTQ4M2QtODliZC1kNjUwODRjZGUwZWQucG5nIiBhbHQ9IiIgc3R5bGU9Im1heC13aWR0aDoxMDAlIiZndDs="/&gt;&lt;ph id="mtc_3" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwOHB4OyBoZWlnaHQ6IDM3M3B4OyB0b3A6IDIycHg7IGxlZnQ6IDE1cHg7IiBkYXRhLXRleHQ9IkZ5c2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9ImIwZDAyZmE5LWEwMjItNDI1OC1kMGE5LWI5YjFiNWRlYWNjMCImZ3Q7"/&gt;&lt;ph id="mtc_4" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_5" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MXB4OyBoZWlnaHQ6IDM0MHB4OyB0b3A6IDU1cHg7IGxlZnQ6IDY3NXB4OyIgZGF0YS10ZXh0PSJCZXNtZXR0aW5nIG1ldCBhbGxlcmdlbmVuIiBkYXRhLWlkPSIwNGUxN2Y3My1mODM2LTQ4NWQtZTJjNS0yOTNiMGY0ZWM0ZmYiJmd0Ow=="/&gt;&lt;ph id="mtc_6" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_7" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwMXB4OyBoZWlnaHQ6IDM1N3B4OyB0b3A6IDQ3OXB4OyBsZWZ0OiAyNnB4OyIgZGF0YS10ZXh0PSJNaWNyb2Jpb2xvZ2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjZhZmEzNzY2LTRkOTctNGQwOC1jM2Q1LWNlOTI4MTcyOGQwMSImZ3Q7"/&gt;&lt;ph id="mtc_8" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_9" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MHB4OyBoZWlnaHQ6IDM2MnB4OyB0b3A6IDQ3OHB4OyBsZWZ0OiA2NzlweDsiIGRhdGEtdGV4dD0iQ2hlbWlzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjI5MThlYTE2LWZiNDktNDA5ZS1kMzNkLTRmMmJiY2JkNGQ1MyImZ3Q7"/&gt;&lt;ph id="mtc_10" ctype="'.CTypeEnum::XML.'" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_11" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;';

        $db_segment          = '&lt;span data-type="hotspot" class="hotspotOnImage" style="position: relative;display: inline-block;max-width: 100%"&gt;';
        $expected_l1_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3NwYW4gZGF0YS10eXBlPSJob3RzcG90IiBjbGFzcz0iaG90c3BvdE9uSW1hZ2UiIHN0eWxlPSJwb3NpdGlvbjogcmVsYXRpdmU7ZGlzcGxheTogaW5saW5lLWJsb2NrO21heC13aWR0aDogMTAwJSImZ3Q7"/>';
        $expected_l2_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3NwYW4gZGF0YS10eXBlPSJob3RzcG90IiBjbGFzcz0iaG90c3BvdE9uSW1hZ2UiIHN0eWxlPSJwb3NpdGlvbjogcmVsYXRpdmU7ZGlzcGxheTogaW5saW5lLWJsb2NrO21heC13aWR0aDogMTAwJSImZ3Q7"/>';


        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db_segment = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment, $db_segment );
    }

    public function testPhTagsWithoutDataRef() {
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '<ph id="1j" type="other" subType="m:j"/>';
        $expected_l1_segment = '<ph id="1j" type="other" subType="m:j"/>';
        $expected_l2_segment = '<ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF . '" equiv-text="base64:PHBoIGlkPSIxaiIgdHlwZT0ib3RoZXIiIHN1YlR5cGU9Im06aiIvPg=="/>';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $expected_l1_segment, $l1_segment );
        $this->assertEquals( $expected_l2_segment, $l2_segment );

        $back_to_db_segment = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment, $db_segment );
    }

    /**
     * Test for airbnb
     *
     * @throws Exception
     */
    public function testSmartCount() {

        $Filter = MateCatFilter::getInstance( new FeatureSet( [ new AirbnbFeature() ] ), 'en-EN', 'et-ET', [] );

        $db_segment      = '%{smart_count} discount||||%{smart_count} discounts';
        $segment_from_UI = '<ph id="mtc_1" ctype="' . CTypeEnum::RUBY_ON_RAILS . '" equiv-text="base64:JXtzbWFydF9jb3VudH0="/> discount<ph id="mtc_2" ctype="x-smart-count" equiv-text="base64:fHx8fA=="/><ph id="mtc_3" ctype="' . CTypeEnum::RUBY_ON_RAILS . '" equiv-text="base64:JXtzbWFydF9jb3VudH0="/> discounts';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );

        $this->assertEquals( $db_segment, $Filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $Filter->fromLayer0ToLayer1( $db_segment ) );

    }

    /**
     **************************
     * Uber pipeline
     **************************
     */

    /**
     * Test for skyscanner
     * (promoted to global behavior)
     *
     * @throws Exception
     */
    public function testSinglePercentageSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax %this_is_a_variable% is no more valid';
        $segment_from_UI = 'This syntax %this_is_a_variable% is no more valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     * Test for skyscanner
     * (promoted to global behavior)
     *
     * @throws Exception
     */
    public function testDoublePercentageSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax %%customer.first_name%% is still valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" ctype="' . CTypeEnum::PERCENTAGES . '" equiv-text="base64:JSVjdXN0b21lci5maXJzdF9uYW1lJSU="/> is still valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     **************************
     * Skyscanner pipeline
     * (promoted to global behavior)
     **************************
     */

    public function testSingleSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this is a variable@ is not valid';
        $segment_from_UI = 'This syntax @this is a variable@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this_is_a_variable@ is no more valid';
        $segment_from_UI = 'This syntax @this_is_a_variable@ is no more valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testDoubleSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this is a variable@@ is not valid';
        $segment_from_UI = 'This syntax @@this is a variable@@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this_is_a_variable@@ is valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" ctype="' . CTypeEnum::SNAILS . '" equiv-text="base64:QEB0aGlzX2lzX2FfdmFyaWFibGVAQA=="/> is valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentDoubleCurlyBracketsSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Save up to {{|discount|}} with these hotels';
        $segment_from_UI = 'Save up to <ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:e3t8ZGlzY291bnR8fX0="/> with these hotels';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testVariablesSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Save up to %{{|discount|}} with these hotels';
        $segment_from_UI = 'Save up to <ph id="mtc_1" ctype="' . CTypeEnum::PERCENT_VARIABLE . '" equiv-text="base64:JXt7fGRpc2NvdW50fH19"/> with these hotels';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string: %@ is a IOS placeholder %@.';
        $segment_from_UI = 'This string: <ph id="mtc_1" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JUA="/> is a IOS placeholder <ph id="mtc_2" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JUA="/>.';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentNumberSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string: %12$@ is a IOS placeholder %1$@ %14343$@';
        $segment_from_UI = 'This string: <ph id="mtc_1" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JTEyJEA="/> is a IOS placeholder <ph id="mtc_2" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JTEkQA=="/> <ph id="mtc_3" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JTE0MzQzJEA="/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testWithMixedPercentTags() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string contains all these tags: %-4d %@ %12$@ ​%{{|discount|}} {% if count &lt; 3 %} but not this %placeholder%';
        $segment_from_UI = 'This string contains all these tags: <ph id="mtc_1" ctype="' . CTypeEnum::SPRINTF . '" equiv-text="base64:JS00ZA=="/> <ph id="mtc_2" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JUA="/> <ph id="mtc_3" ctype="' . CTypeEnum::OBJECTIVE_C_NSSTRING . '" equiv-text="base64:JTEyJEA="/> ​<ph id="mtc_4" ctype="' . CTypeEnum::PERCENT_VARIABLE . '" equiv-text="base64:JXt7fGRpc2NvdW50fH19"/> <ph id="mtc_5" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAzICV9"/> but not this %placeholder%';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     **************************
     * Lastminute pipeline
     * (promoted to global behavior)
     **************************
     */

    /**
     * @return void
     * @throws Exception
     */
    public function testWithDoubleSquareBrackets() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string contains [[placeholder]]';
        $segment_from_UI = 'This string contains <ph id="mtc_1" ctype="' . CTypeEnum::DOUBLE_SQUARE_BRACKETS . '" equiv-text="base64:W1twbGFjZWhvbGRlcl1d"/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }


//    public function testWithDoubleUnderscore() {
//        $filter = $this->getFilterInstance();
//
//        $db_segment      = 'This string contains __placeholder_one__';
//        $segment_from_UI = 'This string contains <ph id="mtc_1" ctype="' . CTypeEnum::DOUBLE_UNDERSCORE . '" equiv-text="base64:X19wbGFjZWhvbGRlcl9vbmVfXw=="/>';
//
//        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
//        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
//    }

    /**
     * @return void
     * @throws Exception
     */
    public function testWithDollarCurlyBrackets() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string contains ${placeholder_one}';
        $segment_from_UI = 'This string contains <ph id="mtc_1" ctype="' . CTypeEnum::DOLLAR_CURLY_BRACKETS . '" equiv-text="base64:JHtwbGFjZWhvbGRlcl9vbmV9"/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testWithSquareSprintf() {
        $filter = $this->getFilterInstance();

        $tags = [
                '[%s]',
                '[%1$s]',
                '[%222$s]',
                '[%s:name]',
                '[%s:placeholder]',
                '[%s:place_holder]',
                '[%i]',
                '[%1$i]',
                '[%222$i]',
                '[%i:name]',
                '[%i:placeholder]',
                '[%i:place_holder]',
                '[%f]',
                '[%.2f]',
                '[%.2332f]',
                '[%1$.2f]',
                '[%23$.24343f]',
                '[%.222f:name]',
                '[%.2f:placeholder]',
                '[%.2f:place_holder]',
                '[%key_id:1234%]',
                '[%test:1234%]',
        ];

        foreach ( $tags as $tag ) {
            $db_segment      = 'Ciao ' . $tag;
            $segment_from_UI = 'Ciao <ph id="mtc_1" ctype="' . CTypeEnum::SQUARE_SPRINTF . '" equiv-text="base64:' . base64_encode( $tag ) . '"/>';

            $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
            $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testTagXWithEquivTextShouldBeHandled() {

        $filter = $this->getFilterInstance();

        $db_segment = 'Last Successfully Logged In At: <x id="1" equiv-text="&lt;ph id=&quot;3&quot; disp=&quot;{{data}}&quot; dataRef=&quot;d1&quot; /&gt;"/>';
        $layer1And2 = 'Last Successfully Logged In At: <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjEiIGVxdWl2LXRleHQ9IiZsdDtwaCBpZD0mcXVvdDszJnF1b3Q7IGRpc3A9JnF1b3Q7e3tkYXRhfX0mcXVvdDsgZGF0YVJlZj0mcXVvdDtkMSZxdW90OyAvJmd0OyIvPg==" equiv-text="base64:Jmx0O3BoIGlkPSZxdW90OzMmcXVvdDsgZGlzcD0mcXVvdDt7e2RhdGF9fSZxdW90OyBkYXRhUmVmPSZxdW90O2QxJnF1b3Q7IC8mZ3Q7"/>';

        $this->assertEquals( $layer1And2, $filter->fromLayer0ToLayer1( $db_segment ) );
        $this->assertEquals( $layer1And2, $filter->fromLayer0ToLayer2( $db_segment ) );

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $layer1And2 ) );
        $this->assertEquals( $db_segment, $filter->fromLayer2ToLayer0( $layer1And2 ) );

    }

    /**
     * @return void
     * @throws Exception
     */
    public function testXliffInXliffWithoutId() {

        $filter = $this->getFilterInstance();

        $db_segment = 'Test &lt;X&gt; and &lt;/X&gt; fine.';
        $layer1And2 = 'Test <ph id="mtc_1" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0O1gmZ3Q7"/> and <ph id="mtc_2" ctype="' . CTypeEnum::XML . '" equiv-text="base64:Jmx0Oy9YJmd0Ow=="/> fine.';

        $this->assertEquals( $layer1And2, $filter->fromLayer0ToLayer1( $db_segment ) );
        $this->assertEquals( $layer1And2, $filter->fromLayer0ToLayer2( $db_segment ) );

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $layer1And2 ) );
        $this->assertEquals( $db_segment, $filter->fromLayer2ToLayer0( $layer1And2 ) );

    }

    /**
     * @throws Exception
     */
    public function testSmartCounts() {

        $pipeline = new Pipeline();
        $pipeline->addLast( SmartCounts::class );

        $db_segment = "Test||||and |||| fine.";

        $transformed = $pipeline->transform( $db_segment );
        $this->assertEquals( 'Test<ph id="mtc_1" ctype="' . CTypeEnum::SMART_COUNT . '" equiv-text="base64:fHx8fA=="/>and <ph id="mtc_2" ctype="x-smart-count" equiv-text="base64:fHx8fA=="/> fine.', $transformed );

        // revert
        $filter = $this->getFilterInstance();

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $transformed ) );
        $this->assertEquals( $db_segment, $filter->fromLayer2ToLayer0( $transformed ) );

    }

    /**
     * @throws Exception
     */
    public function testHtmlDoubleEncodedInXML() {
        $filter = $this->getFilterInstance();

        $segment    = '<g id="123">&lt;code&gt; &amp;lt;strong&amp;gt; THIS IS TREATED AS TEXT CONTENT EVEN IF IT IS AN HTML &amp;lt;/strong&amp;gt; &lt;/code&gt;</g>';
        $expectedL1 = '<g id="123"><ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O2NvZGUmZ3Q7"/> &amp;lt;strong&amp;gt; THIS IS TREATED AS TEXT CONTENT EVEN IF IT IS AN HTML &amp;lt;/strong&amp;gt; <ph id="mtc_2" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0Oy9jb2RlJmd0Ow=="/></g>';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );

        $this->assertEquals( $expectedL1, $segmentL1 );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

    }

    /**
     * @return void
     * @throws Exception
     */
    public function testOriginalPhWithHtmlAttributes() {

        $filter = $this->getFilterInstance();

        $segment   = 'Test <ph id="PlaceHolder1" equiv-text="&lt;ph id=&quot;3&quot; disp=&quot;{{data}}&quot; dataRef=&quot;d1&quot; /&gt;"/> locked.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = 'Test <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="PHBoIGlkPSJQbGFjZUhvbGRlcjEiIGVxdWl2LXRleHQ9IiZsdDtwaCBpZD0mcXVvdDszJnF1b3Q7IGRpc3A9JnF1b3Q7e3tkYXRhfX0mcXVvdDsgZGF0YVJlZj0mcXVvdDtkMSZxdW90OyAvJmd0OyIvPg==" equiv-text="base64:Jmx0O3BoIGlkPSZxdW90OzMmcXVvdDsgZGlzcD0mcXVvdDt7e2RhdGF9fSZxdW90OyBkYXRhUmVmPSZxdW90O2QxJnF1b3Q7IC8mZ3Q7"/> locked.';

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testRealCaseMxliff() {

        $filter = $this->getFilterInstance();

        $segment   = 'For the site <ph id="4" disp="{{siteId}}" dataRef="d2"/><x id="2" equiv-text="&lt;ph id=&quot;4&quot; disp=&quot;{{siteId}}&quot; dataRef=&quot;d2&quot; /&gt;"/><x id="3"/> group id <x id="4"/><x id="5"/><x id="6"/> is already associated.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = 'For the site <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF . '" equiv-text="base64:PHBoIGlkPSI0IiBkaXNwPSJ7e3NpdGVJZH19IiBkYXRhUmVmPSJkMiIvPg=="/><ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjIiIGVxdWl2LXRleHQ9IiZsdDtwaCBpZD0mcXVvdDs0JnF1b3Q7IGRpc3A9JnF1b3Q7e3tzaXRlSWR9fSZxdW90OyBkYXRhUmVmPSZxdW90O2QyJnF1b3Q7IC8mZ3Q7Ii8+" equiv-text="base64:Jmx0O3BoIGlkPSZxdW90OzQmcXVvdDsgZGlzcD0mcXVvdDt7e3NpdGVJZH19JnF1b3Q7IGRhdGFSZWY9JnF1b3Q7ZDImcXVvdDsgLyZndDs="/><x id="3"/> group id <x id="4"/><x id="5"/><x id="6"/> is already associated.';

        $this->assertEquals( $string_from_UI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testXtagAndXtagWithEquivText() {

        $filter = $this->getFilterInstance();

        $segment   = 'Click <x id="1"/>Create Site Admin<x id="2" equiv-text="bold"/><x id="3" equiv-text="italic"/>administration<x id="4" equiv-text="italic"/> site.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = 'Click <x id="1"/>Create Site Admin<ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjIiIGVxdWl2LXRleHQ9ImJvbGQiLz4=" equiv-text="base64:Ym9sZA=="/><ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjMiIGVxdWl2LXRleHQ9Iml0YWxpYyIvPg==" equiv-text="base64:aXRhbGlj"/>administration<ph id="mtc_3" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjQiIGVxdWl2LXRleHQ9Iml0YWxpYyIvPg==" equiv-text="base64:aXRhbGlj"/> site.';

        $this->assertEquals( $segmentL2, $string_from_UI );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testXtagAndXtagWithEquivTextWithRandomAttributeOrder() {

        $filter = $this->getFilterInstance();

        $segment   = 'Click <x id="1"/>Create Site Admin<x id="2" equiv-text="bold"/><x equiv-text="italic" id="3"/>administration<x equiv-text="italic" x-attribute="pippo-attribute" id="4"/> site.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = 'Click <x id="1"/>Create Site Admin<ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjIiIGVxdWl2LXRleHQ9ImJvbGQiLz4=" equiv-text="base64:Ym9sZA=="/><ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggZXF1aXYtdGV4dD0iaXRhbGljIiBpZD0iMyIvPg==" equiv-text="base64:aXRhbGlj"/>administration<ph id="mtc_3" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggZXF1aXYtdGV4dD0iaXRhbGljIiB4LWF0dHJpYnV0ZT0icGlwcG8tYXR0cmlidXRlIiBpZD0iNCIvPg==" equiv-text="base64:aXRhbGlj"/> site.';

        $this->assertEquals( $string_from_UI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws Exception
     */
    public function testRandomPhAndXTags() {

        $filter = $this->getFilterInstance();

        $segment   = 'Click <x id="1"/>Create <ph id="PlaceHolder1" equiv-text="&lt;ph id=&quot;3&quot; disp=&quot;{{data}}&quot; dataRef=&quot;d1&quot; /&gt;"/>Site Admin<x id="2" equiv-text="bold"/><ph id="111"/><x id="3" equiv-text="italic"/>administration<x id="4" equiv-text="italic"/> site.';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = 'Click <x id="1"/>Create <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="PHBoIGlkPSJQbGFjZUhvbGRlcjEiIGVxdWl2LXRleHQ9IiZsdDtwaCBpZD0mcXVvdDszJnF1b3Q7IGRpc3A9JnF1b3Q7e3tkYXRhfX0mcXVvdDsgZGF0YVJlZj0mcXVvdDtkMSZxdW90OyAvJmd0OyIvPg==" equiv-text="base64:Jmx0O3BoIGlkPSZxdW90OzMmcXVvdDsgZGlzcD0mcXVvdDt7e2RhdGF9fSZxdW90OyBkYXRhUmVmPSZxdW90O2QxJnF1b3Q7IC8mZ3Q7"/>Site Admin<ph id="mtc_2" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjIiIGVxdWl2LXRleHQ9ImJvbGQiLz4=" equiv-text="base64:Ym9sZA=="/><ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_PH_OR_NOT_DATA_REF . '" equiv-text="base64:PHBoIGlkPSIxMTEiLz4="/><ph id="mtc_3" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjMiIGVxdWl2LXRleHQ9Iml0YWxpYyIvPg==" equiv-text="base64:aXRhbGlj"/>administration<ph id="mtc_4" ctype="' . CTypeEnum::ORIGINAL_X . '" x-orig="PHggaWQ9IjQiIGVxdWl2LXRleHQ9Iml0YWxpYyIvPg==" equiv-text="base64:aXRhbGlj"/> site.';

        $this->assertEquals( $string_from_UI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @Test
     * @return void
     * @throws Exception
     */
    public function testFromUIConversion() {

        $data_ref_map = [
                'd1' => '[',
                'd2' => '](http://repubblica.it)',
        ];

        $filter = $this->getFilterInstance( $data_ref_map );


        $segment = 'Link semplice: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';

        $segmentL0 = $filter->fromRawXliffToLayer0( $segment );
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );

        $segmentL0_2 = $filter->fromLayer1ToLayer0( $segmentL1 );

        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segmentL0, $segmentL0_2 );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

        $string_from_UI = 'Link semplice: <ph id="1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>La Repubblica<ph id="1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/>.';
        $this->assertEquals( $string_from_UI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @test
     * @throws Exception
     */
    public function layer1ShouldWorkWithMalformedPhTags() {

        $filter  = $this->getFilterInstance();
        $segment = 'not <ph id="1" dataRef="pippo"> valid';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $this->assertEquals( $segment, $segmentL1 );
    }

    /**
     * @test
     * @throws Exception
     */
    public function layer2SShouldWorkWithMalformedPhTags() {

        $filter  = $this->getFilterInstance( [ 'yyy' => 'xxx' ] );
        $segment = 'not <ph id="1" dataRef="pippo"> valid';

        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );
        $this->assertEquals( $segment, $segmentL2 );
    }

    /**
     * @test
     */
    public function when_empty_equiv_text_shgould_put_NULL_in_converted_ph() {
        // sample test
        $map = [
                "source2" => '${RIDER}',
                "source3" => '&amp;lt;br&amp;gt;',
        ];

        $filter = $this->getFilterInstance( $map );

        $string   = 'Hola <ph id="source1" dataRef="source1" equiv-text=""/>';
        $expected = 'Hola <ph id="mtc_1" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIiBlcXVpdi10ZXh0PSIiLz4=" equiv-text="base64:TlVMTA=="/>';

        $layer2        = $filter->fromLayer0ToLayer2( $string );
        $convertedBack = $filter->fromLayer2ToLayer0( $layer2 );

        $this->assertEquals( $expected, $layer2 );
        $this->assertEquals( $string, $convertedBack );

    }

    /**
     * @test
     */
    public function should_work_with_real_pc_case() {

        $segment    = '<pc id="1b" type="fmt" subType="m:b">Ready to get started?</pc>';
        $segment_UI = '<ph id="mtc_1" ctype="x-original_pc_open" equiv-text="base64:PHBjIGlkPSIxYiIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOmIiPg=="/>Ready to get started?<ph id="mtc_2" ctype="x-original_pc_close" equiv-text="base64:PC9wYz4="/>';

        $filter = $this->getFilterInstance( [] );

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );


        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segment_UI, $segmentL2 );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    /**
     * @test
     * @throws Exception
     */
    public function testRubyOnRails() {
        $segment = 'For the %{first_ruby_variable} site %{{second_bnb_variable}}, is ok.';
        $forUI   = 'For the <ph id="mtc_1" ctype="' . CTypeEnum::RUBY_ON_RAILS . '" equiv-text="base64:JXtmaXJzdF9ydWJ5X3ZhcmlhYmxlfQ=="/> site <ph id="mtc_2" ctype="' . CTypeEnum::PERCENT_VARIABLE . '" equiv-text="base64:JXt7c2Vjb25kX2JuYl92YXJpYWJsZX19"/>, is ok.';

        $filter    = $this->getFilterInstance();
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );


        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $forUI, $segmentL2 );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    /**
     * @test
     * @throws Exception
     */
    public function nested_pc_tags_real_case() {

        $refMap = [
                'source1' => '&lt;w:hyperlink r:id="rId25"&gt;&lt;/w:hyperlink&gt;',
                'source2' => '&lt;w:r&gt;&lt;w:rPr&gt;&lt;w:color w:val="1A1A1A"&gt;&lt;/w:color&gt;&lt;/w:rPr&gt;&lt;w:t&gt;&lt;/w:t&gt;&lt;/w:r&gt;',
        ];

        $filter = $this->getFilterInstance( $refMap );

        $segment    = '<pc id="source1" dataRefStart="source1"><pc id="1u" type="fmt" subType="m:u">Crea una carpeta separada en tu Cuenta de ahorros Square para impuestos</pc></pc><pc id="source2" dataRefStart="source2"> y automáticamente contribuye un porcentaje de cada venta de Square.</pc>';
        $sentFromUI = '<ph id="source1_1" ctype="x-pc_open_data_ref" equiv-text="base64:Jmx0O3c6aHlwZXJsaW5rIHI6aWQ9InJJZDI1IiZndDsmbHQ7L3c6aHlwZXJsaW5rJmd0Ow==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/><ph id="mtc_1" ctype="x-original_pc_open" equiv-text="base64:PHBjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiPg=="/>Crea una carpeta separada en tu Cuenta de ahorros Square para impuestos<ph id="mtc_2" ctype="x-original_pc_close" equiv-text="base64:PC9wYz4="/><ph id="source1_2" ctype="x-pc_close_data_ref" equiv-text="base64:Jmx0O3c6aHlwZXJsaW5rIHI6aWQ9InJJZDI1IiZndDsmbHQ7L3c6aHlwZXJsaW5rJmd0Ow==" x-orig="PC9wYz4="/><ph id="source2_1" ctype="x-pc_open_data_ref" equiv-text="base64:Jmx0O3c6ciZndDsmbHQ7dzpyUHImZ3Q7Jmx0O3c6Y29sb3Igdzp2YWw9IjFBMUExQSImZ3Q7Jmx0Oy93OmNvbG9yJmd0OyZsdDsvdzpyUHImZ3Q7Jmx0O3c6dCZndDsmbHQ7L3c6dCZndDsmbHQ7L3c6ciZndDs=" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/> y automáticamente contribuye un porcentaje de cada venta de Square.<ph id="source2_2" ctype="x-pc_close_data_ref" equiv-text="base64:Jmx0O3c6ciZndDsmbHQ7dzpyUHImZ3Q7Jmx0O3c6Y29sb3Igdzp2YWw9IjFBMUExQSImZ3Q7Jmx0Oy93OmNvbG9yJmd0OyZsdDsvdzpyUHImZ3Q7Jmx0O3c6dCZndDsmbHQ7L3c6dCZndDsmbHQ7L3c6ciZndDs=" x-orig="PC9wYz4="/>';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        // layer 2
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $sentFromUI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $sentFromUI ) );

        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    /**
     * @test
     * @throws Exception
     */
    public function nested_pc_tags_real_case2() {

        $refMap = [
                'source3'  => '&lt;bpt ctype="x-style" id="span_2"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source34' => '&lt;g ctype="x-text" id="text_13" /&gt;',
                'source45' => '&lt;bpt ctype="x-block" id="block_17" /&gt;',
                'source56' => '&lt;g ctype="x-text" id="text_22" /&gt;',
                'source30' => '&lt;ph id="generic_12"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="72" TrailingMargin="0" FirstLineMargin="36" Justification="Left" ListLevel="1" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" Color="#000000" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
                'source41' => '&lt;g ctype="x-text" id="text_16" /&gt;',
                'source52' => '&lt;ept id="block_17" /&gt;',
                'source17' => '&lt;g ctype="x-text" id="text_7" /&gt;',
                'source28' => '&lt;ept id="block_8" /&gt;',
                'source8'  => '&lt;bpt ctype="x-block" id="block_3" /&gt;',
                'source39' => '&lt;bpt ctype="x-style" id="span_16"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source13' => '&lt;bpt ctype="x-style" id="span_6"&gt;&lt;Style FontSize="11" FontIsBold="True" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source24' => '&lt;bpt ctype="x-style" id="span_10"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source4'  => '&lt;g ctype="x-text" id="text_2" /&gt;',
                'source35' => '&lt;ept id="span_13" /&gt;',
                'source46' => '&lt;ph id="generic_18"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="72" TrailingMargin="0" FirstLineMargin="36" Justification="Left" ListLevel="1" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" Color="#000000" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
                'source57' => '&lt;ept id="span_22" /&gt;',
                'source20' => '&lt;ept id="span_7" /&gt;',
                'source31' => '&lt;bpt ctype="x-style" id="span_13"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source42' => '&lt;g ctype="x-text" id="text_16" /&gt;',
                'source53' => '&lt;bpt ctype="x-block" id="block_20" /&gt;',
                'source18' => '&lt;g ctype="x-text" id="text_7" /&gt;',
                'source29' => '&lt;bpt ctype="x-block" id="block_11" /&gt;',
                'source9'  => '&lt;ph id="generic_4"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="0" TrailingMargin="0" FirstLineMargin="0" Justification="Left" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" Color="#000000" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
                'source14' => '&lt;g ctype="x-text" id="text_6" /&gt;',
                'source25' => '&lt;g ctype="x-text" id="text_10" /&gt;',
                'source5'  => '&lt;g ctype="x-text" id="text_2" /&gt;',
                'source36' => '&lt;ept id="block_11" /&gt;',
                'source47' => '&lt;bpt ctype="x-style" id="span_19"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source58' => '&lt;ept id="block_20" /&gt;',
                'source10' => '&lt;bpt ctype="x-style" id="span_5"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source21' => '&lt;ept id="block_3" /&gt;',
                'source1'  => '&lt;bpt ctype="x-block" id="block_0" /&gt;',
                'source32' => '&lt;g ctype="x-text" id="text_13" /&gt;',
                'source43' => '&lt;ept id="span_16" /&gt;',
                'source54' => '&lt;ph id="generic_21"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="0" TrailingMargin="0" FirstLineMargin="0" Justification="Left" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
                'source50' => '&lt;g ctype="x-text" id="text_19" /&gt;',
                'source19' => '&lt;g ctype="x-text" id="text_7" /&gt;',
                'source15' => '&lt;ept id="span_6" /&gt;',
                'source26' => '&lt;g ctype="x-text" id="text_10" /&gt;',
                'source6'  => '&lt;ept id="span_2" /&gt;',
                'source37' => '&lt;bpt ctype="x-block" id="block_14" /&gt;',
                'source48' => '&lt;g ctype="x-text" id="text_19" /&gt;',
                'source11' => '&lt;g ctype="x-text" id="text_5" /&gt;',
                'source22' => '&lt;bpt ctype="x-block" id="block_8" /&gt;',
                'source2'  => '&lt;ph id="generic_1"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="0" TrailingMargin="0" FirstLineMargin="0" Justification="Left" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" Color="#000000" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
                'source33' => '&lt;g ctype="x-text" id="text_13" /&gt;',
                'source44' => '&lt;ept id="block_14" /&gt;',
                'source55' => '&lt;bpt ctype="x-style" id="span_22"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source40' => '&lt;g ctype="x-text" id="text_16" /&gt;',
                'source51' => '&lt;ept id="span_19" /&gt;',
                'source16' => '&lt;bpt ctype="x-style" id="span_7"&gt;&lt;Style FontSize="11" ForegroundColor="lt1,00" LinkColor="lt1,00" /&gt;&lt;/bpt&gt;',
                'source27' => '&lt;ept id="span_10" /&gt;',
                'source7'  => '&lt;ept id="block_0" /&gt;',
                'source38' => '&lt;ph id="generic_15"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="72" TrailingMargin="0" FirstLineMargin="36" Justification="Left" ListLevel="1" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" Color="#000000" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
                'source49' => '&lt;g ctype="x-text" id="text_19" /&gt;',
                'source12' => '&lt;ept id="span_5" /&gt;',
                'source23' => '&lt;ph id="generic_9"&gt;&lt;Style FlowDirection="LeftToRight" LeadingMargin="0" TrailingMargin="0" FirstLineMargin="0" Justification="Left" LineSpacingRule="Multiple" LineSpacing="21" SpacingBefore="0" SpacingAfter="6"&gt;&lt;ListStyle ListType="None" ListTypeFormat="Parentheses" Color="#000000" BulletFont="Arial" /&gt;&lt;/Style&gt;&lt;/ph&gt;',
        ];


        $filter = $this->getFilterInstance( $refMap );

        $segment = '<pc id="source5" dataRefStart="source5"></pc><ph id="source6" dataRef="source6"/><ph id="source7" dataRef="source7"/><ph id="source8" dataRef="source8"/><ph id="source9" dataRef="source9"/><ph id="source10" dataRef="source10"/><pc id="source11" dataRefStart="source11">Uber is committed to our employees, contractors, customers, and to the communities where we do business by </pc><ph id="source12" dataRef="source12"/><ph id="source13" dataRef="source13"/><pc id="source14" dataRefStart="source14">putting people first</pc><ph id="source15" dataRef="source15"/><ph id="source16" dataRef="source16"/><pc id="source17" dataRefStart="source17">.</pc>';

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        // layer 2
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }


}
