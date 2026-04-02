# XOOPS 4.0 Architecture Diagrams

Visual representations of the Clean Architecture patterns used in XOOPS 4.0 modules.

## Overview: Clean Architecture Layers

```mermaid
graph TB
    subgraph External["External World"]
        Browser["🌐 Browser"]
        API["📡 API Client"]
        CLI["💻 CLI"]
    end

    subgraph Presentation["Presentation Layer"]
        Controllers["Controllers"]
        Templates["Smarty Templates"]
        APIControllers["API Controllers"]
    end

    subgraph Application["Application Layer"]
        Commands["Commands"]
        Queries["Queries"]
        Handlers["Handlers"]
    end

    subgraph Domain["Domain Layer (Core)"]
        Entities["Entities"]
        ValueObjects["Value Objects"]
        RepoInterfaces["Repository Interfaces"]
        DomainServices["Domain Services"]
        Exceptions["Domain Exceptions"]
    end

    subgraph Infrastructure["Infrastructure Layer"]
        Repositories["MySQL Repositories"]
        XoopsIntegration["XOOPS Integration"]
        Container["DI Container"]
    end

    subgraph External2["External Systems"]
        Database[("MySQL Database")]
        XoopsCore["XOOPS Core"]
    end

    Browser --> Controllers
    API --> APIControllers
    CLI --> Handlers

    Controllers --> Commands
    Controllers --> Queries
    APIControllers --> Commands
    APIControllers --> Queries

    Commands --> Handlers
    Queries --> Handlers
    Handlers --> Entities
    Handlers --> ValueObjects
    Handlers --> RepoInterfaces

    RepoInterfaces -.->|implemented by| Repositories
    Repositories --> Database
    XoopsIntegration --> XoopsCore
    Container --> Repositories
    Container --> Handlers

    classDef domain fill:#e1f5fe,stroke:#01579b
    classDef application fill:#fff3e0,stroke:#e65100
    classDef infrastructure fill:#f3e5f5,stroke:#7b1fa2
    classDef presentation fill:#e8f5e9,stroke:#2e7d32
    classDef external fill:#fce4ec,stroke:#c2185b

    class Entities,ValueObjects,RepoInterfaces,DomainServices,Exceptions domain
    class Commands,Queries,Handlers application
    class Repositories,XoopsIntegration,Container infrastructure
    class Controllers,Templates,APIControllers presentation
    class Browser,API,CLI,Database,XoopsCore external
```

## HTTP Request Flow

```mermaid
sequenceDiagram
    autonumber
    participant B as Browser
    participant C as Controller
    participant H as CommandHandler
    participant E as Entity
    participant R as Repository
    participant DB as MySQL

    B->>C: POST /articles (title, content)
    C->>C: Validate request
    C->>H: CreateArticleCommand

    H->>H: Create Value Objects
    Note over H: ArticleTitle::create()<br/>ArticleContent::create()

    H->>E: Article::create()
    Note over E: Generate ULID<br/>Set initial status

    H->>R: save(article)
    R->>DB: INSERT INTO articles...
    DB-->>R: Success

    R-->>H: void
    H-->>C: Article entity
    C->>C: Format response
    C-->>B: 201 Created + JSON
```

## Command/Query Separation (CQRS)

```mermaid
flowchart LR
    subgraph Write["Write Side (Commands)"]
        CC[CreateArticleCommand]
        UC[UpdateArticleCommand]
        DC[DeleteArticleCommand]

        CH[CreateArticleHandler]
        UH[UpdateArticleHandler]
        DH[DeleteArticleHandler]

        CC --> CH
        UC --> UH
        DC --> DH
    end

    subgraph Read["Read Side (Queries)"]
        GQ[GetArticleQuery]
        LQ[ListArticlesQuery]
        SQ[SearchArticlesQuery]

        GH[GetArticleHandler]
        LH[ListArticlesHandler]
        SH[SearchArticlesHandler]

        GQ --> GH
        LQ --> LH
        SQ --> SH
    end

    subgraph Domain["Domain Model"]
        E[Article Entity]
        R[Repository Interface]
    end

    CH --> E
    UH --> E
    DH --> R

    GH --> R
    LH --> R
    SH --> R

    style Write fill:#ffecb3
    style Read fill:#e3f2fd
    style Domain fill:#e8f5e9
```

## Entity Lifecycle

```mermaid
stateDiagram-v2
    [*] --> Draft: Article::create()

    Draft --> Published: publish()
    Draft --> Archived: archive()

    Published --> Archived: archive()

    Archived --> Draft: restore()

    note right of Draft
        Initial state
        Can edit content
    end note

    note right of Published
        Visible to public
        Limited editing
    end note

    note right of Archived
        Hidden from public
        Can be restored
    end note
```

## Value Object Validation Flow

