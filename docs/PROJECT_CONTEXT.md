# YouthNexus — Project Context

## Purpose
YouthNexus is a web-based centralized management platform for youth clubs in Sri Lanka. It is a university group project supporting youth-club members, club executives, divisional/district coordination, and National Youth Services Council administration.

## Core Actors
- Youth Club Member
- Club Executive: President, Secretary, Treasurer
- Divisional/District Coordinator
- National Youth Services Council / Administration

Any additional organizational-level role must follow approved project requirements rather than being invented during implementation.

## Technology Constraints
Frontend: HTML, CSS, Vanilla JavaScript
Backend: PHP, hand-rolled/modular MVC-style architecture, PHP sessions
Database: MySQL, PDO/prepared statements

Do not introduce frameworks unless the team explicitly changes the project architecture.

## Major Functional Areas
- Authentication and authorization
- Youth club registration
- Club registration approval
- Club/member management
- Event management
- Attendance
- Finance tracking
- Club health monitoring
- Reports
- Notifications
- PDF/QR-related functionality
- Administrative coordination

Implementation status must be verified against the actual repository and accepted project state.

## Database Principles
Before implementing a feature:
1. Inspect the existing schema.
2. Identify existing tables and relationships.
3. Reuse existing fields where appropriate.
4. Do not invent fields because they appear in Figma.
5. If required data does not exist, report the schema/product decision before silently changing the design.

## Security Principles
Use PHP sessions, server-side authorization, prepared SQL statements, validation, output escaping, CSRF protection where applicable, secure file handling, and safe error handling. Never commit passwords, API keys, tokens, app passwords, or other secrets.

## UI / Design Principles
The UI should follow approved Figma/design requirements and existing project conventions. Figma is a design/reference source; it does not prove that a database field or backend capability exists.

## Project-State Principle
The actual repository, database schema, and confirmed team decisions take precedence over assumptions. `CURRENT_STATE.md` describes accepted/integrated project state, not temporary unmerged branch work.

## Shared Context
Shared project-context documents are maintained on the `project-context` branch. Feature branches must not maintain independent copies.

## Important Rule
If information is uncertain, identify it as uncertain. Never turn assumptions into project facts.
