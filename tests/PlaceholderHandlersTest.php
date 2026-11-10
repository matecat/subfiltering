<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\SingleSquareBrackets;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\MateCatFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The handlers that lock a placeholder syntax into a `<ph>` tag: `@@x@@`, `[[x]]`, `${x}`, `{x}`,
 * `%%x%%` and `[x]`. A match that holds whitespace, also written as an entity, or an escaped tag
 * is not a placeholder.
 */
class PlaceholderHandlersTest extends TestCase
{

    /**
     * @param list<string> $handlers
     * @param string $db
     *
     * @return void
     */
    #[DataProvider('whitespaceInsidePlaceholderProvider')]
    public function testLeavesPlaceholderWithWhitespaceAlone(array $handlers, string $db): void
    {
        $filter = $this->getFilterInstance($handlers);

        $this->assertEquals($db, $filter->fromLayer0ToLayer1($db));
        $this->assertEquals($db, $filter->fromLayer1ToLayer0($db));
        $this->assertEquals($db, $filter->fromLayer2ToLayer0($filter->fromLayer0ToLayer2($db)));
    }

    /**
     * @return array<string, array{list<string>, string}>
     */
    public static function whitespaceInsidePlaceholderProvider(): array
    {
        $S = [InjectableFiltersTags::single_square->value];
        $DC = [InjectableFiltersTags::dollar_curly->value];
        $SC = [InjectableFiltersTags::single_curly->value];

        return [
            'single square, lone CR entity' => [$S, 'A [a&#13;b] B'],
            'single square, hex CR entity' => [$S, 'A [a&#xD;b] B'],
            'double square, lone CR entity' => [[], 'A [[a&#13;b]] B'],
            'double square, hex CR entity' => [[], 'A [[a&#xD;b]] B'],
            'snails, tab entity' => [[], 'A @@a&#09;b@@ B'],
            'snails, lone CR entity' => [[], 'A @@a&#13;b@@ B'],
            'snails, literal NBSP' => [[], "A @@a\u{00A0}b@@ B"],
            'double percent, tab entity' => [[], 'A %%a&#09;b%% B'],
            'double percent, line feed entity' => [[], 'A %%a&#10;b%% B'],
            'double percent, literal NBSP' => [[], "A %%a\u{00A0}b%% B"],
            'dollar curly, tab entity' => [$DC, 'A ${a&#09;b} B'],
            'dollar curly, literal NBSP' => [$DC, "A \${a\u{00A0}b} B"],
            'single curly, tab entity' => [$SC, 'A {a&#09;b} B'],
            'single curly, lone CR entity' => [$SC, 'A {a&#13;b} B'],
            'single curly, literal NBSP' => [$SC, "A {a\u{00A0}b} B"],
        ];
    }

    /**
     * @param list<string> $handlers
     * @param CTypeEnum $ctype
     * @param string $placeholder
     *
     * @return void
     */
    #[DataProvider('lockedPlaceholderProvider')]
    public function testLocksPlaceholderWithoutWhitespace(array $handlers, CTypeEnum $ctype, string $placeholder): void
    {
        $filter = $this->getFilterInstance($handlers);
        $db = 'A ' . $placeholder . ' B';
        $expectedLayer1 = 'A ' . self::ph(1, $ctype, $placeholder) . ' B';

        $this->assertEquals($expectedLayer1, $filter->fromLayer0ToLayer1($db));
        $this->assertEquals($db, $filter->fromLayer1ToLayer0($expectedLayer1));
        $this->assertEquals($db, $filter->fromLayer2ToLayer0($filter->fromLayer0ToLayer2($db)));
    }

