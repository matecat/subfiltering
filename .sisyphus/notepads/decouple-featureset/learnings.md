# Learnings — decouple-featureset-from-subfiltering

## [2026-05-15] Session ses_1d2823fe3ffeiA85ULv4p1uLMV — Agent A scope (Tasks 1–6, subfiltering repo only)

### Repo facts
- Subfiltering repo: `/home/hashashiyyin/PhpstormProjects/subfiltering/`
- PHP >=8.3, PHPUnit ^12
- Source repo IS in sync with vendor in matecat — both have 6-method FeatureSetInterface
- `AbstractFilter::fromLayer1ToLayer0()` (line 170) also calls `customizeFromLayer1ToLayer0()` — dispatcher must live at AbstractFilter level
- `MyMemoryFilter` does NOT call customize methods directly — uses `configureFromLayer0ToLayer1Pipeline` override
- `getInstance()` lives in `AbstractFilter` (line 101) — single point of change for signature

### Guardrail (HARD)
- Agent A MUST NOT touch any file under `/home/hashashiyyin/PhpstormProjects/matecat/`
- Scope: Tasks 1–6 only
- After Tasks 1–6 complete + committed, Agent B is spawned for Tasks 7–10 in matecat repo

## [2026-05-15] Task 1 — PSR-14 Event Classes Created

### Implementation Summary
- Added `psr/event-dispatcher:^1.0` to composer.json `require` section
- Created `src/Events/` directory with 6 event classes:
  - `FromLayer0ToLayer1Event.php`
  - `FromLayer1ToLayer2Event.php`
  - `FromLayer2ToLayer1Event.php`
  - `FromRawXliffToLayer0Event.php`
  - `FromLayer0ToRawXliffEvent.php`
  - `FromLayer1ToLayer0Event.php`

### Event Class Pattern
Each event wraps a `Pipeline` instance with getter/setter:
```php
namespace Matecat\SubFiltering\Events;
use Matecat\SubFiltering\Commons\Pipeline;

class FromLayer0ToLayer1Event {
    public function __construct(private Pipeline $pipeline) {}
    public function getPipeline(): Pipeline { return $this->pipeline; }
    public function setPipeline(Pipeline $pipeline): void { $this->pipeline = $pipeline; }
}
```

### Verification Results
- PHPStan: ✓ No errors on `src/Events/`
- PHPUnit: ✓ All 200 tests pass (721 assertions)
- Event instantiation: ✓ All 6 classes instantiate and getter/setter work
- PSR-14 interface: ✓ `Psr\EventDispatcher\EventDispatcherInterface` available
- Commit: ✓ `feat(subfiltering): add PSR-14 event classes for pipeline hooks` (dacf317)

### Key Insight
Event class names derived from FeatureSetInterface method names by stripping `customize` prefix and appending `Event`:
- `customizeFromLayer0ToLayer1()` → `FromLayer0ToLayer1Event`
- `customizeFromLayer1ToLayer2()` → `FromLayer1ToLayer2Event`
- etc.

This naming convention makes the event-to-hook mapping explicit and maintainable.

## [2026-05-15] Task 2 — RED dispatch test suite scaffolded

### RED-phase test coverage added
- Added `tests/Events/PipelineEventDispatchTest.php` with PHPUnit 12 `#[DataProvider]` usage.
- Added a PSR-14-compatible spy dispatcher (`SpyDispatcher`) that captures dispatched events.
- Added explicit RED-phase tests for:
  - `fromLayer0ToLayer1` dispatch expectation
  - null dispatcher path (`testNullDispatcherRunsClean`)
  - mutable pipeline behavior through dispatched event
  - `fromLayer1ToLayer0` dispatch expectation
  - all 6 hook points via one data-provider test

### Expected RED failure observed
- Running only this test file fails with `TypeError` at `AbstractFilter::getInstance()` because arg #1 is still typed as `FeatureSetInterface`.
- This is the intended RED signal until dispatcher wiring lands in follow-up tasks.

### Evidence artifacts
- `.sisyphus/evidence/task-2-red-phase.txt` stores failing PHPUnit tail output.
- `.sisyphus/evidence/task-2-null-test-exists.txt` confirms null-dispatcher test presence.
