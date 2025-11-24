# Architecture Diagrams

This directory contains PlantUML diagrams documenting the system architecture.

## Diagrams

### 1. Architecture Layers (`architecture-layers.puml`)
Shows the four main architectural layers and their dependencies:
- Presentation Layer (API v1, v2, CLI)
- Application Layer (Services, DTOs, Transformers)
- Domain Layer (Business logic, Entities)
- Infrastructure Layer (Doctrine, Kafka, Redis, Auth)

### 2. Authentication Flow (`authentication-flow.puml`)
Illustrates the complete authentication flow:
- Initial login via Auth Service
- JWT token validation (signature, expiration, invalidation)
- Token refresh flow
- Kafka-based token invalidation

### 3. Domain Structure (`domain-structure.puml`)
Shows the DDD domain organization:
- Bounded contexts (Lead, Contact, Deal)
- Entities and Value Objects
- Services and Repositories
- Loose coupling via ID references

### 4. API Request Flow (`api-request-flow.puml`)
Sequence diagram showing the complete flow:
- POST request (create lead)
- GET request (list leads)
- Rate limiting, validation, service calls, database access

## Viewing Diagrams

### Online Viewers
- [PlantUML Online Editor](http://www.plantuml.com/plantuml/uml/)
- [PlantText](https://www.planttext.com/)

### IDE Plugins
- **PhpStorm/IntelliJ**: [PlantUML Integration](https://plugins.jetbrains.com/plugin/7017-plantuml-integration)

### Command Line
```bash
# Install PlantUML
apt install plantuml   # Ubuntu/Debian

# Generate PNG
plantuml docs/architecture-layers.puml

# Generate SVG
plantuml -tsvg docs/architecture-layers.puml

# Generate all diagrams
plantuml docs/*.puml
```

## Updating Diagrams

When making architectural changes, update the relevant diagrams to keep documentation in sync with implementation.
