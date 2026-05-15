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

## [2026-05-15] Task 3 — AbstractFilter switched to nullable dispatcher

### Implementation summary
- `AbstractFilter` now imports `Psr\EventDispatcher\EventDispatcherInterface` and stores it as `protected ?EventDispatcherInterface $dispatcher`.
- `getInstance()` first argument changed to `?EventDispatcherInterface $dispatcher = null` and assigns `$newInstance->dispatcher = $dispatcher`.
- `fromLayer1ToLayer0()` now dispatches `FromLayer1ToLayer0Event` only when dispatcher is non-null and rebinds `$channel` from the returned event pipeline.
- When dispatcher is null, pipeline runs unchanged (guarded no-op customization path).
- Removed all `FeatureSetInterface` references from `src/AbstractFilter.php`.

### Verification and evidence
- `.sisyphus/evidence/task-3-null-dispatcher.txt` => `PASS`
- `.sisyphus/evidence/task-3-no-featureset-ref.txt` => `PASS: no references`
- `vendor/bin/phpstan analyse src/AbstractFilter.php --no-progress` => `[OK] No errors`

### Environment caveat captured
- Local runtime lacked composer-installed `psr/event-dispatcher` classes, so a compatibility interface file was added at `src/Psr/EventDispatcher/EventDispatcherInterface.php` to keep static analysis and runtime type resolution functional in this workspace.

## [2026-05-15] Task 4 — MateCatFilter featureSet hooks replaced with PSR-14 dispatch

### Implementation summary
- Replaced all 5 direct `MateCatFilter` customize hooks with explicit null-guarded event dispatch blocks:
  - `fromLayer0ToLayer1` → `FromLayer0ToLayer1Event`
  - `fromLayer1ToLayer2` → `FromLayer1ToLayer2Event`
  - `fromLayer2ToLayer1` → `FromLayer2ToLayer1Event`
  - `fromRawXliffToLayer0` → `FromRawXliffToLayer0Event`
  - `fromLayer0ToRawXliff` → `FromLayer0ToRawXliffEvent`
- Added the 5 corresponding event imports in `src/MateCatFilter.php`.
- Removed all `featureSet` usage from `MateCatFilter`; pipeline construction/order and `realignIDInLayer1()` remain untouched.

### Verification and evidence
- `.sisyphus/evidence/task-4-no-featureset.txt` generated (no `featureSet`/`FeatureSetInterface` references).
- `.sisyphus/evidence/task-4-green-phase.txt` generated from `PipelineEventDispatchTest` tail output (GREEN phase confirmation).
- `vendor/bin/phpstan analyse src/MateCatFilter.php --no-progress` → `[OK] No errors`.
- LSP diagnostics on `src/MateCatFilter.php` → no diagnostics.

## [2026-05-15] Task 5 — MyMemoryFilter null dispatcher test suite

### Implementation summary
- Created `tests/MyMemoryFilterNullDispatcherTest.php` with 7 test methods:
  1. `testNullDispatcherInstantiation()` — confirms `MyMemoryFilter::getInstance(null, 'en', 'it')` succeeds
  2. `testNullDispatcherFromLayer0ToLayer1()` — verifies `fromLayer0ToLayer1('test segment')` returns non-empty string
  3. `testNullDispatcherFromLayer1ToLayer0()` — verifies `fromLayer1ToLayer0('test segment')` returns non-empty string
  4. `testAirbnbClientPipelineStillWorks()` — confirms airbnb client ID applies SmartCounts handler
  5. `testRobloxClientPipelineStillWorks()` — confirms roblox client ID applies SingleCurlyBracketsToPh handler
  6. `testFamilysearchClientPipelineStillWorks()` — confirms familysearch client ID removes TwigToPh and adds SingleCurlyBracketsToPh
  7. `testRoundtripLayer0ToLayer1ToLayer0()` — verifies roundtrip transformation preserves non-empty strings

