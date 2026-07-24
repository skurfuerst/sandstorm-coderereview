# sandstorm-coderereview

Experimental sandstorm code review skill.

A rudimentary code-review skill built around the deep vs. shallow module idea from
John Ousterhout's *A Philosophy of Software Design*. It reviews code (PHP examples)
across five dimensions: module depth, complexity smells (`if`/ternary/nullable),
why-comments, domain-first package structure, and value objects over primitives.

See [`.claude/skills/code-review/SKILL.md`](.claude/skills/code-review/SKILL.md).