```mermaid
flowchart TD
    Input["Raw Input String"]

    subgraph Validation["Value Object Creation"]
        Trim["trim(input)"]
        CheckMin{"length >= MIN?"}
        CheckMax{"length <= MAX?"}
        Create["new ArticleTitle(value)"]
    end

    subgraph Exceptions["Domain Exceptions"]
        TooShort["InvalidArticleTitle::tooShort()"]
        TooLong["InvalidArticleTitle::tooLong()"]
    end

    Output["Valid ArticleTitle"]

    Input --> Trim
    Trim --> CheckMin
    CheckMin -->|No| TooShort
    CheckMin -->|Yes| CheckMax
    CheckMax -->|No| TooLong
    CheckMax -->|Yes| Create
    Create --> Output

    TooShort --> Error["❌ Exception thrown"]
    TooLong --> Error

    style Output fill:#c8e6c9
    style Error fill:#ffcdd2
```

## Repository Pattern

```mermaid
flowchart TB
    subgraph Application["Application Layer"]
        Handler["Command Handler"]
    end

    subgraph Domain["Domain Layer"]
        Interface["ArticleRepositoryInterface"]
        Entity["Article Entity"]
    end

    subgraph Infrastructure["Infrastructure Layer"]
        MySQL["MySqlArticleRepository"]
        InMemory["InMemoryArticleRepository"]
    end

    subgraph Tests["Test Environment"]
        TestHandler["Test Handler"]
    end

    Handler --> Interface
    Interface -.->|implements| MySQL
    Interface -.->|implements| InMemory

    MySQL --> DB[("Production DB")]
    InMemory --> Memory["In-Memory Storage"]

    TestHandler --> InMemory

    style Interface stroke:#01579b,stroke-width:3px
    style MySQL fill:#f3e5f5
    style InMemory fill:#fff3e0
```

## Dependency Injection Container

```mermaid
flowchart TD
    subgraph Container["Service Container"]
        GetRepo["getArticleRepository()"]
        GetCreate["getCreateArticleHandler()"]
        GetUpdate["getUpdateArticleHandler()"]
        GetQuery["getGetArticleHandler()"]
        GetController["getArticleController()"]
    end

    subgraph Services["Instantiated Services"]
        Repo["MySqlArticleRepository"]
        CreateH["CreateArticleHandler"]
        UpdateH["UpdateArticleHandler"]
        QueryH["GetArticleHandler"]
        Ctrl["ArticleController"]
    end

    subgraph Deps["Dependencies"]
        DB["XoopsDatabase"]
    end

    GetRepo --> Repo
    GetCreate --> CreateH
    GetUpdate --> UpdateH
    GetQuery --> QueryH
    GetController --> Ctrl

    Repo -.->|needs| DB
    CreateH -.->|needs| Repo
    UpdateH -.->|needs| Repo
    QueryH -.->|needs| Repo
    Ctrl -.->|needs| CreateH
    Ctrl -.->|needs| UpdateH
    Ctrl -.->|needs| QueryH
```

## API Request/Response Cycle

```mermaid
sequenceDiagram
    autonumber
    participant Client as API Client
    participant Router as Router
    participant Auth as Auth Middleware
    participant Controller as API Controller
    participant Handler as Query Handler
    participant Repo as Repository

    Client->>Router: GET /api/v1/articles/01HV8X...
    Router->>Auth: Validate JWT Token
    Auth->>Auth: Decode & Verify

    alt Token Invalid
        Auth-->>Client: 401 Unauthorized
    end

    Auth->>Controller: Authenticated Request
    Controller->>Controller: Validate ULID format

    alt Invalid ULID
        Controller-->>Client: 422 Validation Error
    end

    Controller->>Handler: GetArticleQuery(id, userId)
    Handler->>Repo: findById(ArticleId)

    alt Not Found
        Repo-->>Handler: ArticleNotFound
        Handler-->>Controller: Exception
        Controller-->>Client: 404 Not Found
    end

    Repo-->>Handler: Article
    Handler->>Handler: Check permissions

    alt No Permission
        Handler-->>Controller: DomainException
        Controller-->>Client: 403 Forbidden
    end

    Handler-->>Controller: Article
    Controller->>Controller: Format JSON response
    Controller-->>Client: 200 OK + JSON
```

## ULID vs Auto-Increment ID

```mermaid
flowchart LR
    subgraph AutoIncrement["Auto-Increment (Legacy)"]
        AI1["1"]
        AI2["2"]
        AI3["3"]
        AI4["..."]
        AI1 --> AI2 --> AI3 --> AI4
    end

    subgraph ULID["ULID (XOOPS 4.0)"]
        U1["01HV8X5Z0K..."]
        U2["01HV8X5Z0M..."]
        U3["01HV8X5Z0N..."]
        U4["..."]
        U1 --> U2 --> U3 --> U4
    end

    subgraph Benefits["ULID Benefits"]
        B1["✓ Time-sortable"]
        B2["✓ URL-safe"]
        B3["✓ No collisions"]
        B4["✓ Better indexing"]
        B5["✓ Works distributed"]
    end

    ULID --> Benefits

    style AutoIncrement fill:#ffcdd2
    style ULID fill:#c8e6c9
    style Benefits fill:#e3f2fd
```

