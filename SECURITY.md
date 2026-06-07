# Security Policy

`laravelui5/odata` is an MIT-licensed OData v4 engine for Laravel. A defect here can
surface as data exposure (over-broad `$metadata`/entity-set output) or query
injection, so we take vulnerabilities seriously and appreciate responsible
disclosure.

## Reporting a vulnerability

**Please do not open a public issue for security reports.**

Report privately by **encrypted email to `security@pragmatiqu.io`**, using the PGP key
published at https://laravelui5.com/security. GitHub *private vulnerability reporting*
is also enabled on this repository.

Helpful to include:

- the affected version (`composer show laravelui5/odata`),
- a description of the issue and its impact (e.g. data exposure, query injection,
  protocol-level bypass),
- steps to reproduce or a proof of concept,
- any suggested remediation.

## What to expect

- **Acknowledgement** within **3 business days**.
- An initial assessment (severity, affected versions) shortly after.
- A coordinated fix and release; we will keep you informed of progress.
- Credit in the release notes if you wish (and consent to disclosure timing).

We ask that you give us a reasonable window to remediate before any public
disclosure.

## Supported versions

| Version | Supported |
|:--|:--|
| latest `1.x` minor | ✅ |
| older `1.x` minors | ⚠️ by arrangement |

## Scope

**In scope** — vulnerabilities in the `laravelui5/odata` package itself: the OData v4
request pipeline, `$metadata` generation, the query/filter handling, and the entity
set abstractions it ships.

**Out of scope** —

- **Host misconfiguration.** Exposing an entity set without the appropriate
  authentication/authorization is the host's (and `laravelui5/core`'s) responsibility;
  this engine answers OData requests it is given.
- **`laravelui5/core` and `laravelui5/sdk`** — report against their repositories.
- **Third-party dependencies** — report upstream; tell us if it affects this package
  so we can pin or mitigate.

See https://laravelui5.com/security for the full ecosystem policy.