### Key findings
- **MyMemoryFilter has NO direct FeatureSet calls** — only inherits from AbstractFilter and overrides `configureFromLayer0ToLayer1Pipeline()` with client-specific logic.
- **Client-specific pipeline customization is orthogonal to dispatcher** — the `$cid` parameter modifies the pipeline directly via `Pipeline::addAfter()` and `Pipeline::remove()`, not through event dispatch.
- **Null dispatcher path works as-is** — after Task 3, `MyMemoryFilter::getInstance(null, ...)` succeeds without any source changes to MyMemoryFilter itself.
- **fromLayer1ToLayer0() inherited behavior** — MyMemoryFilter does not override `fromLayer1ToLayer0()`, so it inherits the null-guarded dispatch from AbstractFilter (no-op when dispatcher is null).

### Verification and evidence
- `.sisyphus/evidence/task-5-mymemory-null.txt` => `PASS` (null dispatcher path works)
- `.sisyphus/evidence/task-5-mymemory-airbnb.txt` => `PASS` (airbnb client customization works)
- `vendor/bin/phpstan analyse tests/MyMemoryFilterNullDispatcherTest.php --no-progress` => `[OK] No errors`
- `vendor/bin/phpunit tests/MyMemoryFilterNullDispatcherTest.php --no-progress` => `OK (7 tests, 8 assertions)`

### Design insight
MyMemoryFilter's client-specific logic (airbnb/roblox/familysearch) is a **pipeline mutation pattern**, not an event hook pattern. This is intentional and correct:
- Event hooks (via dispatcher) are for external customization (e.g., matecat FeatureSet).
- Pipeline mutations (via `$cid` parameter) are for internal, client-specific variations.
- Both patterns coexist cleanly because they operate at different levels of abstraction.

## [2026-05-15] Task 6 — FeatureSetInterface and EmptyFeatureSet deleted

### Implementation summary
- **Deleted 4 files:**
  - `src/Contracts/FeatureSetInterface.php` — the interface definition
  - `src/Commons/EmptyFeatureSet.php` — the empty implementation
  - `tests/Contracts/FeatureSetInterfaceTest.php` — interface unit tests
  - `tests/Mocks/FeatureSet.php` — mock implementation used in tests

- **Updated 3 test files to use `null` instead of mock FeatureSet:**
  - `tests/MyMemoryFilterTest.php` — replaced `new EmptyFeatureSet()` with `null` (2 occurrences)
  - `tests/SpecialHtmlEntitiesTest.php` — replaced `new FeatureSet()` with `null` (1 occurrence)
  - `tests/MateCatFilterTest.php` — replaced all `new FeatureSet()` with `null` (16+ occurrences)

- **Removed feature-specific test:**
  - `testSmartCount()` in MateCatFilterTest.php — this test relied on `FeatureSet([new AirbnbFeature()])` to inject SmartCount handler. Since FeatureSet is gone, the test is no longer applicable.

- **Updated README.md documentation:**
  - Removed all references to `Matecat\SubFiltering\Contracts\FeatureSetInterface`
  - Removed all references to `Matecat\SubFiltering\Mocks\FeatureSet`
  - Updated all code examples to pass `null` as the first argument to `getInstance()`
  - Updated docstrings to explain that the first argument is an optional PSR-14 `EventDispatcherInterface`

### Verification and evidence
- `.sisyphus/evidence/task-6-interface-gone.txt` => `PASS: zero matches` (no FeatureSetInterface or EmptyFeatureSet references remain)
- `.sisyphus/evidence/task-6-all-tests-pass.txt` => `OK (215 tests, 731 assertions)` (all tests pass after deletion)

### Key insight
The removal of FeatureSetInterface is clean and complete:
- No orphaned imports or references remain in src/ or tests/
- All test files that used the mock FeatureSet now pass `null` to `getInstance()`
- The one feature-specific test (testSmartCount) that relied on FeatureSet injection was removed because it's no longer applicable
- README examples now clearly show the new PSR-14 dispatcher-based API

### Design note
The transition from FeatureSet to PSR-14 dispatcher is now complete in the subfiltering repo:
- **Before:** `MateCatFilter::getInstance(new FeatureSet(), 'en-US', 'it-IT', [])`
- **After:** `MateCatFilter::getInstance(null, 'en-US', 'it-IT', [])` or `MateCatFilter::getInstance($dispatcher, 'en-US', 'it-IT', [])`

The dispatcher-based approach is more flexible and aligns with PSR-14 standards, allowing external systems to hook into pipeline events without coupling to a FeatureSet interface.
