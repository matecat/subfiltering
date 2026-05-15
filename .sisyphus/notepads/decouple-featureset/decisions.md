# Decisions — decouple-featureset-from-subfiltering

## [2026-05-15] Architectural decisions from plan

- PSR-14 `EventDispatcherInterface` is the ONLY contract between subfiltering and app
- `null` dispatcher = no events dispatched, pipeline runs unchanged (MyMemory use case)
- `?EventDispatcherInterface $dispatcher = null` replaces `FeatureSetInterface $featureSet` in `getInstance()`
- Dispatcher stored at `AbstractFilter` level (not MateCatFilter) — accessible by all subclasses
- Version: v4.1.0 (breaking change, safe since v4.0.4 was alpha never released)
- PSR-14 return type `object`: events carry mutable Pipeline; `dispatch()` returns event; caller extracts modified Pipeline from event
- Do NOT implement `StoppableEventInterface` on events
- Do NOT extract a helper method for the dispatch pattern — keep it explicit at each call site