    /**
     * @return array<string, array{list<string>, CTypeEnum, string}>
     */
    public static function lockedPlaceholderProvider(): array
    {
        $S = [InjectableFiltersTags::single_square->value];
        $DC = [InjectableFiltersTags::dollar_curly->value];
        $SC = [InjectableFiltersTags::single_curly->value];

        return [
            'single square' => [$S, CTypeEnum::SINGLE_SQUARE_BRACKETS, '[a&amp;b]'],
            'double square' => [[], CTypeEnum::DOUBLE_SQUARE_BRACKETS, '[[a&amp;b]]'],
            'snails' => [[], CTypeEnum::SNAILS, '@@a&amp;b@@'],
            'double percent' => [[], CTypeEnum::PERCENTAGES, '%%a&amp;b%%'],
            'dollar curly' => [$DC, CTypeEnum::DOLLAR_CURLY_BRACKETS, '${a&amp;b}'],
            'single curly' => [$SC, CTypeEnum::CURLY_BRACKETS, '{a&amp;b}'],
        ];
    }

    /**
     * @param string $db
     * @param string $expectedLayer1
     *
     * @return void
     */
    #[DataProvider('placeholderAfterRejectedMatchProvider')]
    public function testLocksPlaceholderAfterRejectedMatch(string $db, string $expectedLayer1): void
    {
        $filter = $this->getFilterInstance();

        $this->assertEquals($expectedLayer1, $filter->fromLayer0ToLayer1($db));
        $this->assertEquals($db, $filter->fromLayer1ToLayer0($expectedLayer1));
        $this->assertEquals($db, $filter->fromLayer2ToLayer0($filter->fromLayer0ToLayer2($db)));
    }

    /**
     * The scan goes on right after the whitespace entity, as it does after a literal space: the
     * placeholder inside the rejected text is locked too, each one where it was found.
     *
     * @return array<string, array{string, string}>
     */
    public static function placeholderAfterRejectedMatchProvider(): array
    {
        return [
            'double square' => [
                '[[a&#09;[[b]] [[b]]',
                '[[a&#09;' . self::ph(1, CTypeEnum::DOUBLE_SQUARE_BRACKETS, '[[b]]') . ' ' . self::ph(2, CTypeEnum::DOUBLE_SQUARE_BRACKETS, '[[b]]')
            ],
            'snails' => [
                '@@a&#09;@@b@@',
                '@@a&#09;' . self::ph(1, CTypeEnum::SNAILS, '@@b@@')
            ],
            'double percent' => [
                '%%a&#09;%%b%%',
                '%%a&#09;' . self::ph(1, CTypeEnum::PERCENTAGES, '%%b%%')
            ],
        ];
    }

    /**
     * @param class-string $handler
     * @param CTypeEnum $ctype
     * @param string $plain
     *
     * @return void
     */
    #[DataProvider('handlerProvider')]
    public function testDoesNotLockEscapedTag(string $handler, CTypeEnum $ctype, string $plain): void
    {
        // Each plain placeholder holds one `a`: '@@ab@@' becomes '@@a##LESSTHAN##b@@'
        $tagged = str_replace('a', 'a' . ConstantEnum::LTPLACEHOLDER->value, $plain);

        $channel = new Pipeline();
        $channel->addLast($handler);

        $this->assertEquals($tagged, $channel->transform($tagged));
        $this->assertEquals(self::ph(1, $ctype, $plain), $channel->transform($plain));
    }

    /**
     * @param class-string $handler
     * @param CTypeEnum $ctype
     * @param string $plain
     *
     * @return void
     */
    #[DataProvider('handlerProvider')]
    public function testLeavesInvalidUtf8Alone(string $handler, CTypeEnum $ctype, string $plain): void
    {
        $invalidSegment = "\xC3\x28 " . $plain;
        $validSegment = "\xC3\xA9 " . $plain;

        $channel = new Pipeline();
        $channel->addLast($handler);

        $this->assertEquals($invalidSegment, $channel->transform($invalidSegment));
        $this->assertEquals("\xC3\xA9 " . self::ph(1, $ctype, $plain), $channel->transform($validSegment));
    }

