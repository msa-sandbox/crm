# CRM API Architecture Documentation

## Table of Contents
1. [Overview](#overview)
2. [Architecture Approach](#architecture-approach)
3. [Project Structure](#project-structure)
4. [API Versioning Strategy](#api-versioning-strategy)
5. [Domain-Driven Design](#domain-driven-design)
6. [Authentication & Authorization](#authentication--authorization)
7. [Rate Limiting](#rate-limiting)
8. [Data Flow Examples](#data-flow-examples)
9. [Key Principles](#key-principles)
10. [Technology Stack](#technology-stack)

---

## Overview

This is a **CRM API service** built with **Symfony 7.3**, following **Domain-Driven Design (DDD)** principles with **API versioning** to support multiple client versions simultaneously.

The service provides a RESTful API for managing CRM entities (Leads, Contacts, Deals, Tasks, etc.) with OAuth2 authentication, rate limiting, and event-driven token invalidation via Kafka.

**Key Features:**
- Domain-Driven Design with bounded contexts
- API versioning (v1, v2, etc.) with isolated presentation layer
- OAuth2 authentication with JWT tokens
- Token invalidation via Kafka events
- Rate limiting per user/IP
- Event-driven architecture
- Redis caching for performance
- Prometheus metrics for monitoring

---

## Architecture Approach

This project uses a **hybrid architecture** that combines:

1. **Domain-Driven Design (DDD)** - Business logic organized by domains
2. **Clean Architecture principles** - Separation of concerns, dependency inversion
3. **API Versioning** - Isolated presentation layer per API version

### Architecture Layers

```
┌─────────────────────────────────────────────┐
│   Presentation Layer (API v1, v2, CLI)      │  ← Controllers, DTOs, Transformers
├─────────────────────────────────────────────┤
│   Application Layer (Use Cases)             │  ← Services (Command/Query)
├─────────────────────────────────────────────┤
│   Domain Layer (Business Logic)             │  ← Entities, Value Objects, Specifications
├─────────────────────────────────────────────┤
│   Infrastructure Layer                      │  ← Doctrine, Kafka, Redis, External APIs
└─────────────────────────────────────────────┘
```

**Key principle:** Business logic (Domain) is independent of delivery mechanism (API version, CLI, events).

---

## Project Structure
Example structure:
```
src/
├── CRM/                          # Domain Layer (Business Logic)
│   ├── Lead/                     # Lead bounded context
│   │   ├── Entity/
│   │   │   └── Lead.php          # Domain entity
│   │   ├── Repository/
│   │   │   └── LeadRepository.php
│   │   ├── Service/
│   │   │   ├── Command/          # Write operations
│   │   │   │   ├── CreateLeadService.php
│   │   │   │   ├── UpdateLeadService.php
│   │   │   │   └── ConvertLeadService.php
│   │   │   └── Query/            # Read operations
│   │   │       └── LeadQueryService.php
│   │   ├── Specification/
│   │   │   └── LeadCanBeConvertedSpecification.php
│   │   ├── Exception/
│   │   │   └── LeadNotFoundException.php
│   │   └── Event/
│   │       └── LeadCreatedEvent.php
│   │
│   ├── Contact/                  # Contact bounded context
│   │   ├── Entity/
│   │   │   └── Contact.php
│   │   ├── Repository/
│   │   ├── Service/
│   │   │   ├── Command/
│   │   │   └── Query/
│   │   └── ValueObject/
│   │       ├── Email.php
│   │       └── PhoneNumber.php
│   │
│   ├── Deal/                     # Deal bounded context
│   └── Task/                     # Task bounded context
│
├── Api/                          # Presentation Layer (API)
│   ├── V1/                       # API Version 1
│   │   ├── Controller/
│   │   │   ├── LeadController.php
│   │   │   ├── ContactController.php
│   │   │   └── DealController.php
│   │   ├── Dto/
│   │   │   ├── Request/          # Input DTOs
│   │   │   │   ├── CreateLeadRequest.php
│   │   │   │   └── UpdateLeadRequest.php
│   │   │   └── Response/         # Output DTOs
│   │   │       ├── LeadResponse.php
│   │   │       └── ContactResponse.php
│   │   ├── Transformer/          # Entity → Response DTO
│   │   │   ├── LeadTransformer.php
│   │   │   └── ContactTransformer.php
│   │   └── Validator/            # Custom validation rules
│   │       └── UniqueEmailValidator.php
│   │
│   ├── V2/                       # API Version 2 (example)
│   │   ├── Controller/
│   │   ├── Dto/
│   │   └── Transformer/
│   │
│   └── Shared/                   # Shared API components
│       ├── Controller/
│       │   └── AbstractApiController.php
│       ├── Exception/
│       │   └── ApiExceptionListener.php
│       └── Response/
│           └── ApiResponse.php
│
├── Infrastructure/               # Infrastructure Layer
│   ├── Security/
│   │   ├── JwtAuthenticator.php
│   │   ├── TokenValidator.php
│   │   └── TokenInvalidationService.php
│   ├── Kafka/
│   │   ├── Consumer/
│   │   │   └── TokenInvalidationConsumer.php
│   │   └── Producer/
│   │       └── EventProducer.php
│   ├── Redis/
│   │   └── TokenInvalidationCache.php
│   └── RateLimiter/
│       └── ApiRateLimiter.php
│
├── Shared/                       # Shared Domain Components
│   ├── ValueObject/
│   │   ├── Uuid.php
│   │   └── Money.php
│   └── Event/
│       └── DomainEvent.php
│
└── Controller/                   # General controllers (non-API)
    ├── HealthCheckController.php
    └── MetricsController.php
```

---

## API Versioning Strategy

### Why Version the Entire API?

When a new API version is released, **all endpoints** are versioned together. This approach:

- Provides clear API contracts per version
- Allows safe deployment of breaking changes
- Enables planned deprecation of old versions
- Avoids "patchwork" APIs with mixed versions

### Versioning Implementation

**URL-based versioning:**
```
GET /api/v1/leads
GET /api/v2/leads  (example)
```

### Version Isolation

Each API version has its own:
- **Controllers** - Different routing, different business logic calls
- **DTOs** - Different request/response structures
- **Transformers** - Different Entity → DTO mappings
- **Validators** - Version-specific validation rules


**The Domain Layer remains unchanged** - only the presentation (API) layer differs.

---

## Domain-Driven Design

### Bounded Contexts

Each domain (Lead, Contact, Deal, Task) is a **bounded context** with its own:
- Entities
- Repositories
- Services
- Business rules

**Key principle:** Contexts are loosely coupled. They communicate via:
- Service layer coordination
- Domain events
- Reference by ID (not object references)

### Domain Components

#### 1. **Entity**
Domain model representing a business concept:

```php
namespace App\CRM\Lead\Entity;

#[ORM\Entity]
class Lead
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private string $title;

    #[ORM\Column]
    private int $contactId;  // Reference to Contact (not object!)

    #[ORM\Column]
    private string $status;

    public function __construct(string $title, int $contactId)
    {
        $this->title = $title;
        $this->contactId = $contactId;
        $this->status = 'new';
    }

    // Business method
    public function markAsQualified(): void
    {
        if ($this->status === 'converted') {
            throw new \DomainException('Cannot qualify converted lead');
        }
        $this->status = 'qualified';
    }
}
```

#### 2. **Repository**
Data access layer (queries only):

```php
namespace App\CRM\Lead\Repository;

class LeadRepository extends ServiceEntityRepository
{
    public function save(Lead $lead): void
    {
        $this->getEntityManager()->persist($lead);
        $this->getEntityManager()->flush();
    }

    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }
}
```

#### 3. **Service (Use Case)**
Business logic orchestration:

```php
namespace App\CRM\Lead\Service\Command;

class CreateLeadService
{
    public function __construct(
        private LeadRepository $leadRepository,
        private ContactQueryService $contactQueryService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function create(string $title, int $contactId): Lead
    {
        // 1. Validate contact exists
        if (!$this->contactQueryService->exists($contactId)) {
            throw new ContactNotFoundException();
        }

        // 2. Create lead
        $lead = new Lead($title, $contactId);

        // 3. Persist
        $this->leadRepository->save($lead);

        // 4. Dispatch event
        $this->eventDispatcher->dispatch(
            new LeadCreatedEvent($lead->getId())
        );

        return $lead;
    }
}
```

#### 4. **Value Object**
Immutable objects representing values:

```php
namespace App\CRM\Contact\ValueObject;

class Email
{
    private string $value;

    public function __construct(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        $this->value = strtolower($email);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }
}
```

#### 5. **Specification**
Business rules as reusable objects:

```php
namespace App\CRM\Lead\Specification;

class LeadCanBeConvertedSpecification
{
    public function isSatisfiedBy(Lead $lead): bool
    {
        return $lead->getStatus() === 'qualified'
            && $lead->getContactId() !== null;
    }
}
```

---

## Authentication & Authorization

### Overview

The system uses **OAuth2 with JWT tokens** for authentication. The application **does not issue tokens** - it only validates them against an external **Auth Service**.

### Authentication Flow

```
┌─────────┐                  ┌──────────────┐                ┌─────────────┐
│ Client  │                  │  CRM API     │                │ Auth Service│
└────┬────┘                  └──────┬───────┘                └──────┬──────┘
     │                              │                               │
     │  1. POST /auth/login         │                               │
     ├─────────────────────────────>│  2. Forward credentials       │
     │                              ├──────────────────────────────>│
     │                              │                               │
     │                              │  3. Return access token (24h) │
     │                              │<──────────────────────────────┤
     │  4. Return tokens            │                               │
     │<─────────────────────────────┤                               │
     │                              │                               │
     │  5. GET /api/v1/leads        │                               │
     │     Authorization: Bearer XX │                               │
     ├─────────────────────────────>│                               │
     │                              │  6. Validate JWT signature    │
     │                              │     (using public key)        │
     │                              │                               │
     │                              │  7. Check Redis for           │
     │                              │     token invalidation        │
     │                              │                               │
     │  8. Return data              │                               │
     │<─────────────────────────────┤                               │
     │                              │                               │
```

### Token Validation

**JWT Token validation happens in 3 steps:**

1. **Signature verification** using public key (cryptographic validation)
2. **Expiration check** (24 hours TTL)
3. **Invalidation check** against Redis cache

```php
namespace App\Infrastructure\Security;

class TokenValidator
{
    public function __construct(
        private string $publicKeyPath,
        private TokenInvalidationService $invalidationService
    ) {}

    public function validate(string $token): TokenPayload
    {
        // 1. Verify signature with public key
        try {
            $decoded = JWT::decode($token, $this->getPublicKey(), ['RS256']);
        } catch (SignatureInvalidException $e) {
            throw new InvalidTokenException('Invalid token signature');
        }

        // 2. Check expiration
        if ($decoded->exp < time()) {
            throw new ExpiredTokenException('Token has expired');
        }

        // 3. Check invalidation timestamp in Redis
        $userId = $decoded->sub;
        $tokenIssuedAt = $decoded->iat;

        if ($this->invalidationService->isInvalidated($userId, $tokenIssuedAt)) {
            throw new TokenInvalidatedException(
                'Token has been invalidated. Please refresh.',
                ['should_refresh' => true]
            );
        }

        return new TokenPayload($decoded);
    }
}
```

### Token Invalidation via Kafka

**Scenario:** User permissions changed, password reset, or account locked.

**Flow:**
1. Auth Service publishes event to Kafka: `user.permissions.changed`
2. CRM API consumes the event
3. Invalidation timestamp stored in Redis
4. All subsequent requests with old tokens are rejected

```php
namespace App\Infrastructure\Kafka\Consumer;

class TokenInvalidationConsumer
{
    public function __construct(
        private TokenInvalidationService $invalidationService
    ) {}

    public function consume(KafkaMessage $message): void
    {
        $event = json_decode($message->body, true);

        // Event structure:
        // {
        //   "user_id": 123,
        //   "invalidate_before": "2025-01-15T10:30:00Z",
        //   "reason": "permissions_changed"
        // }

        $this->invalidationService->invalidate(
            userId: $event['user_id'],
            timestamp: strtotime($event['invalidate_before'])
        );
    }
}
```

**Redis storage:**
```
Key:   token_invalidation:user:123
Value: 1736935800  (Unix timestamp)
TTL:   24 hours
```

### Token Refresh Flow

When API detects invalidated token, it returns `401 Unauthorized` with hint:

```json
{
  "error": "token_invalidated",
  "message": "Your session has been updated. Please refresh your token.",
  "should_refresh": true
}
```

Client then calls Auth Service to refresh the token:

```
POST /auth/refresh
Authorization: Bearer <old_access_token>

Response:
{
  "access_token": "new_token_here",
  "expires_in": 86400
}
```

### Long-lived Access Tokens

**Why 24 hours?**
- Reduces refresh requests
- Better UX (less interruptions)
- Mitigated by Kafka-based invalidation

**Trade-off:**
- ✅ Better performance (fewer auth service calls)
- ✅ Better UX (no mid-session logouts)
- ⚠️ Security: Invalidation must be immediate (via Kafka)

---

## Rate Limiting

### Implementation

Rate limiting is applied per user (authenticated) or per IP (anonymous) using **Symfony Rate Limiter** with **Redis backend**.

### Configuration

```yaml
# config/packages/rate_limiter.yaml
framework:
  rate_limiter:
    api_authenticated:
      policy: 'token_bucket'
      limit: 1000
      rate: { interval: '1 hour', amount: 1000 }

    api_anonymous:
      policy: 'sliding_window'
      limit: 100
      rate: { interval: '1 hour', amount: 100 }
```

### Usage in Controllers

```php
namespace App\Api\Shared\Controller;

use Symfony\Component\HttpKernel\Attribute\RateLimit;

abstract class AbstractApiController extends AbstractController
{
    #[RateLimit(limit: 1000, period: '1 hour')]  // Authenticated users
    protected function rateLimit(): void {}
}
```

### Rate Limit Headers

API returns standard rate limit headers:

```
HTTP/1.1 200 OK
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 847
X-RateLimit-Reset: 1736939400
```

When limit exceeded:
```
HTTP/1.1 429 Too Many Requests
Retry-After: 3600
{
  "error": "rate_limit_exceeded",
  "message": "Too many requests. Please try again in 1 hour."
}
```

---

## Data Flow Examples

### Example 1: GET /api/v1/leads (with related contacts)

```
HTTP Request
    ↓
1. JwtAuthenticator validates token
    ↓
2. RateLimiter checks limit
    ↓
3. LeadController::list()
    ↓
4. LeadQueryService::findAll()
    ↓
5. LeadRepository::findAll()  [SQL: SELECT * FROM leads]
    ↓
6. For each lead, get contactId
    ↓
7. ContactQueryService::findByIds([1,2,3])
    ↓
8. ContactRepository::findByIds()  [SQL: SELECT * FROM contacts WHERE id IN (...)]
    ↓
9. Service returns [LeadWithContact] DTOs
    ↓
10. LeadTransformer::transformCollection()
     ↓ (calls ContactTransformer for each)
11. ContactTransformer::transform()
    ↓
12. JSON Response with Lead + Contact data
```

### Example 2: POST /api/v1/leads (create lead with contact)

```
HTTP Request (JSON body)
    ↓
1. JwtAuthenticator validates token
    ↓
2. Symfony deserializes JSON → CreateLeadRequest DTO
    ↓
3. Validator validates CreateLeadRequest
    ↓
4. LeadController::create()
    ↓
5. CreateLeadService::create()
    ↓
    ├→ 6. ContactQueryService::exists(contactId)  [Check contact exists]
    │       ↓
    │   ContactRepository::find()
    │       ↓
    │   Return true/false
    ↓
7. Create Lead entity: new Lead($title, $contactId)
    ↓
8. LeadRepository::save($lead)  [SQL: INSERT INTO leads]
    ↓
9. EventDispatcher::dispatch(LeadCreatedEvent)
    ↓
    ├→ LeadCreatedListener::onLeadCreated()
    │       ↓
    │   Send notification, analytics, etc.
    ↓
10. Return Lead entity
    ↓
11. LeadTransformer::transform($lead)
    ↓
12. JSON Response (201 Created)
```

---

## Key Principles

### 1. Separation of Concerns

- **Domain Layer** - Pure business logic, no framework dependencies
- **Application Layer** - Use cases, orchestrates domain
- **Presentation Layer** - API contracts, versioned independently
- **Infrastructure Layer** - External services (DB, Kafka, Redis)

### 2. Dependency Inversion

Domain does not depend on infrastructure. All dependencies point inward:

```
Infrastructure → Application → Domain
```

### 3. API Versioning Isolation

Each API version is completely isolated:
- Different controllers
- Different DTOs
- Different transformers
- **Same domain logic**

### 4. Domain Services Coordination

When operations span multiple domains, **Application Services** coordinate:

```php
// CreateLeadService coordinates Lead and Contact domains
public function createWithContact(...) {
    $contact = $this->contactService->create(...);
    $lead = new Lead(..., $contact->getId());
    $this->leadRepository->save($lead);
}
```

### 5. Event-Driven Architecture

Decouple side effects using events:
- Lead created → Send email, track analytics
- Token invalidated → Clear caches
- Deal closed → Update statistics

### 6. Security by Default

- All API routes require authentication (except public endpoints)
- Rate limiting on all endpoints
- Token invalidation via Kafka events
- JWT signature verification with public key

---

## Technology Stack

| Component | Technology | Purpose |
|-----------|-----------|---------|
| **Framework** | Symfony 7.3 | Web application framework |
| **PHP** | 8.3+ | Programming language |
| **ORM** | Doctrine ORM 3.5 | Database abstraction |
| **Database** | PostgreSQL 16 | Relational database |
| **Authentication** | Lexik JWT Bundle | JWT token handling |
| **Message Queue** | Kafka (via Enqueue) | Event streaming, token invalidation |
| **Cache** | Redis | Token invalidation cache, rate limiting |
| **Monitoring** | Prometheus Metrics Bundle | Application metrics |
| **Logging** | Monolog | Structured logging |
| **Validation** | Symfony Validator | DTO validation |
| **Serialization** | Symfony Serializer | JSON serialization |
| **Rate Limiting** | Symfony Rate Limiter | Request throttling |

---

## Development Workflow

### Running the Application

```bash
# Install dependencies
composer install

# Generate JWT keys
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

# Create database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Start development server
symfony server:start
```

### Code Quality

```bash
# Run code style fixer
composer style

# Run static analysis
composer stan

# Run tests
composer unit

# Run all checks
composer check
```

### Adding a New API Endpoint

1. **Create Request DTO** in `Api/V1/Dto/Request/`
2. **Create Response DTO** in `Api/V1/Dto/Response/`
3. **Create Controller** in `Api/V1/Controller/`
4. **Create Service** in `CRM/{Domain}/Service/Command/` or `Query/`
5. **Create Transformer** in `Api/V1/Transformer/`
6. **Add tests** in `tests/Api/V1/`

### Creating a New API Version

When breaking changes are needed:

1. Copy `Api/V1/` → `Api/V2/`
2. Update routing in `config/routes.yaml`
3. Modify V2 DTOs, controllers, transformers as needed
4. **Domain layer remains unchanged**
5. Document breaking changes in CHANGELOG.md
6. Set deprecation timeline for V1
