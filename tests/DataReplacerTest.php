<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Utils\DataRefReplacer;
use PHPUnit\Framework\TestCase;

class DataReplacerTest extends TestCase
{
    /**
     * @test
     */
    public function can_replace_pc_with_adjacent_angle_brackets()
    {
        $map = [
            'source1' => 'a',
            'source2' => 'b',
            'source3' => 'c',
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<pc id="source1" dataRefStart="source1">Age (if exact date is not available</pc><pc id="source2" dataRefStart="source2"> &lt;day,month,year>&amp;nbsp; </pc><pc id="source3" dataRefStart="source3">or we have work/education history to prove the age difference)</pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>Age (if exact date is not available<ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PC9wYz4="/><ph id="source2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/> &lt;day,month,year>&amp;nbsp; <ph id="source2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PC9wYz4="/><ph id="source3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Yw==" x-orig="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg=="/>or we have work/education history to prove the age difference)<ph id="source3_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Yw==" x-orig="PC9wYz4="/>';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_ph_with_null_values_in_original_map()
    {
        $map = [
            'd1' => null
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<ph dataRef="d1" id="d1"/> ciao';
        $expected = '<ph id="d1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:TlVMTA==" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iZDEiLz4="/> ciao';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_pc_with_null_values_in_original_map()
    {
        $map = [
            'source1' => null
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<pc id="source1" dataRefStart="source1">ciao</pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:TlVMTA==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>ciao<ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:TlVMTA==" x-orig="PC9wYz4="/>';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_add_id_to_ph_ec_sc_when_is_missing()
    {
        $map = [
            'd1' => '&lt;x/&gt;',
            'd2' => '&lt;br\/&gt;',
        ];

        $string = '<ph dataRef="d1" id="d1"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>';
        $expected = '<ph id="d1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O3gvJmd0Ow==" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iZDEiLz4="/><ph id="d2" ctype="'
            . CTypeEnum::EC_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PGVjIGRhdGFSZWY9ImQyIiBzdGFydFJlZj0iNSIgc3ViVHlwZT0ieGxmOmIiIHR5cGU9ImZtdCIvPg=="/>';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_ph_with_same_ids()
    {
        $map = [
            'd1' => '&lt;br\/&gt;',
        ];

        $string = 'San Francisco, CA<ph dataRef="d1" id="1" subType="xlf:lb" type="fmt"/>650 California St, Ste 2950<ph dataRef="d1" id="2" subType="xlf:lb" type="fmt"/>San Francisco<ph dataRef="d1" id="3" subType="xlf:lb" type="fmt"/>CA 94108';
        $expected = 'San Francisco, CA<ph id="1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iMSIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>650 California St, Ste 2950<ph id="2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iMiIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>San Francisco<ph id="3" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iMyIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>CA 94108';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_nothing_with_ph_tags_without_dataref()
    {
        $map = [
            'source3' => '&lt;/a&gt;',
            'source4' => '&lt;br&gt;',
            'source5' => '&lt;br&gt;',
            'source1' => '&lt;br&gt;',
            'source2' => '&lt;a href=%s&gt;',
        ];

        $string = 'Hi <ph id="mtc_1" equiv-text="base64:JXM="/> .';
        $expected = 'Hi <ph id="mtc_1" equiv-text="base64:JXM="/> .';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    /**
     * @test
     */
    public function do_nothing_with_empty_map()
    {
        $map = [];

        $string = '<ph id="mtc_1" dataRef="x5" equiv-text="base64:Jmx0O2gyJmd0Ow=="/>Aanvullende richtlijnen voor hosts van privékamers en gedeelde ruimtes<ph id="mtc_2" equiv-text="base64:Jmx0Oy9oMiZndDs="/> stellen.';
        $expected = '<ph id="mtc_1" dataRef="x5" equiv-text="base64:Jmx0O2gyJmd0Ow=="/>Aanvullende richtlijnen voor hosts van privékamers en gedeelde ruimtes<ph id="mtc_2" equiv-text="base64:Jmx0Oy9oMiZndDs="/> stellen.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    /**
     * @test
     */
    public function can_replace_data()
    {
        $map = [
            'source1' => '${AMOUNT}',
            'source2' => '${RIDER}',
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = 'Hai raccolto >ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/>&amp;nbsp; da >ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/>?';
        $expected = 'Hai raccolto >ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/>&amp;nbsp; da >ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/>?';

        $this->assertEquals($expected, $dataReplacer->replace($string));

        $string = 'Ai colectat <ph id="source1" dataRef="source1"/> din <ph id="source2" dataRef="source2"/>?';
        $expected = 'Ai colectat <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtBTU9VTlR9" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> din <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtSSURFUn0=" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/>?';

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data()
    {
        $map = [
            'source1' => '${AMOUNT}',
            'source2' => '${RIDER}',
        ];

        $string = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';
        $expected = 'Hai raccolto <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtBTU9VTlR9" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>  da <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtSSURFUn0=" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/>?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function idempotent_on_empty_map()
    {
        $map = [];

        $string = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';
        $expected = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_2()
    {
        // sample test
        $map = [
            'source1' => '${recipientName}'
        ];

        $string = '<ph id="source1" dataRef="source1"/> changed the address';
        $expected = '<ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));

        // EC tag
        $string = '<ec id="source1" dataRef="source1"/> changed the address';
        $expected = '<ph id="source1" ctype="' . CTypeEnum::EC_DATA_REF . '" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ==" x-orig="PGVjIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_3()
    {
        // more complex test
        $map = [
            'source1' => '${recipientName}',
            'source2' => 'Babbo Natale',
            'source3' => 'La Befana',
        ];

        $string = '<ph id="source1" dataRef="source1"/> lorem <ec id="source2" dataRef="source2"/> ipsum <sc id="source3" dataRef="source3"/> changed';
        $expected = '<ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> lorem <ph id="source2" ctype="'
            . CTypeEnum::EC_DATA_REF . '" equiv-text="base64:QmFiYm8gTmF0YWxl" x-orig="PGVjIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/> ipsum <ph id="source3" ctype="'
            . CTypeEnum::SC_DATA_REF . '" equiv-text="base64:TGEgQmVmYW5h" x-orig="PHNjIGlkPSJzb3VyY2UzIiBkYXRhUmVmPSJzb3VyY2UzIi8+"/> changed';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_4()
    {
        // sample test
        $map = [
            "source3" => '&amp;lt;br&amp;gt;',
            "source34" => '&amp;lt;div&amp;gt;',
            "source45" > '&amp;lt;a href=&amp;s.uber.co =m /&amp;quot;&amp;gt;',
            "source30" => '&amp;lt;div&amp;gt;',
            "source41" => '&amp;lt;div&amp;gt;',
            "source52" => '&amp;lt;/div&amp;gt;',
            "source17" => '&amp;lt;div&amp;gt;',
            "source28" => '&amp;lt;div&amp;gt;',
            "source8" => '&amp;lt;br&amp;gt;',
            "source39" => '&amp;lt;/b&amp;gt;',
            "source13" => '&amp;lt;br&amp;gt;',
            "source24" => '&amp;lt;div&amp;gt;',
            "source4" => '&amp;lt;/div&amp;gt;',
            "source35" => '&amp;lt;br&amp;gt;',
            "source46" => '&amp;lt;/a&amp;gt;',
            "source20" => '&amp;lt;div&amp;gt;',
            "source31" => '&amp;lt;a href=&amp;s.uber.co =m /&amp;quot;&amp;gt;',
            "source42" => '&amp;lt;br&amp;gt;',
            "source53" => '&amp;lt;div&amp;gt;',
            "source18" => '&amp;lt;br&amp;gt;',
            "source29" => '&amp;lt;/div&amp;gt;',
            "source9" => '&amp;lt;/div&amp;gt;',
            "source14" => '&amp;lt;/div&amp;gt;',
            "source25" => '&amp;lt;b&amp;gt;',
            "source5" => '&amp;lt;div&amp;gt;',
            "source36" => '&amp;lt;/div&amp;gt;',
            "source47" => '&amp;lt;b&amp;gt;',
            "source10" => '&amp;lt;div&amp;gt;',
            "source21" => '&amp;lt;a href=&amp;quot;https://www.uber.com/s/voucher =s /&amp;quot;&amp;gt;',
            "source1" => '{Rider First Name}',
            "source32" => '&amp;lt;/a&amp;gt;',
            "source43" => '&amp;lt;/div&amp;gt;',
            "source54" => '&amp;lt;/div&amp;gt;',
            "source50" => '&amp;lt;div&amp;gt;',
            "source19" => '&amp;lt;/div&amp;gt;',
            "source15" => '&amp;lt;div&amp;gt;',
            "source26" => '&amp;lt;/b&amp;gt;',
            "source6" => '&amp;lt;/div&amp;gt;',
            "source37" => '&amp;lt;div&amp;gt;',
            "source48" => '&amp;lt;/b&amp;gt;',
            "source11" => '&amp;lt;/div&amp;gt;',
            "source22" => '&amp;lt;/a&amp;gt;',
            "source2" => '&amp;lt;div&amp;gt;',
            "source33" => '&amp;lt;/div&amp;gt;',
            "source44" => '&amp;lt;div&amp;gt;',
            "source40" => '&amp;lt;/div&amp;gt;',
            "source51" => '&amp;lt;br&amp;gt;',
            "source16" => '&amp;lt;/div&amp;gt;',
            "source27" => '&amp;lt;/div&amp;gt;',
            "source7" => '&amp;lt;div&amp;gt;',
            "source38" => '&amp;lt;b&amp;gt;',
            "source49" => '&amp;lt;/div&amp;gt;',
            "source12" => '&amp;lt;div&amp;gt;',
            "source23" => '&amp;lt;/div&amp;gt;'
        ];

        $string = 'Hi <ph id="source1" dataRef="source1"/>,<ph id="source2" dataRef="source2"/><ph id="source3" dataRef="source3"/><ph id="source4" dataRef="source4"/><ph id="source5" dataRef="source5"/>Thanks for reaching out.<ph id="source6" dataRef="source6"/><ph id="source7" dataRef="source7"/><ph id="source8" dataRef="source8"/><ph id="source9" dataRef="source9"/><ph id="source10" dataRef="source10"/>Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" dataRef="source11"/><ph id="source12" dataRef="source12"/><ph id="source13" dataRef="source13"/><ph id="source14" dataRef="source14"/><ph id="source15" dataRef="source15"/>To start creating vouchers:<ph id="source16" dataRef="source16"/><ph id="source17" dataRef="source17"/><ph id="source18" dataRef="source18"/><ph id="source19" dataRef="source19"/><ph id="source20" dataRef="source20"/>1.';
        $expected = 'Hi <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:e1JpZGVyIEZpcnN0IE5hbWV9" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>,<ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/><ph id="source3" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2UzIiBkYXRhUmVmPSJzb3VyY2UzIi8+"/><ph id="source4" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2U0IiBkYXRhUmVmPSJzb3VyY2U0Ii8+"/><ph id="source5" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2U1IiBkYXRhUmVmPSJzb3VyY2U1Ii8+"/>Thanks for reaching out.<ph id="source6" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2U2IiBkYXRhUmVmPSJzb3VyY2U2Ii8+"/><ph id="source7" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2U3IiBkYXRhUmVmPSJzb3VyY2U3Ii8+"/><ph id="source8" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2U4IiBkYXRhUmVmPSJzb3VyY2U4Ii8+"/><ph id="source9" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2U5IiBkYXRhUmVmPSJzb3VyY2U5Ii8+"/><ph id="source10" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxMCIgZGF0YVJlZj0ic291cmNlMTAiLz4="/>Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxMSIgZGF0YVJlZj0ic291cmNlMTEiLz4="/><ph id="source12" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxMiIgZGF0YVJlZj0ic291cmNlMTIiLz4="/><ph id="source13" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2UxMyIgZGF0YVJlZj0ic291cmNlMTMiLz4="/><ph id="source14" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxNCIgZGF0YVJlZj0ic291cmNlMTQiLz4="/><ph id="source15" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxNSIgZGF0YVJlZj0ic291cmNlMTUiLz4="/>To start creating vouchers:<ph id="source16" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxNiIgZGF0YVJlZj0ic291cmNlMTYiLz4="/><ph id="source17" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxNyIgZGF0YVJlZj0ic291cmNlMTciLz4="/><ph id="source18" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2UxOCIgZGF0YVJlZj0ic291cmNlMTgiLz4="/><ph id="source19" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxOSIgZGF0YVJlZj0ic291cmNlMTkiLz4="/><ph id="source20" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UyMCIgZGF0YVJlZj0ic291cmNlMjAiLz4="/>1.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_5()
    {
        // sample test
        $map = [
            "source2" => '${RIDER}',
            "source3" => '&amp;lt;br&amp;gt;',
        ];

        $string = 'Hola <ph id="source1" dataRef="source1"/>';
        $expected = 'Hola <ph id="source1" dataRef="source1"/>';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_restore_data_with_no_matching_map_test()
    {
        // sample test
        $map = [
            "source2" => '${RIDER}',
            "source3" => '&amp;lt;br&amp;gt;',
        ];

        $string = 'Hola <ph id="source1" dataRef="source1" equiv-text=""/>';
        $expected = 'Hola <ph id="source1" dataRef="source1" equiv-text=""/>';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->restore($string));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_6()
    {
        $map = [
            'source1' => '${Rider First Name}',
            'source2' => '&amp;lt;div&amp;',
        ];

        $string = 'Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>?';
        $expected = 'Did you collect <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtSaWRlciBGaXJzdCBOYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> from <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wOw==" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/>?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_7()
    {
        $map = [
            'source1' => '${Rider First Name}',
            'source2' => '&amp;lt;div&amp;',
        ];

        $string = 'Did you collect <ph id="source1" dataRef="source1" equiv-text="base64:"/> from <ph id="source2" dataRef="source2" equiv-text="base64:"/>?';
        $expected = 'Did you collect <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JHtSaWRlciBGaXJzdCBOYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIiBlcXVpdi10ZXh0PSJiYXNlNjQ6Ii8+"/> from <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wOw==" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIiBlcXVpdi10ZXh0PSJiYXNlNjQ6Ii8+"/>?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_test_8()
    {
        $map = [
            'source1' => '&lt;p class=&quot;cmln__paragraph&quot;&gt;',
            'source2' => '&amp;#39;',
            'source3' => '&lt;/p&gt;',
        ];

        // in this case string input has some wrong equiv-text
        $string = 'Hai <ph id="source1" dataRef="source1" equiv-text="base64:JHtBTU9VTlR9"/>,<ph id="source2" dataRef="source2" equiv-text="base64:JHtSSURFUn0="/><ph id="source3" dataRef="source3"/>';
        $expected = 'Hai <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O3AgY2xhc3M9JnF1b3Q7Y21sbl9fcGFyYWdyYXBoJnF1b3Q7Jmd0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIiBlcXVpdi10ZXh0PSJiYXNlNjQ6Skh0QlRVOVZUbFI5Ii8+"/>,<ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:JmFtcDsjMzk7" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIiBlcXVpdi10ZXh0PSJiYXNlNjQ6Skh0U1NVUkZVbjA9Ii8+"/><ph id="source3" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0Oy9wJmd0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UzIiBkYXRhUmVmPSJzb3VyY2UzIi8+"/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_1()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
        ];

        $string = 'Link semplice: <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>.';
        $expected = 'Link semplice: <ph id="1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>La Repubblica<ph id="1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_2()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
            'd3' => '[',
            'd4' => '](http://google.it)',
        ];

        $string = 'Link semplici: <pc id="1" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc> <pc id="2" dataRefEnd="d3" dataRefStart="d4">Google</pc>.';
        $expected = 'Link semplici: <ph id="1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMSI+"/>La Repubblica<ph id="1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/> <ph id="2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:XShodHRwOi8vZ29vZ2xlLml0KQ==" x-orig="PHBjIGlkPSIyIiBkYXRhUmVmRW5kPSJkMyIgZGF0YVJlZlN0YXJ0PSJkNCI+"/>Google<ph id="2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_3()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
            'd3' => '[',
            'd4' => '](http://google.it)',
            'source1' => '${Rider First Name}',
            'source2' => '&amp;lt;div&amp;',
        ];

        $string = 'Did you collect <ph id="source1" dataRef="source1"/> from <ph id="source2" dataRef="source2"/>? Link semplici: <pc id="1" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc> <pc id="2" dataRefEnd="d3" dataRefStart="d4">Google</pc>.';
        $expected = 'Did you collect <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF
            . '" equiv-text="base64:JHtSaWRlciBGaXJzdCBOYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> from <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF
            . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wOw==" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/>? Link semplici: <ph id="1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF
            . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMSI+"/>La Repubblica<ph id="1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF
            . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/> <ph id="2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF
            . '" equiv-text="base64:XShodHRwOi8vZ29vZ2xlLml0KQ==" x-orig="PHBjIGlkPSIyIiBkYXRhUmVmRW5kPSJkMyIgZGF0YVJlZlN0YXJ0PSJkNCI+"/>Google<ph id="2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF
            . '" equiv-text="base64:Ww==" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_test_5()
    {
        $map = [
            'd1' => '[',
            'd2' => '](http://repubblica.it)',
            'd3' => '[',
            'd4' => '](http://google.it)',
        ];

        $string = 'Link semplici: <pc id="1" dataRefEnd="d2" dataRefStart="d1">La Repubblica</pc>';
        $expected = 'Link semplici: <ph id="1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSIxIiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMSI+"/>La Repubblica<ph id="1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:XShodHRwOi8vcmVwdWJibGljYS5pdCk=" x-orig="PC9wYz4="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_not_affect_not_matecat_ph_tags_with_equiv_text()
    {
        $dataReplacer = new DataRefReplacer([
            'source1' => '&lt;br&gt;',
        ]);

        $string = 'Hi <ph id="mtc_1" equiv-text="JXM="/>, <ph id="source1" dataRef="source1"/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';
        $expected = 'Hi <ph id="mtc_1" equiv-text="JXM="/>, <ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyJmd0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));

        $string = 'Hi &lt;ph id="mtc_1" equiv-text="JXM="/&gt;, <ph id="source1" dataRef="source1"/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';
        $expected = 'Hi &lt;ph id="mtc_1" equiv-text="JXM="/&gt;, <ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyJmd0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>You mentioned that you have a dashcam video footage to help us to better understand your recent incident.';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_nested_pc_tags()
    {
        $map = [
            'd1' => '_',
            'd2' => '**',
            'd3' => '`',
        ];

        $string = 'Testo libero contenente <pc id="3" dataRefEnd="d1" dataRefStart="d1"><pc id="4" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc></pc>';
        $expected = 'Testo libero contenente <ph id="3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PHBjIGlkPSIzIiBkYXRhUmVmRW5kPSJkMSIgZGF0YVJlZlN0YXJ0PSJkMSI+"/><ph id="4_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Kio=" x-orig="PHBjIGlkPSI0IiBkYXRhUmVmRW5kPSJkMiIgZGF0YVJlZlN0YXJ0PSJkMiI+"/>grassetto + corsivo<ph id="4_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Kio=" x-orig="PC9wYz4="/><ph id="3_2" ctype="x-pc_close_data_ref" equiv-text="base64:Xw==" x-orig="PC9wYz4="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_escaped_nested_pc_tags()
    {
        $map = [
            'd1' => '_',
            'd2' => '**',
            'd3' => '`',
        ];

        $string = 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>, <pc id="2" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2">grassetto</pc>, <pc id="3" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1"><pc id="4" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc></pc> e <pc id="5" canCopy="no" canDelete="no" dataRefEnd="d3" dataRefStart="d3">larghezza fissa</pc>.';
        $expected = 'Testo libero contenente <ph id="1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>corsivo<ph id="1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PC9wYz4="/>, <ph id="2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Kio=" x-orig="PHBjIGlkPSIyIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiPg=="/>grassetto<ph id="2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Kio=" x-orig="PC9wYz4="/>, <ph id="3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PHBjIGlkPSIzIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiPg=="/><ph id="4_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Kio=" x-orig="PHBjIGlkPSI0IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiPg=="/>grassetto + corsivo<ph id="4_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Kio=" x-orig="PC9wYz4="/><ph id="3_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PC9wYz4="/> e <ph id="5_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:YA==" x-orig="PHBjIGlkPSI1IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDMiIGRhdGFSZWZTdGFydD0iZDMiPg=="/>larghezza fissa<ph id="5_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:YA==" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_and_ph_matecat_tags()
    {
        $map = [
            'source1' => '[',
        ];

        $string = 'Text <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><ph id="mtc_2" equiv-text="base64:Yg=="/>Uber Community Guidelines<ph id="mtc_3" equiv-text="base64:Yg=="/></pc>.';
        $expected = 'Text <ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiPg=="/><ph id="mtc_2" equiv-text="base64:Yg=="/>Uber Community Guidelines<ph id="mtc_3" equiv-text="base64:Yg=="/><ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Ww==" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_and_ph_real_matecat_tags()
    {
        $map = [
            'source1' => '&lt;w:hyperlink r:id="rId6"&gt;&lt;/w:hyperlink&gt;',
        ];

        $string = 'This code of conduct sets forth the minimum standards by which Uber’s Driver Partners must adhere when using the Uber app in Czech Republic, in addition to the terms of their services agreement with Uber and the <pc id="source1" dataRefStart="source1" dataRefEnd="source1"><ph id="mtc_2" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/>Uber Community Guidelines<ph id="mtc_3" equiv-text="base64:Jmx0Oy9wYyZndDs="/></pc>.';
        $expected = 'This code of conduct sets forth the minimum standards by which Uber’s Driver Partners must adhere when using the Uber app in Czech Republic, in addition to the terms of their services agreement with Uber and the <ph id="source1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Jmx0O3c6aHlwZXJsaW5rIHI6aWQ9InJJZDYiJmd0OyZsdDsvdzpoeXBlcmxpbmsmZ3Q7" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiIGRhdGFSZWZFbmQ9InNvdXJjZTEiPg=="/><ph id="mtc_2" equiv-text="base64:Jmx0O3BjIGlkPSIxdSIgdHlwZT0iZm10IiBzdWJUeXBlPSJtOnUiJmd0Ow=="/>Uber Community Guidelines<ph id="mtc_3" equiv-text="base64:Jmx0Oy9wYyZndDs="/><ph id="source1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Jmx0O3c6aHlwZXJsaW5rIHI6aWQ9InJJZDYiJmd0OyZsdDsvdzpoeXBlcmxpbmsmZ3Q7" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_restore_data_with_pc_from_matecat_real_case()
    {
        $map = [
            'd1' => '_',
            'd2' => '**',
            'd3' => '`',
        ];

        $string = 'Testo libero contenente <ph id="1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PHBjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiPg=="/>x<ph id="1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Xw==" x-orig="PC9wYz4="/>';
        $expected = 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">x</pc>';

        $dataRefReplacer = new DataRefReplacer($map);

        $this->assertEquals($string, $dataRefReplacer->replace($expected));

        $this->assertEquals($expected, $dataRefReplacer->restore($string));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_missing_dataRefStart()
    {
        $map = [
            'd1' => '&lt;br\/&gt;',
        ];

        $string = 'Text <pc id="d1" dataRefStart="d1">Uber Community Guidelines</pc>.';
        $expected = 'Text <ph id="d1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBjIGlkPSJkMSIgZGF0YVJlZlN0YXJ0PSJkMSI+"/>Uber Community Guidelines<ph id="d1_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_missing_dataRefEnd()
    {
        $map = [
            'd1' => '&lt;br\/&gt;',
        ];

        $string = 'Text <pc id="d1" dataRefEnd="d1">Uber Community Guidelines</pc>.';
        $expected = 'Text <ph id="d1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBjIGlkPSJkMSIgZGF0YVJlZkVuZD0iZDEiPg=="/>Uber Community Guidelines<ph id="d1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PC9wYz4="/>.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_non_standard_characters()
    {
        $map = [
            "source3" => "<g id=\"jcP-TFFSO2CSsuLt\" ctype=\"x-html-strong\" \/>",
            "source4" => "<g id=\"5StCYYRvqMc0UAz4\" ctype=\"x-html-ul\" \/>",
            "source5" => "<g id=\"99phhJcEQDLHBjeU\" ctype=\"x-html-li\" \/>",
            "source1" => "<g id=\"lpuxniQlIW3KrUyw\" ctype=\"x-html-p\" \/>",
            "source6" => "<g id=\"0HZug1d3LkXJU04E\" ctype=\"x-html-li\" \/>",
            "source2" => "<g id=\"d3TlPtomlUt0Ej1k\" ctype=\"x-html-p\" \/>",
            "source7" => "<g id=\"oZ3oW_0KaicFXFDS\" ctype=\"x-html-li\" \/>"
        ];

        // this string contains ’
        $string = '<pc id="source4" dataRefStart="source4">The rider can’t tell if the driver matched the profile picture.</pc>';
        $expected = '<ph id="source4_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg==" x-orig="PHBjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiPg=="/>The rider can’t tell if the driver matched the profile picture.<ph id="source4_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:PGcgaWQ9IjVTdENZWVJ2cU1jMFVBejQiIGN0eXBlPSJ4LWh0bWwtdWwiIFwvPg==" x-orig="PC9wYz4="/>';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_nested_pc_structures()
    {
        $map = [
            "source1" => "x",
            "source2" => "y",
        ];

        $string = '<pc id="source1" dataRefStart="source1">foo <pc id="source2" dataRefStart="source2">bar</pc> baz</pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eA==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>foo <ph id="source2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eQ==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/>bar<ph id="source2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eQ==" x-orig="PC9wYz4="/> baz<ph id="source1_2" ctype="x-pc_close_data_ref" equiv-text="base64:eA==" x-orig="PC9wYz4="/>';

        $dataReplacer = new DataRefReplacer($map);


        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_pc_with_more_complex_nested_pc_structures()
    {
        $map = [
            "source1" => "x",
            "source2" => "y",
            "source3" => "z",
            "source4" => "a",
            "source5" => "b",
        ];

        $string = '<pc id="source1" dataRefStart="source1">foo <pc id="source2" dataRefStart="source2">bar lorem</pc> <pc id="source3" dataRefStart="source3">bar <pc id="source4" dataRefStart="source4">bar</pc> <pc id="source5" dataRefStart="source5">bar</pc></pc> cavolino</pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eA==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>foo <ph id="source2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eQ==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/>bar lorem<ph id="source2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eQ==" x-orig="PC9wYz4="/> <ph id="source3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eg==" x-orig="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg=="/>bar <ph id="source4_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PHBjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiPg=="/>bar<ph id="source4_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PC9wYz4="/> <ph id="source5_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PHBjIGlkPSJzb3VyY2U1IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTUiPg=="/>bar<ph id="source5_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PC9wYz4="/><ph id="source3_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eg==" x-orig="PC9wYz4="/> cavolino<ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eA==" x-orig="PC9wYz4="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_data_with_sc_and_ex_tags()
    {
        $map = [
            "d1" => "&lt;strong&gt;",
            "d2" => "&lt;\/strong&gt;",
            "d3" => "&lt;br\/&gt;",
            "d4" => "&lt;a href=\"mailto:info@elysiancollection.com\"&gt;",
            "d5" => "&lt;\/a&gt;",
        ];

        $string = '<sc dataRef="d1" id="1" subType="xlf:b" type="fmt"/>Elysian Collection<ph dataRef="d3" id="2" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="1" subType="xlf:b" type="fmt"/>Bahnhofstrasse 15, Postfach 341, Zermatt CH- 3920, Switzerland<ph dataRef="d3" id="3" subType="xlf:lb" type="fmt"/>Tel: +44 203 468 2235  Email: <pc dataRefEnd="d5" dataRefStart="d4" id="4" type="link">info@elysiancollection.com</pc><sc dataRef="d1" id="5" subType="xlf:b" type="fmt"/><ph dataRef="d3" id="6" subType="xlf:lb" type="fmt"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>';
        $expected = '<ph id="1" ctype="'
            . CTypeEnum::SC_DATA_REF . '" equiv-text="base64:Jmx0O3N0cm9uZyZndDs=" x-orig="PHNjIGRhdGFSZWY9ImQxIiBpZD0iMSIgc3ViVHlwZT0ieGxmOmIiIHR5cGU9ImZtdCIvPg=="/>Elysian Collection<ph id="2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQzIiBpZD0iMiIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/><ph id="d2" ctype="'
            . CTypeEnum::EC_DATA_REF . '" equiv-text="base64:Jmx0O1wvc3Ryb25nJmd0Ow==" x-orig="PGVjIGRhdGFSZWY9ImQyIiBzdGFydFJlZj0iMSIgc3ViVHlwZT0ieGxmOmIiIHR5cGU9ImZtdCIvPg=="/>Bahnhofstrasse 15, Postfach 341, Zermatt CH- 3920, Switzerland<ph id="3" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQzIiBpZD0iMyIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>Tel: +44 203 468 2235  Email: <ph id="4_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Jmx0O2EgaHJlZj0ibWFpbHRvOmluZm9AZWx5c2lhbmNvbGxlY3Rpb24uY29tIiZndDs=" x-orig="PHBjIGRhdGFSZWZFbmQ9ImQ1IiBkYXRhUmVmU3RhcnQ9ImQ0IiBpZD0iNCIgdHlwZT0ibGluayI+"/>info@elysiancollection.com<ph id="4_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Jmx0O1wvYSZndDs=" x-orig="PC9wYz4="/><ph id="5" ctype="x-sc_data_ref" equiv-text="base64:Jmx0O3N0cm9uZyZndDs=" x-orig="PHNjIGRhdGFSZWY9ImQxIiBpZD0iNSIgc3ViVHlwZT0ieGxmOmIiIHR5cGU9ImZtdCIvPg=="/><ph id="6" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQzIiBpZD0iNiIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/><ph id="d2" ctype="'
            . CTypeEnum::EC_DATA_REF . '" equiv-text="base64:Jmx0O1wvc3Ryb25nJmd0Ow==" x-orig="PGVjIGRhdGFSZWY9ImQyIiBzdGFydFJlZj0iNSIgc3ViVHlwZT0ieGxmOmIiIHR5cGU9ImZtdCIvPg=="/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function do_not_duplicate_equiv_text_in_ph_tags()
    {
        $map = [
            'source1' => '%s',
        ];

        $string = 'Hi <ph id="source1" dataRef="d1" equiv-text="base64:JXM="/> .';
        $expected = 'Hi <ph id="source1" dataRef="d1" equiv-text="base64:JXM="/> .';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * This test is not a real case
     *
     * @test
     */
    public function do_not_duplicate_equiv_text_in_already_transformed_pc_tags()
    {
        $map = [
            "d2" => "&lt;/a&gt;",
        ];

        $string = '<ph id="source2_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eQ==" x-orig="PC9wYz4="/>';
        $expected = '<ph id="source2_2" ctype="' . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eQ==" x-orig="PC9wYz4="/>';
        $restored = '</pc>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($restored, $dataReplacer->replace($restored));
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_parse_nested_not_mapped_pc()
    {
        $map = [
            "source1" => "a",
            "source2" => "b",
            "source3" => "c",
        ];

        $string = '<pc id="source1" dataRefStart="source1">April 24, 2017</pc> | Written by <pc id="source2" dataRefStart="source2"><pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc></pc><pc id="source3" dataRefStart="source3">,</pc> Global Head of Community Operations';

        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>April 24, 2017<ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PC9wYz4="/> | Written by <ph id="source2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/><pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc><ph id="source2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PC9wYz4="/><ph id="source3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Yw==" x-orig="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg=="/>,<ph id="source3_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Yw==" x-orig="PC9wYz4="/> Global Head of Community Operations';


        $restored = '<pc id="source1" dataRefStart="source1">April 24, 2017</pc> | Written by <pc id="source2" dataRefStart="source2"><pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc></pc><pc id="source3" dataRefStart="source3">,</pc> Global Head of Community Operations';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_parse_pc_with_lessThan_symbol_in_the_sentence()
    {
        $map = [
            "source1" => "a",
            "source2" => "b",
        ];

        $string = '<pc id="source1" dataRefStart="source1">&lt;<pc id="source2" dataRefStart="source2">Rider </pc></pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>&lt;<ph id="source2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/>Rider <ph id="source2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Yg==" x-orig="PC9wYz4="/><ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PC9wYz4="/>';
        $restored = '<pc id="source1" dataRefStart="source1">&lt;<pc id="source2" dataRefStart="source2">Rider </pc></pc>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_ph_nested_in_pc_tags()
    {
        $map = [
            "source3" => "1",
            "source8" => "2",
            "source4" => "3",
            "source9" => "4",
            "source5" => "5",
            "source10" => "6",
            "source1" => "7",
            "source6" => "8",
            "source11" => "9",
            "source2" => "10",
            "source7" => "11"
        ];

        $string = '<pc id="source1" dataRefStart="source1"><ph id="source2" dataRef="source2"/></pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Nw==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/><ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:MTA=" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/><ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Nw==" x-orig="PC9wYz4="/>';
        $restored = '<pc id="source1" dataRefStart="source1"><ph id="source2" dataRef="source2"/></pc>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_and_restore_with_a_complex_string_with_ph_nested_in_pc_tags()
    {
        $map = [
            "source3" => "1",
            "source8" => "2",
            "source4" => "3",
            "source9" => "4",
            "source5" => "5",
            "source10" => "6",
            "source1" => "7",
            "source6" => "8",
            "source11" => "9",
            "source2" => "10",
            "source7" => "11"
        ];

        $string = '<pc id="source1" dataRefStart="source1"><ph id="source2" dataRef="source2"/></pc><pc id="source3" dataRefStart="source3"><pc id="source4" dataRefStart="source4">Well done!<ph id="source5" dataRef="source5"/><ph id="source6" dataRef="source6"/>You have completed the course and are now ready to demonstrate your knowledge of<ph id="source7" dataRef="source7"/>how to use Case Management.</pc></pc><pc id="source8" dataRefStart="source8"><ph id="source9" dataRef="source9"/><ph id="source10" dataRef="source10"/><pc id="source11" dataRefStart="source11">Click on the "X" on the right side to exit the course.</pc></pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Nw==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/><ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:MTA=" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/><ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Nw==" x-orig="PC9wYz4="/><ph id="source3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:MQ==" x-orig="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg=="/><ph id="source4_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:Mw==" x-orig="PHBjIGlkPSJzb3VyY2U0IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTQiPg=="/>Well done!<ph id="source5" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:NQ==" x-orig="PHBoIGlkPSJzb3VyY2U1IiBkYXRhUmVmPSJzb3VyY2U1Ii8+"/><ph id="source6" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:OA==" x-orig="PHBoIGlkPSJzb3VyY2U2IiBkYXRhUmVmPSJzb3VyY2U2Ii8+"/>You have completed the course and are now ready to demonstrate your knowledge of<ph id="source7" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:MTE=" x-orig="PHBoIGlkPSJzb3VyY2U3IiBkYXRhUmVmPSJzb3VyY2U3Ii8+"/>how to use Case Management.<ph id="source4_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:Mw==" x-orig="PC9wYz4="/><ph id="source3_2" ctype="x-pc_close_data_ref" equiv-text="base64:MQ==" x-orig="PC9wYz4="/><ph id="source8_1" ctype="x-pc_open_data_ref" equiv-text="base64:Mg==" x-orig="PHBjIGlkPSJzb3VyY2U4IiBkYXRhUmVmU3RhcnQ9InNvdXJjZTgiPg=="/><ph id="source9" ctype="x-ph_data_ref" equiv-text="base64:NA==" x-orig="PHBoIGlkPSJzb3VyY2U5IiBkYXRhUmVmPSJzb3VyY2U5Ii8+"/><ph id="source10" ctype="x-ph_data_ref" equiv-text="base64:Ng==" x-orig="PHBoIGlkPSJzb3VyY2UxMCIgZGF0YVJlZj0ic291cmNlMTAiLz4="/><ph id="source11_1" ctype="x-pc_open_data_ref" equiv-text="base64:OQ==" x-orig="PHBjIGlkPSJzb3VyY2UxMSIgZGF0YVJlZlN0YXJ0PSJzb3VyY2UxMSI+"/>Click on the "X" on the right side to exit the course.<ph id="source11_2" ctype="x-pc_close_data_ref" equiv-text="base64:OQ==" x-orig="PC9wYz4="/><ph id="source8_2" ctype="x-pc_close_data_ref" equiv-text="base64:Mg==" x-orig="PC9wYz4="/>';
        $restored = '<pc id="source1" dataRefStart="source1"><ph id="source2" dataRef="source2"/></pc><pc id="source3" dataRefStart="source3"><pc id="source4" dataRefStart="source4">Well done!<ph id="source5" dataRef="source5"/><ph id="source6" dataRef="source6"/>You have completed the course and are now ready to demonstrate your knowledge of<ph id="source7" dataRef="source7"/>how to use Case Management.</pc></pc><pc id="source8" dataRefStart="source8"><ph id="source9" dataRef="source9"/><ph id="source10" dataRef="source10"/><pc id="source11" dataRefStart="source11">Click on the "X" on the right side to exit the course.</pc></pc>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function test_with_lt_and_gt()
    {
        $map = [
            'source1' => 'a',
        ];

        $string = 'Ödemenizin kapatılması için Ödemenizin kapatılması için &lt;Outage SLA time> beklemenizi rica ediyoruz. <ph dataRef="source1" id="source1"/>';
        $expected = 'Ödemenizin kapatılması için Ödemenizin kapatılması için &lt;Outage SLA time> beklemenizi rica ediyoruz. <ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:YQ==" x-orig="PHBoIGRhdGFSZWY9InNvdXJjZTEiIGlkPSJzb3VyY2UxIi8+"/>';
        $restored = 'Ödemenizin kapatılması için Ödemenizin kapatılması için &lt;Outage SLA time> beklemenizi rica ediyoruz. <ph dataRef="source1" id="source1"/>';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($restored, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function can_replace_self_closing_pc_with_values_in_original_map()
    {
        $map = [
            "d1" => "Hello"
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<pc dataRefStart="d1" id="d1"/> ciao';
        $expected = '<ph id="d1_1" ctype="' . CTypeEnum::PC_SELF_CLOSE_DATA_REF . '" equiv-text="base64:SGVsbG8=" x-orig="PHBjIGRhdGFSZWZTdGFydD0iZDEiIGlkPSJkMSIvPg=="/> ciao';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function more_tests_with_self_closed_pc_tags()
    {
        $map = [
            "source3" => "x-style",
            "source8" => "x-text",
            "source4" => "x-text",
            "source9" => "&lt;ept id=\"span_3\" \/&gt;",
            "source5" => "x-text",
            "source10" => "&lt;ept id=\"block_0\" \/&gt;",
            "source1" => "x-block",
            "source6" => "&lt;ept id=\"span_2\" \/&gt;",
            "source2" => "&lt;ph id=\"generic_1\"&gt;&lt;Style FlowDirection=\"LeftToRight\" LeadingMargin=\"0\" TrailingMargin=\"0\" FirstLineMargin=\"0\" Justification=\"Left\" ListLevel=\"0\" LineSpacingRule=\"Single\" LineSpacing=\"20\" SpacingBefore=\"0\" SpacingAfter=\"0\"&gt;&lt;ListStyle ListType=\"None\" ListTypeFormat=\"Parentheses\" Color=\"#212121\" BulletChar=\"\u00fc\" BulletFont=\"Arial\"&gt;&lt;BulletPicture Size=\"0x0\" \/&gt;&lt;\/ListStyle&gt;&lt;\/Style&gt;&lt;\/ph&gt;",
            "source7" => "x-style"
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<pc dataRefStart="source5" id="source5"/><ph dataRef="source6" id="source6"/><ph dataRef="source7" id="source7"/><pc dataRefStart="source8" id="source8">Let’s start!</pc><ph dataRef="source9" id="source9"/><ph dataRef="source10" id="source10"/>';
        $expected = '<ph id="source5_1" ctype="'
            . CTypeEnum::PC_SELF_CLOSE_DATA_REF . '" equiv-text="base64:eC10ZXh0" x-orig="PHBjIGRhdGFSZWZTdGFydD0ic291cmNlNSIgaWQ9InNvdXJjZTUiLz4="/><ph id="source6" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2VwdCBpZD0ic3Bhbl8yIiBcLyZndDs=" x-orig="PHBoIGRhdGFSZWY9InNvdXJjZTYiIGlkPSJzb3VyY2U2Ii8+"/><ph id="source7" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:eC1zdHlsZQ==" x-orig="PHBoIGRhdGFSZWY9InNvdXJjZTciIGlkPSJzb3VyY2U3Ii8+"/><ph id="source8_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF . '" equiv-text="base64:eC10ZXh0" x-orig="PHBjIGRhdGFSZWZTdGFydD0ic291cmNlOCIgaWQ9InNvdXJjZTgiPg=="/>Let’s start!<ph id="source8_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF . '" equiv-text="base64:eC10ZXh0" x-orig="PC9wYz4="/><ph id="source9" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2VwdCBpZD0ic3Bhbl8zIiBcLyZndDs=" x-orig="PHBoIGRhdGFSZWY9InNvdXJjZTkiIGlkPSJzb3VyY2U5Ii8+"/><ph id="source10" ctype="'
            . CTypeEnum::PH_DATA_REF . '" equiv-text="base64:Jmx0O2VwdCBpZD0iYmxvY2tfMCIgXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9InNvdXJjZTEwIiBpZD0ic291cmNlMTAiLz4="/>';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    /**
     * @test
     */
    public function not_data_ref_pc_should_be_ignored()
    {
        $map = [
            "source1" => "a"
        ];

        $string = '<pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc>';
        $expected = '<pc id="1b" type="fmt" subType="m:b">Troy Stevenson</pc>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }


}
