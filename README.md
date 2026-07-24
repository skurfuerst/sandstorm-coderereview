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

See [`.claude/skills/code-review/SKILL.md`](.claude/skills/code-review/SKILL.md).
