# Security Policy

## Supported versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

This package follows [semantic versioning](https://semver.org/). Security fixes are released against the latest `1.x` minor/patch release; there is no long-term support branch at this time.

## Reporting a vulnerability

Please **do not** open a public GitHub issue for security vulnerabilities.

Instead, report it privately using one of these channels:

- [GitHub's private vulnerability reporting](https://github.com/salioudiabate/select/security/advisories/new) for this repository, or
- Email **saliou.diiabate@gmail.com** with a description of the issue, steps to reproduce, and its potential impact.

This is a solo-maintained open-source project. There's no formal SLA, but I aim to acknowledge reports within a few days and to keep you updated as I investigate and prepare a fix. Please allow time for a patch to be released before any public disclosure.

## Scope

In scope: vulnerabilities in this package's own code — the Blade components and `resources/js/select.js` (e.g. anything that could lead to XSS through an option label/value that isn't escaped correctly).

Out of scope: the `search-method` your own Livewire component exposes for async search. Select calls it with whatever the user typed, over the wire — validating/sanitizing that input and authorizing what it's allowed to query is the same responsibility as for any other Livewire action, and is not something this package can enforce for you.

## Supported dependencies

This package targets actively supported PHP, Laravel, and Livewire versions as declared in `composer.json`. Running on an unsupported/end-of-life PHP or Laravel version is outside this policy's scope — please upgrade first.
