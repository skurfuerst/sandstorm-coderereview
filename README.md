# sandstorm-coderereview

Experimental sandstorm code review skill.

A rudimentary code-review skill built around the deep vs. shallow module idea from
John Ousterhout's *A Philosophy of Software Design*. It reviews code (PHP examples)
across eight dimensions: module depth, complexity smells (`if`/ternary/nullable),
why-comments, domain-first package structure, immutable value objects over primitives,
proximity/locality (classes that read top-to-bottom, related classes kept close),
modern PHP and clean evolution (union types/enums; refactor rather than keep old code
paths), and test-first end-to-end coverage. It points at Neos ContentRepository Core as
an exemplar to study.

## Layout

- [`.claude/skills/code-review/SKILL.md`](.claude/skills/code-review/SKILL.md) — the
  skill: principles and how to review, kept lean so it stays cheap to load.
- [`.claude/skills/code-review/references/examples.md`](.claude/skills/code-review/references/examples.md)
  — PHP before/after patterns per dimension, read on demand.
- [`.claude/skills/code-review/evals/`](.claude/skills/code-review/evals/) — a small
  regression suite (six PHP sample files + `evals.json` with per-case expectations)
  used to sanity-check the skill against a no-skill baseline.
