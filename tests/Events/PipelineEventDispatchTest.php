<?php

namespace Psr\EventDispatcher {
    if (!interface_exists(EventDispatcherInterface::class)) {
        interface EventDispatcherInterface
        {
            public function dispatch(object $event): object;
        }
    }
}

namespace Matecat\SubFiltering\Tests\Events {

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Events\FromLayer0ToLayer1Event;
use Matecat\SubFiltering\Events\FromLayer0ToRawXliffEvent;
use Matecat\SubFiltering\Events\FromLayer1ToLayer0Event;
use Matecat\SubFiltering\Events\FromLayer1ToLayer2Event;
use Matecat\SubFiltering\Events\FromLayer2ToLayer1Event;
use Matecat\SubFiltering\Events\FromRawXliffToLayer0Event;
use Matecat\SubFiltering\MateCatFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionMethod;

class SpyDispatcher implements EventDispatcherInterface
{
    /** @var object[] */
    public array $dispatched = [];

    /** @var callable|null */
    private $onDispatch;

    public function __construct(?callable $onDispatch = null)
    {
        $this->onDispatch = $onDispatch;
    }

    public function dispatch(object $event): object
    {
        $this->dispatched[] = $event;

        if ($this->onDispatch !== null) {
            ($this->onDispatch)($event);
        }

        return $event;
    }
}

class AppendRedPhaseSuffixHandler extends AbstractHandler
{
    public function transform(string $segment): string
    {
        return $segment . '|event-mutated';
    }
}

class PipelineEventDispatchTest extends TestCase
{
    public function testFromLayer0ToLayer1DispatchesEvent(): void
    {
        $spy = new SpyDispatcher();

        $filter = $this->getFilterWithDispatcher($spy);
        $filter->fromLayer0ToLayer1('hello');

        $this->assertDispatched(FromLayer0ToLayer1Event::class, $spy);
    }

    public function testNullDispatcherRunsClean(): void
    {
        $filter = $this->getFilterWithDispatcher(null);
        $result = $filter->fromLayer0ToLayer1('hello');

        $this->assertIsString($result);
    }

    public function testEventPipelineIsMutable(): void
    {
        $spy = new SpyDispatcher(
            static function (object $event): void {
                if ($event instanceof FromLayer0ToLayer1Event) {
                    $event->getPipeline()->addFirst(AppendRedPhaseSuffixHandler::class);
                }
            }
        );

        $filter = $this->getFilterWithDispatcher($spy);
        $result = $filter->fromLayer0ToLayer1('hello');

        $this->assertStringContainsString('|event-mutated', $result);
    }

    public function testFromLayer1ToLayer0DispatchesEvent(): void
    {
        $spy = new SpyDispatcher();

        $filter = $this->getFilterWithDispatcher($spy);
        $filter->fromLayer1ToLayer0('hello');

        $this->assertDispatched(FromLayer1ToLayer0Event::class, $spy);
    }

    #[DataProvider('allSixHookPointsProvider')]
    public function testAllSixHookPointsDispatch(string $method, string $eventClass): void
    {
        $spy = new SpyDispatcher();

        $filter = $this->getFilterWithDispatcher($spy);
        $filter->{$method}('hello');

        $this->assertDispatched($eventClass, $spy);
    }

    /**
     * @return array<string,array{method:string,eventClass:class-string}>
     */
    public static function allSixHookPointsProvider(): array
    {
        return [
            'fromLayer0ToLayer1' => ['method' => 'fromLayer0ToLayer1', 'eventClass' => FromLayer0ToLayer1Event::class],
            'fromLayer1ToLayer2' => ['method' => 'fromLayer1ToLayer2', 'eventClass' => FromLayer1ToLayer2Event::class],
            'fromLayer2ToLayer1' => ['method' => 'fromLayer2ToLayer1', 'eventClass' => FromLayer2ToLayer1Event::class],
            'fromRawXliffToLayer0' => ['method' => 'fromRawXliffToLayer0', 'eventClass' => FromRawXliffToLayer0Event::class],
            'fromLayer0ToRawXliff' => ['method' => 'fromLayer0ToRawXliff', 'eventClass' => FromLayer0ToRawXliffEvent::class],
            'fromLayer1ToLayer0' => ['method' => 'fromLayer1ToLayer0', 'eventClass' => FromLayer1ToLayer0Event::class],
        ];
    }

    /**
     * A listener is not limited to adding handlers to the pipeline it was handed: it can
     * hand back a different one, and the filter must run that one instead. The replacement
     * carries the marker handler alone, so the marker in the output is the only way the
     * text could have been produced.
     */
    #[DataProvider('allSixHookPointsProvider')]
    public function testAListenerCanReplaceThePipelineOnEveryHookPoint(string $method, string $eventClass): void
    {
        $spy = new SpyDispatcher(function (object $event) use ($eventClass): void {
            $this->assertInstanceOf($eventClass, $event);

            $replacement = new Pipeline('en', 'it');
            $replacement->addLast(AppendRedPhaseSuffixHandler::class);

            $event->setPipeline($replacement);
        });

        $filter = $this->getFilterWithDispatcher($spy);

        $this->assertSame('hello|event-mutated', $filter->{$method}('hello'));
    }

    private function assertDispatched(string $eventClass, SpyDispatcher $spy): void
    {
        foreach ($spy->dispatched as $event) {
            if ($event instanceof $eventClass) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail(sprintf('Expected %s to be dispatched.', $eventClass));
    }

    private function getFilterWithDispatcher(mixed $dispatcher): MateCatFilter
    {
        $factory = new ReflectionMethod(MateCatFilter::class, 'getInstance');

        /** @var MateCatFilter $filter */
        $filter = $factory->invoke(null, $dispatcher, 'en', 'it');

        return $filter;
    }
}

}
