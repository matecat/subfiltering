<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 25/09/23
 * Time: 18:26
 *
 */

namespace Matecat\SubFiltering\Tests;

use Exception;
use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use PHPUnit\Framework\TestCase;

class SpecialHtmlEntitiesTest extends TestCase {

    private function getFilterInstance() {
        MateCatFilter::destroyInstance(); // for isolation test

        return MateCatFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT' );
    }

    /**
     * @throws Exception
     */
    public function testHtmlNbspInHtmlEncoding() {

        /**
         * @var $filter MateCatFilter
         */
        $filter = $this->getFilterInstance();

        $segment          = "This is &amp;nbsp; in html, but we are in xml ( double encode )";
        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $segment, $database_segment );

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );
    }

    public function testRealNbspInXliff() {

        /**
         * @var $filter MateCatFilter
         */
        $filter = $this->getFilterInstance();

        $segment          = "This is a real non-breaking space   in xliff";
        $segment_UI       = 'This is a real non-breaking space ##$_A0$## in xliff';
        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $segment, $database_segment );

        $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $segment );
        $this->assertEquals( $segment_UI, $segmentL2 );

        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

        $this->assertEquals( $segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    public function testUnsupportedXmlEntities() {

        /**
         * @var $filter MateCatFilter
         */
        $filter              = $this->getFilterInstance();
        $segment             = "These are &lt;p&gt; some chars \n \r\n \t inside an xliff";
        $expected_db_segment = "These are &lt;p&gt; some chars &#10; &#13;&#10; &#09; inside an xliff";
        $segment_UI          = 'These are <ph id="mtc_1" ctype="x-html" equiv-text="base64:Jmx0O3AmZ3Q7"/> some chars ##$_0A$## ##$_0D$####$_0A$## ##$_09$## inside an xliff';

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

        $segmentL1 = $filter->fromLayer0ToLayer1( $database_segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $database_segment );
        $this->assertEquals( $segment_UI, $segmentL2 );

        $this->assertEquals( $database_segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

        $this->assertEquals( $database_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $expected_db_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    public function testQuotesNoEntities() {

        /**
         * @var $filter MateCatFilter
         */
        $filter              = $this->getFilterInstance();
        $segment             = "These are quotes inside an xliff:  ' \"";
        $expected_db_segment = "These are quotes inside an xliff:  ' \"";
        $segment_UI          = "These are quotes inside an xliff:  ' \"";

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

        $segmentL1 = $filter->fromLayer0ToLayer1( $database_segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $database_segment );
        $this->assertEquals( $segment_UI, $segmentL2 );

        $this->assertEquals( $database_segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

        $this->assertEquals( $database_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $expected_db_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    public function testQuotesAsEntities() {

        /**
         * @var $filter MateCatFilter
         */
        $filter              = $this->getFilterInstance();
        $segment             = "These are quotes inside a xliff:  &apos; &quot;";
        $expected_db_segment = "These are quotes inside a xliff:  &apos; &quot;";
        $segment_UI          = 'These are quotes inside a xliff:  &apos; &quot;';

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

        $segmentL1 = $filter->fromLayer0ToLayer1( $database_segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $database_segment );
        $this->assertEquals( $segment_UI, $segmentL2 );

        $this->assertEquals( $database_segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

        $this->assertEquals( $database_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $expected_db_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    public function testQuotesAsEncodedEntities() {

        /**
         * @var $filter MateCatFilter
         */
        $filter              = $this->getFilterInstance();
        $segment             = "These are quotes inside a html encoded in a xliff:  &amp;apos; &amp;quot;";
        $expected_db_segment = "These are quotes inside a html encoded in a xliff:  &amp;apos; &amp;quot;";
        $segment_UI          = 'These are quotes inside a html encoded in a xliff:  &amp;apos; &amp;quot;';

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

        $segmentL1 = $filter->fromLayer0ToLayer1( $database_segment );
        $segmentL2 = $filter->fromLayer0ToLayer2( $database_segment );
        $this->assertEquals( $segment_UI, $segmentL2 );

        $this->assertEquals( $database_segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
        $this->assertEquals( $segmentL2, $filter->fromLayer1ToLayer2( $segmentL1 ) );

        $this->assertEquals( $database_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );
        $this->assertEquals( $expected_db_segment, $filter->fromLayer2ToLayer0( $segmentL2 ) );

        $this->assertEquals( $segmentL1, $filter->fromLayer2ToLayer1( $segmentL2 ) );

    }

    public function testDangerousChars() {

        /**
         * @var $filter MateCatFilter
         */
        $filter              = $this->getFilterInstance();
        $segment             = "These dangerous characters in a xliff: '" . chr( 0X07 ) . "'(Bell) '" . chr( 0X7F ) . "'(Delete) '" . chr(0X18) . "'(Cancel)";
        $expected_db_segment = "These dangerous characters in a xliff: ''(Bell) ''(Delete) ''(Cancel)";

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

        $segment             = "These dangerous characters in a xliff: '&#07;'(Bell) '&#127;'(Delete) '&#24;'(Cancel)";
        $expected_db_segment = "These dangerous characters in a xliff: ''(Bell) ''(Delete) ''(Cancel)";

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

        $segment             = "These dangerous characters in a xliff: '&#x07;'(Bell) '&#x7F;'(Delete) '&#x18;'(Cancel)";
        $expected_db_segment = "These dangerous characters in a xliff: ''(Bell) ''(Delete) ''(Cancel)";

        $database_segment = $filter->fromRawXliffToLayer0( $segment );
        $this->assertEquals( $expected_db_segment, $database_segment );

    }

}