    /**
     * @return array<string, array{class-string, CTypeEnum, string}>
     */
    public static function handlerProvider(): array
    {
        return [
            'snails' => [Snails::class, CTypeEnum::SNAILS, '@@ab@@'],
            'double square' => [DoubleSquareBrackets::class, CTypeEnum::DOUBLE_SQUARE_BRACKETS, '[[ab]]'],
            'dollar curly' => [DollarCurlyBrackets::class, CTypeEnum::DOLLAR_CURLY_BRACKETS, '${ab}'],
            'single curly' => [SingleCurlyBracketsToPh::class, CTypeEnum::CURLY_BRACKETS, '{ab}'],
            'double percent' => [DoublePercentages::class, CTypeEnum::PERCENTAGES, '%%ab%%'],
            'single square' => [SingleSquareBrackets::class, CTypeEnum::SINGLE_SQUARE_BRACKETS, '[ab]'],
        ];
    }

    /**
     * @return void
     */
    public function testRejectedMatchUsesNoId(): void
    {
        $channel = new Pipeline();
        $channel->addLast(DoubleSquareBrackets::class);

        $input = '[[a&#09;b]] [[ok]]';
        $expected = '[[a&#09;b]] ' . self::ph(1, CTypeEnum::DOUBLE_SQUARE_BRACKETS, '[[ok]]');

        $this->assertEquals($expected, $channel->transform($input));
        $this->assertEquals('__mtc_1', $channel->getNextId());
    }

    /**
     * @param list<string> $handlers
     * @param string $db
     *
     * @return void
     */
    #[DataProvider('namedAndHexEntityProvider')]
    public function testLeavesNamedAndHexWhitespaceEntitiesAlone(array $handlers, string $db): void
    {
        $filter = $this->getFilterInstance($handlers);

        $this->assertEquals($db, $filter->fromLayer0ToLayer1($db));
    }

    /**
     * @return array<string, array{list<string>, string}>
     */
    public static function namedAndHexEntityProvider(): array
    {
        $S = [InjectableFiltersTags::single_square->value];

        return [
            'double square, Tab entity' => [[], '[[a&Tab;b]]'],
            'double square, nbsp entity' => [[], '[[a&nbsp;b]]'],
            'double square, hex tab entity' => [[], '[[a&#x9;b]]'],
            'single square, hex NBSP entity' => [$S, '[a&#xA0;b]'],
            'snails, NewLine entity' => [[], '@@a&NewLine;b@@'],
        ];
    }

    /**
     * @return void
     */
    public function testBothSquareHandlersWithWhitespaceEntities(): void
    {
        $handlers = [
            InjectableFiltersTags::single_square->value,
            InjectableFiltersTags::double_square->value
        ];
        $filter = $this->getFilterInstance($handlers);

        $db = '[[a&#09;b]] [x] [c&#09;d] [[y]]';
        $expectedLayer1 = '[[a&#09;b]] ' . self::ph(1, CTypeEnum::SINGLE_SQUARE_BRACKETS, '[x]') . ' [c&#09;d] ' . self::ph(2, CTypeEnum::DOUBLE_SQUARE_BRACKETS, '[[y]]');

        $this->assertEquals($expectedLayer1, $filter->fromLayer0ToLayer1($db));
        $this->assertEquals($db, $filter->fromLayer1ToLayer0($expectedLayer1));
        $this->assertEquals($db, $filter->fromLayer2ToLayer0($filter->fromLayer0ToLayer2($db)));
    }

    /**
     * @param list<string> $injectable_handlers An empty list loads the default handlers
     */
    private function getFilterInstance(array $injectable_handlers = []): MateCatFilter
    {
        return MateCatFilter::getInstance(null, 'en-US', 'it-IT', [], $injectable_handlers);
    }

    private static function ph(int $id, CTypeEnum $ctype, string $original): string
    {
        return '<ph id="mtc_' . $id . '" ctype="' . $ctype->value . '" equiv-text="base64:' . base64_encode($original) . '"/>';
    }
}
