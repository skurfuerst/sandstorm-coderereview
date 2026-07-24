# sandstorm-coderereview

Experimental sandstorm code review skill.

A rudimentary code-review skill built around the deep vs. shallow module idea from
John Ousterhout's *A Philosophy of Software Design*. It reviews code (PHP examples)
across six dimensions: module depth, complexity smells (`if`/ternary/nullable),
why-comments, domain-first package structure, value objects over primitives, and
proximity/locality (classes that read top-to-bottom, related classes kept close).
It points at Neos ContentRepository Core as an exemplar to study.

See [`.claude/skills/code-review/SKILL.md`](.claude/skills/code-review/SKILL.md).
