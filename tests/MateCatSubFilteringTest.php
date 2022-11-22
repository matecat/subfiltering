<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\LtGtDecode;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Tests\Mocks\Features\UberFeature;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use Matecat\SubFiltering\Utils\CatUtils;
use PHPUnit\Framework\TestCase;

class MateCatSubFilteringTest extends TestCase
{
    /**
     * @return \Matecat\SubFiltering\AbstractFilter
     * @throws \Exception
     */
    private function getFilterInstance()
    {
        MateCatFilter::destroyInstance(); // for isolation test

        return MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT' );
    }

    /**
     * @throws \Exception
     */
    public function testSimpleString() {
        $filter = $this->getFilterInstance();

        $segment   = "The house is red.";
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $tmpLayer2 = ( new LtGtDecode() )->transform( $segmentL2 );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $tmpLayer2 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $tmpLayer2 ) );
    }

    /**
     * @throws \Exception
     */
    public function testHtmlInXML() {
        $filter = $this->getFilterInstance();

        $segment   = '&lt;p&gt; Airbnb &amp;amp; Co. &amp;lt; <x id="1"> &lt;strong&gt;Use professional tools&lt;/strong&gt; in your &lt;a href="/users/settings?test=123&amp;amp;ciccio=1" target="_blank"&gt;';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
    }

    /**
     * @throws \Exception
     */
    public function testUIHtmlInXML() {
        $filter = $this->getFilterInstance();

        $segment   = '&lt;p&gt; Airbnb &amp;amp; Co. &amp;lt; &lt;strong&gt;Use professional tools&lt;/strong&gt; in your &lt;a href="/users/settings?test=123&amp;amp;ciccio=1" target="_blank"&gt;';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        //Start test
        $string_from_UI = '<ph id="mtc_1" equiv-text="base64:Jmx0O3AmZ3Q7"/> Airbnb &amp; Co. &lt; <ph id="mtc_2" equiv-text="base64:Jmx0O3N0cm9uZyZndDs="/>Use professional tools<ph id="mtc_3" equiv-text="base64:Jmx0Oy9zdHJvbmcmZ3Q7"/> in your <ph id="mtc_4" equiv-text="base64:Jmx0O2EgaHJlZj0iL3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDthbXA7Y2ljY2lvPTEiIHRhcmdldD0iX2JsYW5rIiZndDs="/>';

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws \Exception
     */
    public function testComplexUrls() {
        $filter = $this->getFilterInstance();

        $fromUi       = '<ph id="mtc_14" equiv-text="base64:Jmx0O2EgaHJlZj0iaHR0cHM6Ly9hdXRoLnViZXIuY29tL2xvZ2luLz9icmVlemVfbG9jYWxfem9uZT1kY2ExJmFtcDthbXA7bmV4dF91cmw9aHR0cHMlM0ElMkYlMkZkcml2ZXJzLnViZXIuY29tJTJGcDMlMkYmYW1wO2FtcDtzdGF0ZT00MElLeF9YR0N1OXRobEtrSUkxUmRCOFlhUVRVY0g1aE1uVnllWXJCN0lBJTNEIiZndDs="/>Partner Dashboard<ph id="mtc_15" equiv-text="base64:Jmx0Oy9hJmd0Ow=="/> to match the payment document you uploaded';
        $expectedToDb = '&lt;a href="https://auth.uber.com/login/?breeze_local_zone=dca1&amp;amp;next_url=https%3A%2F%2Fdrivers.uber.com%2Fp3%2F&amp;amp;state=40IKx_XGCu9thlKkII1RdB8YaQTUcH5hMnVyeYrB7IA%3D"&gt;Partner Dashboard&lt;/a&gt; to match the payment document you uploaded';
        $toDb         = $filter->fromLayer1ToLayer0( $fromUi );

        $this->assertEquals( $toDb, $expectedToDb );
    }

    /**
     * @throws \Exception
     */
    public function testComplexXML() {
        $filter = $this->getFilterInstance();

        $segment   = '&lt;p&gt; Airbnb &amp;amp; Co. &amp;amp; <ph id="PlaceHolder1" equiv-text="{0}"/> &amp;quot; &amp;apos;<ph id="PlaceHolder2" equiv-text="/users/settings?test=123&amp;ciccio=1"/> &lt;a href="/users/settings?test=123&amp;amp;ciccio=1" target="_blank"&gt;';
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $string_from_UI = '<ph id="mtc_1" equiv-text="base64:Jmx0O3AmZ3Q7"/> Airbnb &amp; Co. &amp; <ph id="PlaceHolder1" equiv-text="base64:ezB9"/> " \'<ph id="PlaceHolder2" equiv-text="base64:L3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDtjaWNjaW89MQ=="/> <ph id="mtc_2" equiv-text="base64:Jmx0O2EgaHJlZj0iL3VzZXJzL3NldHRpbmdzP3Rlc3Q9MTIzJmFtcDthbXA7Y2ljY2lvPTEiIHRhcmdldD0iX2JsYW5rIiZndDs="/>';

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );

        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $string_from_UI ) );

    }

    /**
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
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
        $segment_to_UI = $string_from_UI = '5 tips for creating a great ' . CatUtils::nbspPlaceholder . ' guide';

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
     * @throws \Exception
     */
    public function testHTMLFromLayer2() {
        $filter           = $this->getFilterInstance();
        $expected_segment = '&lt;b&gt;de %1$s, &lt;/b&gt;que';

        //Start test
        $string_from_UI = '&lt;b&gt;de <ph id="mtc_1" equiv-text="base64:JTEkcw=="/>, &lt;/b&gt;que';
        $this->assertEquals( $expected_segment, $filter->fromLayer2ToLayer0( $string_from_UI ) );

        $string_in_layer1 = '<ph id="mtc_1" equiv-text="base64:Jmx0O2ImZ3Q7"/>de <ph id="mtc_2" equiv-text="base64:JTEkcw=="/>, <ph id="mtc_3" equiv-text="base64:Jmx0Oy9iJmd0Ow=="/>que';
        $this->assertEquals( $expected_segment, $filter->fromLayer1ToLayer0( $string_in_layer1 ) );

    }

    /**
     **************************
     * Sprintf
     **************************
     */

    public function testSprintf()
    {
        $channel = new Pipeline( 'hu-HU', 'az-AZ' );
        $channel->addLast( new SprintfToPH() );

        $segment         = 'Legalább 10%-os befejezett foglalás 20%-dir VAGY';
        $seg_transformed = $channel->transform( $segment );

        $this->assertEquals( $segment, $seg_transformed );

        $segment         = 'Legalább 10%-aaa befejezett foglalás 20%-bbb VAGY';
        $seg_transformed = $channel->transform( $segment );

        $this->assertEquals( $segment, $seg_transformed );

        $channel = new Pipeline( 'hu-HU', 'it-IT' );
        $channel->addLast( new SprintfToPH() );

        $segment         = 'Legalább 10%-aaa befejezett foglalás 20%-bbb VAGY';
        $seg_transformed = $channel->transform( $segment );

        $this->assertEquals( $segment, $seg_transformed );
    }

    /**
     **************************
     * Tag <x> <g>
     **************************
     */

    public function testFilterWithTagGAndX() {

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '&lt;g id="1"&gt;';
        $expected_l1_segment = '<ph id="mtc_1" equiv-text="base64:Jmx0O2cgaWQ9IjEiJmd0Ow=="/>';
        $expected_l2_segment = '&lt;ph id="mtc_1" equiv-text="base64:Jmx0O2cgaWQ9IjEiJmd0Ow=="/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
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
        $expected_l1_segment = '<ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAzICV9"/>';
        $expected_l2_segment = '&lt;ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAzICV9"/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithLessThanAttachedToANumber() {
        // less than %lt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &lt;3 %}';
        $expected_l1_segment = '<ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmx0OzMgJX0="/>';
        $expected_l2_segment = '&lt;ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmx0OzMgJX0="/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithGreaterThan() {
        // less than %gt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &gt; 3 %}';
        $expected_l1_segment = '<ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmd0OyAzICV9"/>';
        $expected_l2_segment = '&lt;ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmd0OyAzICV9"/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithLessThanAndGreaterThan() {
        // less than %lt;
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '{% if count &lt; 10 and &gt; 3 %}';
        $expected_l1_segment = '<ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAxMCBhbmQgJmd0OyAzICV9"/>';
        $expected_l2_segment = '&lt;ph id="mtc_1" equiv-text="base64:eyUgaWYgY291bnQgJmx0OyAxMCBhbmQgJmd0OyAzICV9"/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db = $Filter->fromLayer1ToLayer0( $expected_l1_segment );

        $this->assertEquals( $db_segment, $back_to_db );
    }

    public function testTwigFilterWithSingleBrackets() {
        $segment  = 'Hi {this strings would not be escaped}. Instead {{this one}} is a valid twig expression. Also {%%ciao%%} is valid!';
        $expected = 'Hi {this strings would not be escaped}. Instead <ph id="mtc_1" equiv-text="base64:e3t0aGlzIG9uZX19"/> is a valid twig expression. Also <ph id="mtc_2" equiv-text="base64:eyUlY2lhbyUlfQ=="/> is valid!';

        $channel = new Pipeline();
        $channel->addLast( new TwigToPh() );
        $seg_transformed = $channel->transform( $segment );
        $this->assertEquals( $expected, $seg_transformed );
    }

    public function testTwigUngreedy() {
        $segment  = 'Dear {{customer.first_name}}, This is {{agent.alias}} with Airbnb.';
        $expected = 'Dear <ph id="mtc_1" equiv-text="base64:e3tjdXN0b21lci5maXJzdF9uYW1lfX0="/>, This is <ph id="mtc_2" equiv-text="base64:e3thZ2VudC5hbGlhc319"/> with Airbnb.';

        $channel = new Pipeline();
        $channel->addLast( new TwigToPh() );
        $seg_transformed = $channel->transform( $segment );
        $this->assertEquals( $expected, $seg_transformed );
    }

    /**
     **************************
     * <ph> tags test (xliff 2.0)
     **************************
     */

    public function testPhWithoutDataRef() {
        $db_segment = 'We can control who sees content when with <ph id="source1" dataRef="source1"/>Visibility Constraints.';
        $Filter     = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $expected_l1_segment = 'We can control who sees content when with <ph id="source1" dataRef="source1"/>Visibility Constraints.';
        $expected_l2_segment = 'We can control who sees content when with &lt;ph id="mtc_ph_u_1" equiv-text="base64:Jmx0O3BoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8mZ3Q7"/&gt;Visibility Constraints.';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        // Persistance test
        $from_UI              = 'Saame nähtavuse piirangutega kontrollida, kes sisu näeb .<ph id="mtc_ph_u_1" equiv-text="base64:Jmx0O3BoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8mZ3Q7"/>';
        $exptected_db_segment = 'Saame nähtavuse piirangutega kontrollida, kes sisu näeb .<ph id="source1" dataRef="source1"/>';
        $back_to_db_segment   = $Filter->fromLayer1ToLayer0( $from_UI );

        $this->assertEquals( $back_to_db_segment, $exptected_db_segment );
    }

    /**
     * @throws \Exception
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
        $expected_l1_segment     = "Hi <ph id=\"mtc_1\" equiv-text=\"base64:JXM=\"/> .";
        $expected_l1_translation = "Tere <ph id=\"mtc_1\" equiv-text=\"base64:JXM=\"/> .";
        $expected_l2_segment     = "Hi &lt;ph id=\"mtc_1\" equiv-text=\"base64:JXM=\"/&gt; .";
        $expected_l2_translation = "Tere &lt;ph id=\"mtc_1\" equiv-text=\"base64:JXM=\"/&gt; .";

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $l2_segment, $expected_l2_segment );
        $this->assertEquals( $l2_translation, $expected_l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     * @throws \Exception
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
        $expected_l2_segment     = 'Frase semplice: &lt;ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/&gt;.';
        $expected_l2_translation = 'Simple sentence: &lt;ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/&gt;.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $l2_segment, $expected_l2_segment );
        $this->assertEquals( $l2_translation, $expected_l2_translation );

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

        $db_segment          = '<pc id="source1" dataRefStart="source1">&lt;<pc id="source2" dataRefStart="source2">Rider </pc></pc>';
        $expected_l1_segment = '<pc id="source1" dataRefStart="source1">&lt;<pc id="source2" dataRefStart="source2">Rider </pc></pc>';
        $expected_l2_segment = '&lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:PGJyPg=="/&gt;&lt;&lt;ph id="source2_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiJmd0Ow==" dataRef="source2" equiv-text="base64:PGhyPg=="/&gt;Rider &lt;ph id="source2_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source2" equiv-text="base64:PGhyPg=="/&gt;&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:PGJyPg=="/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );
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
        $expected_l2_segment = '&lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:PGcgaWQ9ImxwdXhuaVFsSVczS3JVeXciIGN0eXBlPSJ4LWh0bWwtcCIgXC8+"/&gt;Click the image on the left, read the information and then select the contact type that would replace the red question mark.&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:PGcgaWQ9ImxwdXhuaVFsSVczS3JVeXciIGN0eXBlPSJ4LWh0bWwtcCIgXC8+"/&gt;&lt;ph id="source2_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiJmd0Ow==" dataRef="source2" equiv-text="base64:PGcgaWQ9ImQzVGxQdG9tbFV0MEVqMWsiIGN0eXBlPSJ4LWh0bWwtcCIgXC8+"/&gt;&lt;ph id="source3_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiJmd0Ow==" dataRef="source3" equiv-text="base64:PGcgaWQ9ImpjUC1URkZTTzJDU3N1THQiIGN0eXBlPSJ4LWh0bWwtc3Ryb25nIiBcLz4="/&gt;Things to consider:&lt;ph id="source3_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source3" equiv-text="base64:PGcgaWQ9ImpjUC1URkZTTzJDU3N1THQiIGN0eXBlPSJ4LWh0bWwtc3Ryb25nIiBcLz4="/&gt;&lt;ph id="source2_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source2" equiv-text="base64:PGcgaWQ9ImQzVGxQdG9tbFV0MEVqMWsiIGN0eXBlPSJ4LWh0bWwtcCIgXC8+"/&gt;&lt;ph id="source4_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiJmd0Ow==" dataRef="source4" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg=="/&gt;&lt;ph id="source5_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2U1IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTUiJmd0Ow==" dataRef="source5" equiv-text="base64:PGcgaWQ9Ijk5cGhoSmNFUURMSEJqZVUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg=="/&gt;The rider stated the car had a different tag from another state.&lt;ph id="source5_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source5" equiv-text="base64:PGcgaWQ9Ijk5cGhoSmNFUURMSEJqZVUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg=="/&gt;&lt;ph id="source6_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2U2IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTYiJmd0Ow==" dataRef="source6" equiv-text="base64:PGcgaWQ9IjBIWnVnMWQzTGtYSlUwNEUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg=="/&gt;The rider stated the car had a color from the one registered in Bliss.&lt;ph id="source6_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source6" equiv-text="base64:PGcgaWQ9IjBIWnVnMWQzTGtYSlUwNEUiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg=="/&gt;&lt;ph id="source7_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2U3IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTciJmd0Ow==" dataRef="source7" equiv-text="base64:PGcgaWQ9Im9aM29XXzBLYWljRlhGRFMiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg=="/&gt;The rider can’t tell if the driver matched the profile picture.&lt;ph id="source7_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source7" equiv-text="base64:PGcgaWQ9Im9aM29XXzBLYWljRlhGRFMiIGN0eXBlPSJ4LWh0bWwtbGkiIFwvPg=="/&gt;&lt;ph id="source4_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source4" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg=="/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db_segment_from_l1 = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment_from_l1, $db_segment );
    }

    public function testPCWithoutAnyDataRefMap() {
        $data_ref_map = [];

        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', $data_ref_map );

        $db_segment          = 'Practice using <pc id="1b" type="fmt" subType="m:b">coaching frameworks</pc> and skills with peers and coaches in a safe learning environment.';
        $expected_l1_segment = 'Practice using <pc id="1b" type="fmt" subType="m:b">coaching frameworks</pc> and skills with peers and coaches in a safe learning environment.';
        $expected_l2_segment = 'Practice using &lt;ph id="mtc_u_1" equiv-text="base64:Jmx0O3BjIGlkPSIxYiIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOmIiJmd0Ow=="/&gt;coaching frameworks&lt;ph id="mtc_u_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt; and skills with peers and coaches in a safe learning environment.';

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
        $expected_l2_segment     = 'Testo libero contenente &lt;ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Xw=="/&gt;corsivo&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d1" equiv-text="base64:Xw=="/&gt;.';
        $expected_l2_translation = 'Free text containing &lt;ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Xw=="/&gt;curvise&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d1" equiv-text="base64:Xw=="/&gt;.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $l2_segment, $expected_l2_segment );
        $this->assertEquals( $l2_translation, $expected_l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     * @throws \Exception
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
        $expected_l2_segment     = 'Link semplice: &lt;ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Ww=="/&gt;La Repubblica&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk="/&gt;.';
        $expected_l2_translation = 'Simple link: &lt;ph id="1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow==" dataRef="d1" equiv-text="base64:Ww=="/&gt;La Repubblica&lt;ph id="1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="d2" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk="/&gt;.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $l2_segment, $expected_l2_segment );
        $this->assertEquals( $l2_translation, $expected_l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    /**
     * @throws \Exception
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
        $expected_l2_segment     = 'Text &lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:eA=="/&gt;&lt;ph id="mtc_u_1" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/&gt;link&lt;ph id="mtc_u_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:eA=="/&gt;.';
        $expected_l2_translation = 'Testo &lt;ph id="source1_1" dataType="pcStart" originalData="Jmx0O3BjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiJmd0Ow==" dataRef="source1" equiv-text="base64:eA=="/&gt;&lt;ph id="mtc_u_1" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/&gt;link&lt;ph id="mtc_u_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;ph id="source1_2" dataType="pcEnd" originalData="Jmx0Oy9wYyZndDs=" dataRef="source1" equiv-text="base64:eA=="/&gt;.';

        $l1_segment     = $Filter->fromLayer0ToLayer1( $db_segment );
        $l1_translation = $Filter->fromLayer0ToLayer1( $db_translation );
        $l2_segment     = $Filter->fromLayer1ToLayer2( $l1_segment );
        $l2_translation = $Filter->fromLayer1ToLayer2( $l1_translation );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l1_translation, $expected_l1_translation );
        $this->assertEquals( $l2_segment, $expected_l2_segment );
        $this->assertEquals( $l2_translation, $expected_l2_translation );

        $back_to_db_segment     = $Filter->fromLayer1ToLayer0( $l1_segment );
        $back_to_db_translation = $Filter->fromLayer1ToLayer0( $l1_translation );

        $this->assertEquals( $back_to_db_segment, $db_segment );
        $this->assertEquals( $back_to_db_translation, $db_translation );
    }

    public function testDontTouchAlreadyParsedPhTags() {
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $segment    = 'Frase semplice: <ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>.';
        $expected   = 'Frase semplice: &lt;ph id="source1" dataRef="source1" equiv-text="base64:Jmx0O2JyJmd0Ow=="/&gt;.';
        $l2_segment = $Filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $expected, $l2_segment );
    }

    public function testHtmlStringsWithDataTypeAttribute() {
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        $db_segment          = '&lt;span data-type="hotspot" class="hotspotOnImage" style="position: relative;display: inline-block;max-width: 100%"&gt;&lt;img src="https://files-storage.easygenerator.com/image/a59cc702-b609-483d-89bd-d65084cde0ed.png" alt="" style="max-width:100%"&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 608px; height: 373px; top: 22px; left: 15px;" data-text="Fysische besmetting" data-id="b0d02fa9-a022-4258-d0a9-b9b1b5deacc0"&gt;&lt;/span&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 591px; height: 340px; top: 55px; left: 675px;" data-text="Besmetting met allergenen" data-id="04e17f73-f836-485d-e2c5-293b0f4ec4ff"&gt;&lt;/span&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 601px; height: 357px; top: 479px; left: 26px;" data-text="Microbiologische besmetting" data-id="6afa3766-4d97-4d08-c3d5-ce9281728d01"&gt;&lt;/span&gt;&lt;span class="spot" style="position: absolute; display: inline-block; width: 590px; height: 362px; top: 478px; left: 679px;" data-text="Chemische besmetting" data-id="2918ea16-fb49-409e-d33d-4f2bbcbd4d53"&gt;&lt;/span&gt;&lt;/span&gt;';
        $expected_l1_segment = '<ph id="mtc_1" equiv-text="base64:Jmx0O3NwYW4gZGF0YS10eXBlPSJob3RzcG90IiBjbGFzcz0iaG90c3BvdE9uSW1hZ2UiIHN0eWxlPSJwb3NpdGlvbjogcmVsYXRpdmU7ZGlzcGxheTogaW5saW5lLWJsb2NrO21heC13aWR0aDogMTAwJSImZ3Q7"/><ph id="mtc_2" equiv-text="base64:Jmx0O2ltZyBzcmM9Imh0dHBzOi8vZmlsZXMtc3RvcmFnZS5lYXN5Z2VuZXJhdG9yLmNvbS9pbWFnZS9hNTljYzcwMi1iNjA5LTQ4M2QtODliZC1kNjUwODRjZGUwZWQucG5nIiBhbHQ9IiIgc3R5bGU9Im1heC13aWR0aDoxMDAlIiZndDs="/><ph id="mtc_3" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwOHB4OyBoZWlnaHQ6IDM3M3B4OyB0b3A6IDIycHg7IGxlZnQ6IDE1cHg7IiBkYXRhLXRleHQ9IkZ5c2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9ImIwZDAyZmE5LWEwMjItNDI1OC1kMGE5LWI5YjFiNWRlYWNjMCImZ3Q7"/><ph id="mtc_4" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_5" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MXB4OyBoZWlnaHQ6IDM0MHB4OyB0b3A6IDU1cHg7IGxlZnQ6IDY3NXB4OyIgZGF0YS10ZXh0PSJCZXNtZXR0aW5nIG1ldCBhbGxlcmdlbmVuIiBkYXRhLWlkPSIwNGUxN2Y3My1mODM2LTQ4NWQtZTJjNS0yOTNiMGY0ZWM0ZmYiJmd0Ow=="/><ph id="mtc_6" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_7" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwMXB4OyBoZWlnaHQ6IDM1N3B4OyB0b3A6IDQ3OXB4OyBsZWZ0OiAyNnB4OyIgZGF0YS10ZXh0PSJNaWNyb2Jpb2xvZ2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjZhZmEzNzY2LTRkOTctNGQwOC1jM2Q1LWNlOTI4MTcyOGQwMSImZ3Q7"/><ph id="mtc_8" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_9" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MHB4OyBoZWlnaHQ6IDM2MnB4OyB0b3A6IDQ3OHB4OyBsZWZ0OiA2NzlweDsiIGRhdGEtdGV4dD0iQ2hlbWlzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjI5MThlYTE2LWZiNDktNDA5ZS1kMzNkLTRmMmJiY2JkNGQ1MyImZ3Q7"/><ph id="mtc_10" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/><ph id="mtc_11" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/>';
        $expected_l2_segment = '&lt;ph id="mtc_1" equiv-text="base64:Jmx0O3NwYW4gZGF0YS10eXBlPSJob3RzcG90IiBjbGFzcz0iaG90c3BvdE9uSW1hZ2UiIHN0eWxlPSJwb3NpdGlvbjogcmVsYXRpdmU7ZGlzcGxheTogaW5saW5lLWJsb2NrO21heC13aWR0aDogMTAwJSImZ3Q7"/&gt;&lt;ph id="mtc_2" equiv-text="base64:Jmx0O2ltZyBzcmM9Imh0dHBzOi8vZmlsZXMtc3RvcmFnZS5lYXN5Z2VuZXJhdG9yLmNvbS9pbWFnZS9hNTljYzcwMi1iNjA5LTQ4M2QtODliZC1kNjUwODRjZGUwZWQucG5nIiBhbHQ9IiIgc3R5bGU9Im1heC13aWR0aDoxMDAlIiZndDs="/&gt;&lt;ph id="mtc_3" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwOHB4OyBoZWlnaHQ6IDM3M3B4OyB0b3A6IDIycHg7IGxlZnQ6IDE1cHg7IiBkYXRhLXRleHQ9IkZ5c2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9ImIwZDAyZmE5LWEwMjItNDI1OC1kMGE5LWI5YjFiNWRlYWNjMCImZ3Q7"/&gt;&lt;ph id="mtc_4" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_5" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MXB4OyBoZWlnaHQ6IDM0MHB4OyB0b3A6IDU1cHg7IGxlZnQ6IDY3NXB4OyIgZGF0YS10ZXh0PSJCZXNtZXR0aW5nIG1ldCBhbGxlcmdlbmVuIiBkYXRhLWlkPSIwNGUxN2Y3My1mODM2LTQ4NWQtZTJjNS0yOTNiMGY0ZWM0ZmYiJmd0Ow=="/&gt;&lt;ph id="mtc_6" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_7" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDYwMXB4OyBoZWlnaHQ6IDM1N3B4OyB0b3A6IDQ3OXB4OyBsZWZ0OiAyNnB4OyIgZGF0YS10ZXh0PSJNaWNyb2Jpb2xvZ2lzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjZhZmEzNzY2LTRkOTctNGQwOC1jM2Q1LWNlOTI4MTcyOGQwMSImZ3Q7"/&gt;&lt;ph id="mtc_8" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_9" equiv-text="base64:Jmx0O3NwYW4gY2xhc3M9InNwb3QiIHN0eWxlPSJwb3NpdGlvbjogYWJzb2x1dGU7IGRpc3BsYXk6IGlubGluZS1ibG9jazsgd2lkdGg6IDU5MHB4OyBoZWlnaHQ6IDM2MnB4OyB0b3A6IDQ3OHB4OyBsZWZ0OiA2NzlweDsiIGRhdGEtdGV4dD0iQ2hlbWlzY2hlIGJlc21ldHRpbmciIGRhdGEtaWQ9IjI5MThlYTE2LWZiNDktNDA5ZS1kMzNkLTRmMmJiY2JkNGQ1MyImZ3Q7"/&gt;&lt;ph id="mtc_10" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;&lt;ph id="mtc_11" equiv-text="base64:Jmx0Oy9zcGFuJmd0Ow=="/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db_segment = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment, $db_segment );
    }

    public function testPhTagsWithoutDataRef() {
        $Filter = MateCatFilter::getInstance( new FeatureSet(), 'en-EN', 'et-ET', [] );

        //dataRef="source1"
        $db_segment          = '<ph id="1j" type="other" subType="m:j"/>';
        $expected_l1_segment = '<ph id="1j" type="other" subType="m:j"/>';
        $expected_l2_segment = '&lt;ph id="mtc_ph_u_1" equiv-text="base64:Jmx0O3BoIGlkPSIxaiIgdHlwZT0ib3RoZXIiIHN1YlR5cGU9Im06aiIvJmd0Ow=="/&gt;';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );
        $l2_segment = $Filter->fromLayer1ToLayer2( $l1_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );
        $this->assertEquals( $l2_segment, $expected_l2_segment );

        $back_to_db_segment = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment, $db_segment );
    }

    /**
     **************************
     * Uber pipeline
     **************************
     */

    public function testWithUberPipeline() {
        $Filter = MateCatFilter::getInstance( new FeatureSet( [ new UberFeature() ] ), 'en-EN', 'et-ET', [] );

        $db_segment          = 'Ciao questo è una prova {RIDER}. { RIDER } non viene bloccato.';
        $expected_l1_segment = 'Ciao questo è una prova <ph id="mtc_1" equiv-text="base64:e1JJREVSfQ=="/>. { RIDER } non viene bloccato.';

        $l1_segment = $Filter->fromLayer0ToLayer1( $db_segment );

        $this->assertEquals( $l1_segment, $expected_l1_segment );

        $back_to_db_segment = $Filter->fromLayer1ToLayer0( $l1_segment );

        $this->assertEquals( $back_to_db_segment, $db_segment );
    }
}
