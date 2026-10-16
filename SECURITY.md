# Security Policy

## Reporting a vulnerability

**Please do not report security vulnerabilities through public GitHub issues, discussions, or pull requests.**

Instead, report them privately through GitHub:

1. Go to the [Security tab](https://github.com/gbook/nidb/security) of this repository.
2. Click **Report a vulnerability**.
3. Fill out the form with as much detail as you can.

Only the maintainers can see the report. We'll discuss it with you there, work on a fix, and publish a security advisory once a fix is available.

Helpful details to include:

- The NiDB version, and the OS and PHP version it runs on
- The affected page, API endpoint, or component
- Steps to reproduce, or a proof of concept
- What an attacker could do with it (for example, read PHI, change data, or run commands)

## Supported versions

Security fixes go into the latest release only. If you're running an older version, please upgrade before reporting.

## Response

NiDB is maintained by a small team, so we can't guarantee response times. We'll try to acknowledge reports within a week, and we'll keep you updated as we work on a fix. We're glad to credit you in the advisory unless you'd rather stay anonymous.

## A note on data

NiDB often stores protected health information (PHI). When testing, please use your own installation and test data. Don't access, change, or keep data that belongs to anyone else.
