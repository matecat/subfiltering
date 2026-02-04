<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Utils\DataRefReplacer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DataReplacerTest extends TestCase
{
    #[Test]
    public function can_replace_pc_with_adjacent_angle_brackets(): void
    {
        $map = [
            'source1' => 'a',
            'source2' => 'b',
            'source3' => 'c',
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<pc id="source1" dataRefStart="source1">Age (if exact date is not available</pc><pc id="source2" dataRefStart="source2"> &lt;day,month,year>&amp;nbsp; </pc><pc id="source3" dataRefStart="source3">or we have work/education history to prove the age difference)</pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF->value . '" equiv-text="base64:YQ==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>Age (if exact date is not available<ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF->value . '" equiv-text="base64:YQ==" x-orig="PC9wYz4="/><ph id="source2_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF->value . '" equiv-text="base64:Yg==" x-orig="PHBjIGlkPSJzb3VyY2UyIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTIiPg=="/> &lt;day,month,year>&amp;nbsp; <ph id="source2_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF->value . '" equiv-text="base64:Yg==" x-orig="PC9wYz4="/><ph id="source3_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF->value . '" equiv-text="base64:Yw==" x-orig="PHBjIGlkPSJzb3VyY2UzIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTMiPg=="/>or we have work/education history to prove the age difference)<ph id="source3_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF->value . '" equiv-text="base64:Yw==" x-orig="PC9wYz4="/>';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_replace_ph_with_null_values_in_original_map(): void
    {
        $map = [
            'd1' => null
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<ph dataRef="d1" id="d1"/> ciao';
        $expected = '<ph id="d1" ctype="' . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:TlVMTA==" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iZDEiLz4="/> ciao';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_replace_pc_with_null_values_in_original_map(): void
    {
        $map = [
            'source1' => null
        ];

        $dataReplacer = new DataRefReplacer($map);

        $string = '<pc id="source1" dataRefStart="source1">ciao</pc>';
        $expected = '<ph id="source1_1" ctype="'
            . CTypeEnum::PC_OPEN_DATA_REF->value . '" equiv-text="base64:TlVMTA==" x-orig="PHBjIGlkPSJzb3VyY2UxIiBkYXRhUmVmU3RhcnQ9InNvdXJjZTEiPg=="/>ciao<ph id="source1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF->value . '" equiv-text="base64:TlVMTA==" x-orig="PC9wYz4="/>';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_add_id_to_ph_ec_sc_when_is_missing(): void
    {
        $map = [
            'd1' => '&lt;x/&gt;',
            'd2' => '&lt;br\/&gt;',
        ];

        $string = '<ph dataRef="d1" id="d1"/><ec dataRef="d2" startRef="5" subType="xlf:b" type="fmt"/>';
        $expected = '<ph id="d1" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:Jmx0O3gvJmd0Ow==" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iZDEiLz4="/><ph id="d2" ctype="'
            . CTypeEnum::EC_DATA_REF->value . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PGVjIGRhdGFSZWY9ImQyIiBzdGFydFJlZj0iNSIgc3ViVHlwZT0ieGxmOmIiIHR5cGU9ImZtdCIvPg=="/>';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_replace_and_restore_data_with_ph_with_same_ids(): void
    {
        $map = [
            'd1' => '&lt;br\/&gt;',
        ];

        $string = 'San Francisco, CA<ph dataRef="d1" id="1" subType="xlf:lb" type="fmt"/>650 California St, Ste 2950<ph dataRef="d1" id="2" subType="xlf:lb" type="fmt"/>San Francisco<ph dataRef="d1" id="3" subType="xlf:lb" type="fmt"/>CA 94108';
        $expected = 'San Francisco, CA<ph id="1" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iMSIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>650 California St, Ste 2950<ph id="2" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iMiIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>San Francisco<ph id="3" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:Jmx0O2JyXC8mZ3Q7" x-orig="PHBoIGRhdGFSZWY9ImQxIiBpZD0iMyIgc3ViVHlwZT0ieGxmOmxiIiB0eXBlPSJmbXQiLz4="/>CA 94108';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function do_nothing_with_ph_tags_without_dataref(): void
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

    #[Test]
    public function do_nothing_with_empty_map(): void
    {
        $map = [];

        $string = '<ph id="mtc_1" dataRef="x5" equiv-text="base64:Jmx0O2gyJmd0Ow=="/>Aanvullende richtlijnen voor hosts van privékamers en gedeelde ruimtes<ph id="mtc_2" equiv-text="base64:Jmx0Oy9oMiZndDs="/> stellen.';
        $expected = '<ph id="mtc_1" dataRef="x5" equiv-text="base64:Jmx0O2gyJmd0Ow=="/>Aanvullende richtlijnen voor hosts van privékamers en gedeelde ruimtes<ph id="mtc_2" equiv-text="base64:Jmx0Oy9oMiZndDs="/> stellen.';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    #[Test]
    public function can_replace_data(): void
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
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JHtBTU9VTlR9" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> din <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JHtSSURFUn0=" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/>?';

        $this->assertEquals($expected, $dataReplacer->replace($string));
    }

    #[Test]
    public function can_replace_and_restore_data(): void
    {
        $map = [
            'source1' => '${AMOUNT}',
            'source2' => '${RIDER}',
        ];

        $string = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';
        $expected = 'Hai raccolto <ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JHtBTU9VTlR9" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>  da <ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JHtSSURFUn0=" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/>?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function idempotent_on_empty_map(): void
    {
        $map = [];

        $string = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';
        $expected = 'Hai raccolto <ph id="source1" dataRef="source1"/>  da <ph id="source2" dataRef="source2"/>?';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_replace_and_restore_data_test_2(): void
    {
        // sample test
        $map = [
            'source1' => '${recipientName}'
        ];

        $string = '<ph id="source1" dataRef="source1"/> changed the address';
        $expected = '<ph id="source1" ctype="' . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));

        // EC tag
        $string = '<ec id="source1" dataRef="source1"/> changed the address';
        $expected = '<ph id="source1" ctype="' . CTypeEnum::EC_DATA_REF->value . '" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ==" x-orig="PGVjIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> changed the address';
        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_replace_and_restore_data_test_3(): void
    {
        // more complex test
        $map = [
            'source1' => '${recipientName}',
            'source2' => 'Babbo Natale',
            'source3' => 'La Befana',
        ];

        $string = '<ph id="source1" dataRef="source1"/> lorem <ec id="source2" dataRef="source2"/> ipsum <sc id="source3" dataRef="source3"/> changed';
        $expected = '<ph id="source1" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JHtyZWNpcGllbnROYW1lfQ==" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/> lorem <ph id="source2" ctype="'
            . CTypeEnum::EC_DATA_REF->value . '" equiv-text="base64:QmFiYm8gTmF0YWxl" x-orig="PGVjIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/> ipsum <ph id="source3" ctype="'
            . CTypeEnum::SC_DATA_REF->value . '" equiv-text="base64:TGEgQmVmYW5h" x-orig="PHNjIGlkPSJzb3VyY2UzIiBkYXRhUmVmPSJzb3VyY2UzIi8+"/> changed';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function can_replace_and_restore_data_test_4(): void
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
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:e1JpZGVyIEZpcnN0IE5hbWV9" x-orig="PHBoIGlkPSJzb3VyY2UxIiBkYXRhUmVmPSJzb3VyY2UxIi8+"/>,<ph id="source2" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UyIiBkYXRhUmVmPSJzb3VyY2UyIi8+"/><ph id="source3" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2UzIiBkYXRhUmVmPSJzb3VyY2UzIi8+"/><ph id="source4" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2U0IiBkYXRhUmVmPSJzb3VyY2U0Ii8+"/><ph id="source5" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2U1IiBkYXRhUmVmPSJzb3VyY2U1Ii8+"/>Thanks for reaching out.<ph id="source6" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2U2IiBkYXRhUmVmPSJzb3VyY2U2Ii8+"/><ph id="source7" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2U3IiBkYXRhUmVmPSJzb3VyY2U3Ii8+"/><ph id="source8" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2U4IiBkYXRhUmVmPSJzb3VyY2U4Ii8+"/><ph id="source9" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2U5IiBkYXRhUmVmPSJzb3VyY2U5Ii8+"/><ph id="source10" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxMCIgZGF0YVJlZj0ic291cmNlMTAiLz4="/>Vouchers can be used to treat customers or employees by covering the cost of rides and meals.<ph id="source11" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxMSIgZGF0YVJlZj0ic291cmNlMTEiLz4="/><ph id="source12" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxMiIgZGF0YVJlZj0ic291cmNlMTIiLz4="/><ph id="source13" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2UxMyIgZGF0YVJlZj0ic291cmNlMTMiLz4="/><ph id="source14" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxNCIgZGF0YVJlZj0ic291cmNlMTQiLz4="/><ph id="source15" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxNSIgZGF0YVJlZj0ic291cmNlMTUiLz4="/>To start creating vouchers:<ph id="source16" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxNiIgZGF0YVJlZj0ic291cmNlMTYiLz4="/><ph id="source17" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UxNyIgZGF0YVJlZj0ic291cmNlMTciLz4="/><ph id="source18" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDticiZhbXA7Z3Q7" x-orig="PHBoIGlkPSJzb3VyY2UxOCIgZGF0YVJlZj0ic291cmNlMTgiLz4="/><ph id="source19" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDsvZGl2JmFtcDtndDs=" x-orig="PHBoIGlkPSJzb3VyY2UxOSIgZGF0YVJlZj0ic291cmNlMTkiLz4="/><ph id="source20" ctype="'
            . CTypeEnum::PH_DATA_REF->value . '" equiv-text="base64:JmFtcDtsdDtkaXYmYW1wO2d0Ow==" x-orig="PHBoIGlkPSJzb3VyY2UyMCIgZGF0YVJlZj0ic291cmNlMjAiLz4="/>1.';

        $dataReplacer = new DataRefReplacer($map);
        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function test_can_replace_sc_and_ec_tags_with_dataref(): void
    {
        $map = [
            'd1' => '<b>',
            'd2' => '</b>',
        ];

        $string = '<sc dataRef="d1" id="1"/><ec dataRef="d2" id="2" startRef="1"/>';
        $expected = '<ph id="1" ctype="' . CTypeEnum::SC_DATA_REF->value
            . '" equiv-text="base64:' . base64_encode($map['d1']) . '" x-orig="'
            . base64_encode('<sc dataRef="d1" id="1"/>') . '"/>'
            . '<ph id="2" ctype="' . CTypeEnum::EC_DATA_REF->value
            . '" equiv-text="base64:' . base64_encode($map['d2']) . '" x-orig="'
            . base64_encode('<ec dataRef="d2" id="2" startRef="1"/>') . '"/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function test_can_replace_opening_pc_with_missing_data_ref_start(): void
    {
        $map = [
            'd1' => 'X',
        ];

        $string = '<pc id="d1" dataRefEnd="d1">x</pc>';
        $expected = '<ph id="d1_1" ctype="' . CTypeEnum::PC_OPEN_DATA_REF->value
            . '" equiv-text="base64:' . base64_encode($map['d1']) . '" x-orig="'
            . base64_encode('<pc id="d1" dataRefEnd="d1">') . '"/>x<ph id="d1_2" ctype="'
            . CTypeEnum::PC_CLOSE_DATA_REF->value . '" equiv-text="base64:' . base64_encode($map['d1'])
            . '" x-orig="' . base64_encode('</pc>') . '"/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function test_can_replace_self_closed_pc_tag_with_dataref_start(): void
    {
        $map = [
            'd1' => 'Hello',
        ];

        $string = '<pc id="d1" dataRefStart="d1"/>';
        $expected = '<ph id="d1_1" ctype="' . CTypeEnum::PC_SELF_CLOSE_DATA_REF->value
            . '" equiv-text="base64:' . base64_encode($map['d1']) . '" x-orig="'
            . base64_encode($string) . '"/>';

        $dataReplacer = new DataRefReplacer($map);

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

    #[Test]
    public function test_restore_returns_input_when_map_is_empty(): void
    {
        $dataReplacer = new DataRefReplacer([]);
        $string = '<ph id="x" ctype="' . CTypeEnum::PH_DATA_REF->value
            . '" equiv-text="base64:YQ==" x-orig="PHBoIGlkPSJ4IiBkYXRhUmVmPSJ4Ii8+"/>';

        $this->assertSame($string, $dataReplacer->restore($string));
    }

    #[Test]
    public function test_restore_recurses_into_children_nodes(): void
    {
        $dataReplacer = new DataRefReplacer(['x' => 'a']);
        $inner = '<ph id="x" ctype="' . CTypeEnum::PH_DATA_REF->value
            . '" equiv-text="base64:YQ==" x-orig="' . base64_encode('<ph id="x" dataRef="x"/>') . '"/>';
        $string = '<g>' . $inner . '</g>';
        $expected = '<g><ph id="x" dataRef="x"/></g>';

        $this->assertSame($expected, $dataReplacer->restore($string));
    }

    #[Test]
    public function test_can_replace_ph_with_empty_string_values_in_original_map(): void
    {
        $map = [
            'd1' => '',
        ];

        $dataReplacer = new DataRefReplacer($map);
        $string = '<ph dataRef="d1" id="d1"/>';
        $expected = '<ph id="d1" ctype="' . CTypeEnum::PH_DATA_REF->value
            . '" equiv-text="base64:TlVMTA==" x-orig="' . base64_encode($string) . '"/>';

        $this->assertEquals($expected, $dataReplacer->replace($string));
        $this->assertEquals($string, $dataReplacer->restore($expected));
    }

}