## Module Directory Structure

```mermaid
flowchart TD
    Root["modules/mymodule/"]

    subgraph Domain["Domain/ (No Dependencies)"]
        DE["Entity/"]
        DV["ValueObject/"]
        DR["Repository/"]
        DX["Exception/"]
    end

    subgraph Application["Application/ (Uses Domain)"]
        AC["Command/"]
        AQ["Query/"]
    end

    subgraph Infrastructure["Infrastructure/ (Implements)"]
        IP["Persistence/"]
        IX["Xoops/"]
        IA["Api/"]
    end

    subgraph Presentation["Presentation/ (User Interface)"]
        PC["Controller/"]
        PT["templates/"]
    end

    Root --> Domain
    Root --> Application
    Root --> Infrastructure
    Root --> Presentation

    Application -.->|depends on| Domain
    Infrastructure -.->|implements| Domain
    Presentation -.->|uses| Application

    style Domain fill:#e1f5fe
    style Application fill:#fff3e0
    style Infrastructure fill:#f3e5f5
    style Presentation fill:#e8f5e9
```

## Exception Handling Flow

```mermaid
flowchart TD
    Request["HTTP Request"]

    subgraph Controller["Controller Layer"]
        Try["try { ... }"]
        Catch["catch blocks"]
    end

    subgraph Exceptions["Exception Types"]
        Domain["DomainException"]
        NotFound["EntityNotFound"]
        Validation["ValidationException"]
        Auth["UnauthorizedException"]
    end

    subgraph Responses["HTTP Responses"]
        R400["400 Bad Request"]
        R401["401 Unauthorized"]
        R404["404 Not Found"]
        R422["422 Unprocessable"]
        R500["500 Server Error"]
    end

    Request --> Try
    Try --> Catch

    Catch --> Domain
    Catch --> NotFound
    Catch --> Validation
    Catch --> Auth

    Domain --> R400
    NotFound --> R404
    Validation --> R422
    Auth --> R401

    Catch -->|Unexpected| R500

    style R400 fill:#fff3e0
    style R401 fill:#ffcdd2
    style R404 fill:#f3e5f5
    style R422 fill:#fff3e0
    style R500 fill:#ffcdd2
```

## Test Structure

```mermaid
flowchart TB
    subgraph Unit["Unit Tests"]
        VO["Value Object Tests"]
        ET["Entity Tests"]
        HT["Handler Tests"]
    end

    subgraph Fixtures["Test Fixtures"]
        IM["InMemory Repository"]
        MO["Object Mothers"]
        FA["Factories"]
    end

    subgraph Integration["Integration Tests"]
        RT["Repository Tests"]
        AT["API Tests"]
    end

    VO --> Fixtures
    ET --> Fixtures
    HT --> Fixtures

    RT --> DB[("Test Database")]
    AT --> API["Test Server"]

    style Unit fill:#e8f5e9
    style Fixtures fill:#fff3e0
    style Integration fill:#e3f2fd
```

## Complete Request Lifecycle

```mermaid
flowchart TB
    subgraph External["1. External"]
        User["👤 User"]
        Browser["🌐 Browser"]
    end

    subgraph Presentation["2. Presentation"]
        Route["Route Matching"]
        Controller["Controller"]
        Template["Template Rendering"]
    end

    subgraph Application["3. Application"]
        Command["Command/Query"]
        Handler["Handler"]
        Validation["Input Validation"]
    end

    subgraph Domain["4. Domain"]
        Entity["Entity"]
        VO["Value Objects"]
        Rules["Business Rules"]
    end

    subgraph Infrastructure["5. Infrastructure"]
        Repo["Repository"]
        Container["DI Container"]
    end

    subgraph Database["6. Database"]
        MySQL[("MySQL")]
    end

    User -->|1| Browser
    Browser -->|2| Route
    Route -->|3| Controller
    Controller -->|4| Command
    Command -->|5| Handler
    Handler -->|6| Validation
    Validation -->|7| VO
    VO -->|8| Entity
    Entity -->|9| Rules
    Handler -->|10| Repo
    Repo -->|11| MySQL
    MySQL -->|12| Repo
    Repo -->|13| Handler
    Handler -->|14| Controller
    Controller -->|15| Template
    Template -->|16| Browser
    Browser -->|17| User

    style External fill:#fce4ec
    style Presentation fill:#e8f5e9
    style Application fill:#fff3e0
    style Domain fill:#e1f5fe
    style Infrastructure fill:#f3e5f5
    style Database fill:#fafafa
```

## Related Documentation

- [[../Tutorials/Getting-Started-with-XOOPS-4.0-Module-Development]]
- [[../Quick-Reference-Card]]
- [Repository & Query Patterns](../Implementation-Guides/Repository-Query-Patterns-Guide.md)
- [Error Handling & Validation](../Implementation-Guides/Error-Handling-Validation-Guide.md)
