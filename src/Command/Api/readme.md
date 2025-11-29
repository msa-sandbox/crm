### API Test Commands

This folder contains **CLI test commands** that simulate API requests without making real HTTP calls.
They’re designed for local testing and debugging of handlers, DTOs, validation, and authorization logic.

---

#### Commands (just one example)

##### `api:test:contacts:list`

Simulates **`GET /contacts`** request.

**Options:**

* `--after-id=` – pagination start ID
* `--limit=` – items per page (default: 20, max: 100)
* `--include-deleted` – include soft-deleted contacts
* `--search=` – full-text search string
* `--with=` – related entities, e.g. `leads`

**Example:**

```bash
php bin/console api:test:contacts:list --limit=10 --with=leads --search=john
```

---

#### Authorization

Each command automatically **authenticates a CLI user**
using the `CliAuthTrait`, which injects a mock JWT user into the security context.
Permissions and `Security::getUser()` behave exactly like in real API requests.

---

#### Purpose

* Quickly test handlers and DTO validation logic
* Debug permission checks and repository queries
* Inspect raw handler responses without running the HTTP stack
