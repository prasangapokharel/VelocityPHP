# Contributing to VelocityPHP

Thank you for considering contributing to VelocityPHP! Contributions of all kinds are welcome — bug reports, feature requests, documentation improvements, and code contributions.

---

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [How to Report a Bug](#how-to-report-a-bug)
- [How to Request a Feature](#how-to-request-a-feature)
- [Development Setup](#development-setup)
- [Submitting a Pull Request](#submitting-a-pull-request)
- [Coding Standards](#coding-standards)
- [Commit Message Guidelines](#commit-message-guidelines)

---

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md). We expect all contributors to be respectful and inclusive.

---

## How to Report a Bug

1. Search [existing issues](https://github.com/prasangapokharel/VelocityPHP/issues) to avoid duplicates.
2. If no existing issue matches, open a new one using the **Bug Report** template.
3. Include:
   - PHP version
   - VelocityPHP version
   - Steps to reproduce
   - Expected vs actual behavior
   - Any relevant logs or stack traces

---

## How to Request a Feature

1. Search [existing issues](https://github.com/prasangapokharel/VelocityPHP/issues) first.
2. Open a new issue using the **Feature Request** template.
3. Describe the use case clearly and explain why it would benefit other users.

---

## Development Setup

```bash
# Clone the repository
git clone https://github.com/prasangapokharel/VelocityPHP.git
cd VelocityPHP

# Copy environment config
cp .env.example .env

# Install dev dependencies (for tests)
composer install

# Start the dev server
php start.php
```

---

## Submitting a Pull Request

1. **Fork** the repository and create a branch from `main`:
   ```bash
   git checkout -b feature/my-new-feature
   ```

2. **Make your changes.** Follow the [Coding Standards](#coding-standards) below.

3. **Write or update tests** if applicable.

4. **Run the test suite** to ensure nothing is broken:
   ```bash
   composer test
   ```

5. **Commit your changes** following the [Commit Message Guidelines](#commit-message-guidelines).

6. **Push** your branch and open a pull request against `main`.

7. Fill in the pull request template — describe what you changed and why.

8. A maintainer will review your PR. Please be patient and responsive to feedback.

---

## Coding Standards

- Follow **PSR-12** coding style.
- Use **PHP 7.4+** compatible syntax (no PHP 8-only features unless clearly documented).
- All public methods **must** have PHPDoc blocks.
- Keep functions **small and focused** — single responsibility principle.
- Avoid external runtime dependencies.

---

## Commit Message Guidelines

Use the following format:

```
type(scope): short summary in present tense

Optional longer description explaining the why.

Refs #issue-number
```

**Types:**
| Type | When to use |
|---|---|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation only |
| `refactor` | Code change that doesn't fix a bug or add a feature |
| `test` | Adding or updating tests |
| `chore` | Build process, tooling, CI changes |
| `perf` | Performance improvement |

**Examples:**
```
feat(cache): add remember() helper with TTL support
fix(router): prevent greedy matching on optional trailing slash
docs(readme): add routing and middleware sections
```

---

## Co-authoring Commits

If you pair-programmed or collaborated on a change, add a `Co-authored-by` trailer:

```
Co-authored-by: Name <email@example.com>
```

---

Thank you for helping make VelocityPHP better!
