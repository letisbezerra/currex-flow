# Buzzvel — Multi-Currency Payment API
## Plano de Ação — Do Zero à Entrega

> Teste técnico Buzzvel 2026 · Laravel 12 · PHP 8.2+ · 5 dias

---

## Estratégia Git

### Branches

```
main ──────────────────────────────────────────────── v1.0.0 (entrega final)
  └── develop ──────────────────────────────────────── integração contínua
        ├── feature/project-setup
        ├── feature/database-domain
        ├── feature/authentication
        ├── feature/exchange-rate-service
        ├── feature/payment-requests
        ├── feature/security
        ├── feature/scheduled-tasks
        ├── feature/testing-quality
        └── docs/final-documentation
```

**Por quê esse modelo?**
Gitflow com `main` + `develop` é o padrão para projetos com ciclo de release definido — exatamente o caso aqui (entrega única em 5 dias). Cada feature branch isola o trabalho, mantém `develop` estável para revisão intermediária, e `main` fica intocado até a entrega final. A Buzzvel valoriza experiência com GIT como requisito obrigatório — um histórico limpo e semântico é parte da avaliação.

### Padrão de Commits — Conventional Commits

```
<tipo>(<escopo>): <descrição curta no imperativo>

Tipos:
  feat     → nova funcionalidade
  fix      → correção de bug
  docs     → apenas documentação
  test     → adição/ajuste de testes
  refactor → refatoração sem mudança de comportamento
  chore    → configuração, dependências, scripts
  ci       → pipelines, Docker, scripts de ambiente
  security → correção de vulnerabilidade
```

**Exemplos reais do projeto:**
```
feat(auth): implement register and login endpoints with Sanctum
feat(payment): add CreatePaymentRequestAction with exchange rate integration
test(auth): add feature tests for register, login and logout flows
docs(adr): record decision to use Action Pattern over Service Pattern
chore(docker): add scheduler container for artisan schedule:work
security(rate-limit): apply throttle 6/min to authentication routes
```

**Por quê?** Conventional Commits é o padrão da indústria — usado em projetos como Vue.js, Angular e Laravel. Torna o histórico legível, facilita code review (item explícito na vaga Buzzvel) e seria obrigatório em qualquer time sério.

### Regras de PR

- Toda feature branch → PR para `develop`
- PR requer descrição: **o quê**, **por quê**, e **como testar**
- Nenhum código vai direto para `develop` ou `main`
- PRs de `docs/` podem ser mergeados sem revisão técnica pesada
- PR final: `develop → main` é a entrega

### ADRs — Architecture Decision Records

Cada decisão arquitetural relevante gera um arquivo em `docs/adr/`:

```
docs/
└── adr/
    ├── 001-sanctum-over-passport.md
    ├── 002-action-pattern-over-service-pattern.md
    ├── 003-interface-for-exchange-rate-service.md
    ├── 004-mysql-with-redis-for-rate-limiting.md
    └── 005-api-versioning-v1.md
```

**Formato de cada ADR:**
```markdown
# ADR-001: Sanctum over Passport

## Status
Accepted

## Context
[Qual problema estávamos resolvendo]

## Decision
[O que foi decidido]

## Consequences
[Prós, contras, impactos]
```

---

## Fase 0 — Infraestrutura e Repositório

> **Objetivo:** Repositório criado, protegido, com estrutura inicial e Docker funcional.
> **Branch:** configuração direta em `main` → `develop` criada a partir daí
> **Duração estimada:** 1–2 horas

### Pré-requisitos locais

Verificar/instalar antes de começar:

```bash
php --version       # precisa ser 8.2+
composer --version  # qualquer versão recente
docker --version    # Docker Desktop ou Docker Engine
git --version       # qualquer versão recente
gh --version        # GitHub CLI (instalar se não tiver)
```

### Passo a passo

**0.1 — Autenticar no GitHub CLI**
```bash
gh auth login
```

**0.2 — Criar repositório no GitHub**
```bash
gh repo create buzzvel-payment-api \
  --public \
  --description "Multi-currency payment request API — Laravel 12 / PHP 8.2" \
  --clone

cd buzzvel-payment-api
```

> **Por quê público?** A Buzzvel pede para "compartilhar o repositório". Público elimina etapas de permissão.

**0.3 — Configurar `.gitignore` e commit inicial**
```bash
# O Laravel já tem .gitignore adequado, mas vamos confirmar depois
echo "# Buzzvel Multi-Currency Payment API" > README.md
git add README.md
git commit -m "chore: initial repository setup"
git push origin main
```

**0.4 — Proteger `main` e criar `develop`**
```bash
# Criar branch develop
git checkout -b develop
git push origin develop

# Proteger main via GitHub CLI (impede push direto)
gh api repos/:owner/buzzvel-payment-api/branches/main/protection \
  --method PUT \
  --field required_pull_request_reviews[required_approving_review_count]=0 \
  --field enforce_admins=false \
  --field restrictions=null \
  --field required_status_checks=null
```

> **Por quê proteger main?** Simula ambiente profissional real. A Buzzvel tem experiência em agile/scrum — nenhum time sério faz push direto em main.

**0.5 — Definir branch padrão como `develop` no GitHub**
```bash
gh api repos/:owner/buzzvel-payment-api \
  --method PATCH \
  --field default_branch=develop
```

**Commit desta fase:**
```
chore: configure git repository with main/develop branch strategy
```

**Documentação gerada nesta fase:**
- `README.md` com título e descrição básica (será expandido na Fase 9)

---

## Fase 1 — Bootstrap do Projeto Laravel

> **Objetivo:** Projeto Laravel 12 criado, configurado, com Docker funcional e ferramentas de qualidade instaladas.
> **Branch:** `feature/project-setup`
> **PR:** `feature/project-setup` → `develop`
> **Duração estimada:** 2–3 horas

### Passo a passo

**1.1 — Criar a branch**
```bash
git checkout develop
git checkout -b feature/project-setup
```

**1.2 — Criar projeto Laravel 12**
```bash
composer create-project laravel/laravel . "^12.0"
```

**1.3 — Instalar dependências de produção**
```bash
# Autenticação
composer require laravel/sanctum

# Verificação: confirmar que está no composer.json
```

> **Por quê Sanctum e não Passport?** Sanctum é a escolha oficial do Laravel para APIs SPA/mobile com token simples. Passport implementa OAuth2 completo — necessário apenas quando há autorização de terceiros (ex: "Login com Google"). Para uma API interna, Passport é overkill e adiciona complexidade desnecessária.

**1.4 — Instalar ferramentas de qualidade**
```bash
# Análise estática (detecta erros sem rodar o código)
composer require --dev larastan/larastan

# Formatação automática PSR-12
# Laravel Pint já vem com Laravel 12, mas confirmar:
composer require --dev laravel/pint
```

> **Por quê Larastan?** PHPStan adaptado para Laravel. Pega erros de tipo, chamadas a métodos inexistentes, null pointers — antes de qualquer teste rodar. Times profissionais usam nível 6+ como gate de CI.

**1.5 — Configurar Pint (PSR-12)**

Criar `pint.json` na raiz:
```json
{
    "preset": "laravel",
    "rules": {
        "ordered_imports": {"sort_algorithm": "alpha"},
        "no_unused_imports": true,
        "declare_strict_types": true
    }
}
```

> **Por quê `declare_strict_types`?** PHP com tipos estritos pega erros de tipo em tempo de execução ao invés de silenciosamente converter valores. Padrão em projetos PHP modernos.

**1.6 — Configurar PHPStan**

Criar `phpstan.neon` na raiz:
```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
    level: 6
    ignoreErrors:
        - '#Unsafe usage of new static#'
```

**1.7 — Configurar `.env.example`**

Garantir que `.env.example` tem todas as variáveis necessárias **sem valores reais**:
```env
APP_NAME="Payment API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=payment_api
DB_USERNAME=payment_user
DB_PASSWORD=

REDIS_HOST=redis
REDIS_PORT=6379

EXCHANGE_RATE_API_KEY=
EXCHANGE_RATE_BASE_URL=https://api.exchangerate-api.com/v4/latest
```

> **Por quê `DB_HOST=db`?** Porque no Docker Compose o container do MySQL se chama `db` — é assim que os containers se comunicam pela rede interna do Docker.

**1.8 — Criar estrutura Docker**

```bash
mkdir -p docker/nginx docker/php
```

`docker/php/Dockerfile`:
```dockerfile
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    oniguruma-dev

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

USER www

EXPOSE 9000
```

> **Por quê Alpine?** Imagem mínima (~5MB base), segura por default (menos pacotes = menos superfície de ataque), padrão em ambientes profissionais.
> **Por quê usuário não-root?** OWASP recomenda containers sem root. Se houver exploração de vulnerabilidade, o atacante não tem privilégios de sistema.

`docker/nginx/default.conf`:
```nginx
server {
    listen 80;
    index index.php;
    root /var/www/html/public;
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

`docker-compose.yml`:
```yaml
services:
  app:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: payment_api_app
    restart: unless-stopped
    volumes:
      - .:/var/www/html
      - /var/www/html/vendor
    depends_on:
      db:
        condition: service_healthy
      redis:
        condition: service_started
    networks:
      - payment_network

  nginx:
    image: nginx:alpine
    container_name: payment_api_nginx
    restart: unless-stopped
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - app
    networks:
      - payment_network

  db:
    image: mysql:8.0
    container_name: payment_api_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD:-rootpassword}
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10
    networks:
      - payment_network

  redis:
    image: redis:7-alpine
    container_name: payment_api_redis
    restart: unless-stopped
    networks:
      - payment_network

  scheduler:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: payment_api_scheduler
    restart: unless-stopped
    command: php artisan schedule:work
    volumes:
      - .:/var/www/html
    depends_on:
      - app
      - db
    networks:
      - payment_network

networks:
  payment_network:
    driver: bridge

volumes:
  db_data:
```

> **Por quê container `scheduler` separado?** Sem ele, seria necessário um cron no servidor host — acoplando a aplicação ao ambiente. Com o container, o agendador roda dentro do ecossistema Docker, portável e reproduzível em qualquer máquina. Isso demonstra maturidade com Docker (fator decisivo na vaga).
> **Por quê `healthcheck` no MySQL?** Garante que o container `app` só inicia após o banco estar efetivamente pronto para aceitar conexões — não apenas "rodando".

**1.9 — Adicionar scripts úteis ao `composer.json`**
```json
"scripts": {
    "analyse": "vendor/bin/phpstan analyse",
    "format": "vendor/bin/pint",
    "test": "php artisan test",
    "test:coverage": "php artisan test --coverage"
}
```

**1.10 — Criar ADR-001 (Sanctum)**

`docs/adr/001-sanctum-over-passport.md` — justificativa da escolha de auth.

**1.11 — Commits e PR**
```bash
git add .
git commit -m "ci(docker): add PHP 8.2-FPM, nginx, mysql, redis, scheduler containers"
git commit -m "chore(deps): install sanctum, larastan, pint"
git commit -m "chore(config): configure pint PSR-12 and phpstan level 6"
git commit -m "docs(adr): record decision 001 — sanctum over passport"

git push origin feature/project-setup
gh pr create \
  --base develop \
  --title "feat: project bootstrap — Laravel 12, Docker, quality tools" \
  --body "## O que foi feito
- Laravel 12 instalado com Sanctum
- Docker Compose com 5 containers (app, nginx, db, redis, scheduler)
- Laravel Pint configurado (PSR-12)
- PHPStan/Larastan nível 6
- .env.example sem secrets

## Como testar
\`\`\`bash
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
\`\`\`
Acesse http://localhost:8000 — deve retornar a página do Laravel."
```

---

## Fase 2 — Database e Domain

> **Objetivo:** Schema do banco criado, Models com tipos corretos, Enums, DTOs e Seeders.
> **Branch:** `feature/database-domain`
> **PR:** `feature/database-domain` → `develop`
> **Duração estimada:** 2–3 horas

### Passo a passo

**2.1 — Criar a branch**
```bash
git checkout develop && git pull origin develop
git checkout -b feature/database-domain
```

**2.2 — Criar Enums PHP 8.1+**

`app/Enums/PaymentStatus.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending  = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired  = 'expired';
}
```

`app/Enums/UserRole.php`:
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Finance  = 'finance';
}
```

> **Por quê Enums nativos?** PHP 8.1 introduziu enums nativos. Usar strings soltas (`'pending'`, `'approved'`) é frágil — um typo não gera erro. Enums são type-safe, completam com IDE e o PHP rejeita valores inválidos. Padrão moderno.

**2.3 — Migration: adicionar campos à tabela users**
```bash
docker compose exec app php artisan make:migration add_profile_fields_to_users_table --table=users
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('role')->default(UserRole::Employee->value)->after('email');
        $table->string('country')->after('role');
        $table->string('currency_code', 3)->after('country');
    });
}
```

**2.4 — Migration: tabela payment_requests**
```bash
docker compose exec app php artisan make:migration create_payment_requests_table
```

```php
public function up(): void
{
    Schema::create('payment_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->decimal('amount', 15, 2);
        $table->string('currency_code', 3);
        $table->string('description');
        $table->string('status')->default(PaymentStatus::Pending->value);
        $table->decimal('exchange_rate', 15, 6);
        $table->string('exchange_rate_source');
        $table->timestamp('exchange_rate_fetched_at');
        $table->decimal('amount_in_eur', 15, 2);
        $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('reviewed_at')->nullable();
        $table->timestamp('expires_at');
        $table->timestamps();
    });
}
```

> **Por quê `decimal` e não `float`?** Float tem imprecisão binária — `0.1 + 0.2 ≠ 0.3` em ponto flutuante. Para valores monetários, `decimal` é obrigatório. É um erro clássico que avaliadores experientes checam.

**2.5 — DTO com PHP 8.2 readonly**

`app/DTOs/ExchangeRateDTO.php`:
```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

readonly class ExchangeRateDTO
{
    public function __construct(
        public float  $rate,
        public string $source,
        public Carbon $fetchedAt,
    ) {}
}
```

> **Por quê DTO readonly?** Garante que a taxa de câmbio não seja modificada após ser buscada — imutabilidade por design, não por convenção. `readonly` é feature do PHP 8.2. Demonstra conhecimento de PHP moderno.

**2.6 — Model User**

`app/Models/User.php` — adicionar:
```php
protected $fillable = [..., 'role', 'country', 'currency_code'];

protected $casts = [
    'role' => UserRole::class,
];

public function isFinance(): bool
{
    return $this->role === UserRole::Finance;
}

public function paymentRequests(): HasMany
{
    return $this->hasMany(PaymentRequest::class);
}
```

**2.7 — Model PaymentRequest**

`app/Models/PaymentRequest.php`:
```php
protected $fillable = [
    'user_id', 'amount', 'currency_code', 'description',
    'status', 'exchange_rate', 'exchange_rate_source',
    'exchange_rate_fetched_at', 'amount_in_eur',
    'reviewed_by', 'reviewed_at', 'expires_at',
];

protected $casts = [
    'status'                  => PaymentStatus::class,
    'exchange_rate_fetched_at'=> 'datetime',
    'reviewed_at'             => 'datetime',
    'expires_at'              => 'datetime',
    'amount'                  => 'decimal:2',
    'amount_in_eur'           => 'decimal:2',
    'exchange_rate'           => 'decimal:6',
];

public function isPending(): bool
{
    return $this->status === PaymentStatus::Pending;
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function reviewer(): BelongsTo
{
    return $this->belongsTo(User::class, 'reviewed_by');
}
```

**2.8 — Seeders**

`database/seeders/UserSeeder.php`:
```php
// 5 employees em países/moedas diferentes + 1 finance
$employees = [
    ['name' => 'Ana Lima',      'country' => 'Brazil',         'currency_code' => 'BRL'],
    ['name' => 'James Smith',   'country' => 'United Kingdom',  'currency_code' => 'GBP'],
    ['name' => 'Yuki Tanaka',   'country' => 'Japan',          'currency_code' => 'JPY'],
    ['name' => 'Priya Patel',   'country' => 'India',          'currency_code' => 'INR'],
    ['name' => 'Lucas Dupont',  'country' => 'Canada',         'currency_code' => 'CAD'],
];

// finance@buzzvel.com / password
User::create([..., 'role' => UserRole::Finance, 'country' => 'Portugal', 'currency_code' => 'EUR']);
```

**2.9 — Commits e PR**
```bash
git add .
git commit -m "feat(domain): add PaymentStatus and UserRole enums"
git commit -m "feat(migration): add profile fields to users table"
git commit -m "feat(migration): create payment_requests table"
git commit -m "feat(dto): add ExchangeRateDTO as PHP 8.2 readonly class"
git commit -m "feat(model): configure User and PaymentRequest with casts and relations"
git commit -m "feat(seeder): add 5 employees across countries and 1 finance user"

git push origin feature/database-domain
gh pr create --base develop --title "feat: database schema, models, enums, DTOs and seeders" ...
```

---

## Fase 3 — Autenticação

> **Objetivo:** Register, login, logout com Sanctum funcionando, testados e documentados.
> **Branch:** `feature/authentication`
> **PR:** `feature/authentication` → `develop`
> **Duração estimada:** 2–3 horas

### Passo a passo

**3.1 — Form Requests de auth**

`app/Http/Requests/Auth/RegisterRequest.php`:
```php
public function rules(): array
{
    return [
        'name'          => 'required|string|max:255',
        'email'         => 'required|email|unique:users,email',
        'password'      => 'required|string|min:8|confirmed',
        'country'       => 'required|string|max:100',
        'currency_code' => 'required|string|size:3',
    ];
}
```

`app/Http/Requests/Auth/LoginRequest.php`:
```php
public function rules(): array
{
    return [
        'email'    => 'required|email',
        'password' => 'required|string',
    ];
}
```

**3.2 — AuthController**

`app/Http/Controllers/Api/V1/AuthController.php`:
```php
public function register(RegisterRequest $request): JsonResponse
{
    $user = User::create([
        ...$request->safe()->except('password_confirmation'),
        'password' => Hash::make($request->password),
        'role'     => UserRole::Employee,
    ]);

    return response()->json([
        'token' => $user->createToken('api-token')->plainTextToken,
        'user'  => new UserResource($user),
    ], 201);
}

public function login(LoginRequest $request): JsonResponse
{
    if (! Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $user->tokens()->delete(); // revoga tokens antigos

    return response()->json([
        'token' => $user->createToken('api-token')->plainTextToken,
        'user'  => new UserResource($user),
    ]);
}

public function logout(Request $request): JsonResponse
{
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Logged out successfully']);
}
```

> **Por quê revogar tokens antigos no login?** Evita acúmulo de tokens órfãos no banco — prática de segurança (OWASP: Broken Authentication). Um usuário deve ter apenas um token ativo por vez em fluxos simples.

**3.3 — UserResource**

`app/Http/Resources/Api/V1/UserResource.php`:
```php
public function toArray($request): array
{
    return [
        'id'            => $this->id,
        'name'          => $this->name,
        'email'         => $this->email,
        'role'          => $this->role->value,
        'country'       => $this->country,
        'currency_code' => $this->currency_code,
    ];
}
```

> **Por quê Resource e não retornar o Model direto?** O Model contém `password`, `remember_token` e campos internos. Retornar o Model expõe esses dados — erro clássico de OWASP Broken Object Property Authorization.

**3.4 — Rotas**

`routes/api.php`:
```php
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login'])
             ->middleware('throttle:auth');  // rate limit especial para login
        Route::post('logout', [AuthController::class, 'logout'])
             ->middleware('auth:sanctum');
    });
});
```

**3.5 — Testes**

`tests/Feature/Auth/AuthTest.php` — cobrir:
- Register com dados válidos → 201 + token
- Register com email duplicado → 422
- Register com senha fraca → 422
- Login com credenciais válidas → 200 + token
- Login com senha errada → 401
- Logout com token válido → 200
- Logout sem token → 401

**3.6 — Commits e PR**
```bash
git commit -m "feat(auth): implement register endpoint with Sanctum token"
git commit -m "feat(auth): implement login with token rotation"
git commit -m "feat(auth): implement logout with token revocation"
git commit -m "feat(resource): add UserResource to control API output"
git commit -m "test(auth): add feature tests for auth endpoints"
```

---

## Fase 4 — Integração Exchange Rate

> **Objetivo:** Interface e serviço de câmbio implementados, testáveis via mock, binding no container.
> **Branch:** `feature/exchange-rate-service`
> **PR:** `feature/exchange-rate-service` → `develop`
> **Duração estimada:** 2 horas

### Passo a passo

**4.1 — Interface (Dependency Inversion)**

`app/Contracts/ExchangeRateServiceInterface.php`:
```php
interface ExchangeRateServiceInterface
{
    public function getRate(string $currencyCode): ExchangeRateDTO;
}
```

**4.2 — Implementação**

`app/Services/ExchangeRate/ExchangeRateApiService.php`:
```php
class ExchangeRateApiService implements ExchangeRateServiceInterface
{
    public function getRate(string $currencyCode): ExchangeRateDTO
    {
        $response = Http::timeout(10)
            ->retry(2, 500)
            ->get(config('services.exchange_rate.base_url') . '/EUR');

        throw_unless(
            $response->successful(),
            ExchangeRateException::class,
            'Exchange rate service unavailable'
        );

        $rates = $response->json('rates');

        throw_unless(
            isset($rates[$currencyCode]),
            ExchangeRateException::class,
            "Currency code {$currencyCode} not supported"
        );

        return new ExchangeRateDTO(
            rate:      (float) $rates[$currencyCode],
            source:    config('services.exchange_rate.source'),
            fetchedAt: now(),
        );
    }
}
```

> **Por quê `retry(2, 500)`?** APIs externas podem ter instabilidade momentânea. Retry com backoff de 500ms resolve falhas transientes sem necessidade de queue — proporcional à criticidade do endpoint.
> **Por quê `timeout(10)`?** Sem timeout, uma API lenta pode travar o worker do PHP indefinidamente. 10 segundos é generoso mas seguro.

**4.3 — Exception tipada**

`app/Exceptions/ExchangeRateException.php`:
```php
class ExchangeRateException extends RuntimeException {}
```

**4.4 — Bind no container (DIP na prática)**

`app/Providers/AppServiceProvider.php`:
```php
public function register(): void
{
    $this->app->bind(
        ExchangeRateServiceInterface::class,
        ExchangeRateApiService::class
    );
}
```

> **Por quê bind e não singleton?** Taxa de câmbio muda a cada requisição — não deve ser cacheada na memória do processo. Cada criação de PaymentRequest deve buscar a taxa atual.

**4.5 — Configurar em `config/services.php`**
```php
'exchange_rate' => [
    'base_url' => env('EXCHANGE_RATE_BASE_URL', 'https://api.exchangerate-api.com/v4/latest'),
    'source'   => 'exchangerate-api.com',
],
```

**4.6 — Testes unitários com mock HTTP**

`tests/Unit/ExchangeRateServiceTest.php`:
```php
// Http::fake() — sem chamar a API real nos testes
Http::fake([
    '*/EUR' => Http::response(['rates' => ['BRL' => 5.42]], 200),
]);

$dto = $service->getRate('BRL');
expect($dto->rate)->toBe(5.42);
expect($dto->source)->toBe('exchangerate-api.com');
```

> **Por quê mock e não chamar a API real?** Testes devem ser determinísticos e independentes de serviços externos. A API pode estar fora, retornar taxas diferentes, ou ter limite de requests. `Http::fake()` é a solução nativa do Laravel para isso.

**4.7 — ADR**

`docs/adr/003-interface-for-exchange-rate-service.md` — justificar a decisão de usar interface.

**4.8 — Commits e PR**
```bash
git commit -m "feat(contract): add ExchangeRateServiceInterface"
git commit -m "feat(service): implement ExchangeRateApiService with retry and timeout"
git commit -m "feat(dto): bind interface to implementation in AppServiceProvider"
git commit -m "test(unit): add ExchangeRateService tests with Http::fake()"
git commit -m "docs(adr): record decision 003 — interface for exchange rate service"
```

---

## Fase 5 — Payment Requests (Core da Aplicação)

> **Objetivo:** CRUD completo de payment requests com Actions, Policy, Resources e testes.
> **Branch:** `feature/payment-requests`
> **PR:** `feature/payment-requests` → `develop`
> **Duração estimada:** 4–5 horas

### Passo a passo

**5.1 — Form Requests**

`StorePaymentRequest.php`:
```php
public function rules(): array
{
    return [
        'amount'      => 'required|numeric|min:0.01|max:9999999.99',
        'description' => 'required|string|max:255',
    ];
}

// currency_code vem do perfil do usuário logado — não é input do usuário
```

> **Por quê `currency_code` não é input?** O funcionário submete na sua moeda local — que já está no perfil. Aceitar `currency_code` como input permitiria que um funcionário em BRL submetesse em USD, burlando o propósito do sistema. **Menos input = menos superfície de ataque.**

`UpdatePaymentStatusRequest.php`:
```php
public function rules(): array
{
    return [
        'status' => ['required', Rule::in([
            PaymentStatus::Approved->value,
            PaymentStatus::Rejected->value,
        ])],
    ];
}
```

**5.2 — Actions**

`app/Actions/Payment/CreatePaymentRequestAction.php`:
```php
class CreatePaymentRequestAction
{
    public function __construct(
        private readonly ExchangeRateServiceInterface $exchangeRate
    ) {}

    public function __invoke(array $data, User $user): PaymentRequest
    {
        $rate = $this->exchangeRate->getRate($user->currency_code);

        return PaymentRequest::create([
            'user_id'                  => $user->id,
            'amount'                   => $data['amount'],
            'currency_code'            => $user->currency_code,
            'description'              => $data['description'],
            'status'                   => PaymentStatus::Pending,
            'exchange_rate'            => $rate->rate,
            'exchange_rate_source'     => $rate->source,
            'exchange_rate_fetched_at' => $rate->fetchedAt,
            'amount_in_eur'            => round($data['amount'] / $rate->rate, 2),
            'expires_at'               => now()->addHours(48),
        ]);
    }
}
```

`app/Actions/Payment/ApprovePaymentRequestAction.php`:
```php
public function __invoke(PaymentRequest $payment, User $reviewer): PaymentRequest
{
    throw_unless($payment->isPending(), \DomainException::class, 'Only pending requests can be approved');

    $payment->update([
        'status'      => PaymentStatus::Approved,
        'reviewed_by' => $reviewer->id,
        'reviewed_at' => now(),
    ]);

    return $payment->fresh();
}
```

**5.3 — PaymentRequestResource**

```php
public function toArray($request): array
{
    return [
        'id'                       => $this->id,
        'amount'                   => $this->amount,
        'currency_code'            => $this->currency_code,
        'amount_in_eur'            => $this->amount_in_eur,
        'description'              => $this->description,
        'status'                   => $this->status->value,
        'exchange_rate'            => $this->exchange_rate,
        'exchange_rate_source'     => $this->exchange_rate_source,
        'exchange_rate_fetched_at' => $this->exchange_rate_fetched_at,
        'expires_at'               => $this->expires_at,
        'reviewed_by'              => $this->reviewed_by,
        'reviewed_at'              => $this->reviewed_at,
        'created_at'               => $this->created_at,
    ];
}
```

**5.4 — Policy**

`app/Policies/PaymentRequestPolicy.php`:
```php
public function viewAny(User $user): bool
{
    return true; // employee vê os seus (filtrado no controller); finance vê todos
}

public function view(User $user, PaymentRequest $payment): bool
{
    return $user->isFinance() || $user->id === $payment->user_id;
}

public function updateStatus(User $user, PaymentRequest $payment): bool
{
    return $user->isFinance() && $payment->isPending();
}
```

**5.5 — Controller**

`app/Http/Controllers/Api/V1/PaymentRequestController.php`:
```php
public function index(Request $request): AnonymousResourceCollection
{
    $query = $request->user()->isFinance()
        ? PaymentRequest::with('user')
        : $request->user()->paymentRequests();

    $requests = $query
        ->when($request->status, fn($q, $s) => $q->where('status', $s))
        ->latest()
        ->paginate(15);

    return PaymentRequestResource::collection($requests);
}

public function store(StorePaymentRequest $request): PaymentRequestResource
{
    $payment = ($this->createAction)($request->validated(), $request->user());
    return (new PaymentRequestResource($payment))->response()->setStatusCode(201);
}

public function show(PaymentRequest $payment): PaymentRequestResource
{
    $this->authorize('view', $payment);
    return new PaymentRequestResource($payment);
}

public function updateStatus(UpdatePaymentStatusRequest $request, PaymentRequest $payment): PaymentRequestResource
{
    $this->authorize('updateStatus', $payment);

    $action = $request->status === PaymentStatus::Approved->value
        ? $this->approveAction
        : $this->rejectAction;

    return new PaymentRequestResource(($action)($payment, $request->user()));
}
```

**5.6 — Rotas**
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('payment-requests', [PaymentRequestController::class, 'index']);
    Route::post('payment-requests', [PaymentRequestController::class, 'store']);
    Route::get('payment-requests/{paymentRequest}', [PaymentRequestController::class, 'show']);
    Route::patch('payment-requests/{paymentRequest}/status', [PaymentRequestController::class, 'updateStatus']);
});
```

**5.7 — Testes**

Cobrir:
- Employee cria payment request → 201, taxa salva corretamente
- Finance vê todos os pedidos, employee vê só os seus
- Filtro `?status=pending` funciona
- Finance aprova pedido pendente → 200
- Finance rejeita pedido pendente → 200
- Employee tentando aprovar → 403
- Aprovar pedido já aprovado → erro de domínio
- Detalhe de pedido alheio como employee → 403

**5.8 — Commits e PR**
```bash
git commit -m "feat(payment): add StorePaymentRequest and UpdatePaymentStatusRequest form requests"
git commit -m "feat(payment): add CreatePaymentRequestAction with exchange rate integration"
git commit -m "feat(payment): add ApprovePaymentRequestAction and RejectPaymentRequestAction"
git commit -m "feat(payment): add PaymentRequestPolicy for authorization"
git commit -m "feat(payment): add PaymentRequestController with index, store, show, updateStatus"
git commit -m "feat(route): register payment request routes under /api/v1"
git commit -m "test(payment): add feature tests for payment request CRUD and authorization"
```

---

## Fase 6 — Segurança

> **Objetivo:** Rate limiting, CORS, exception handler, headers de segurança e checklist OWASP aplicado.
> **Branch:** `feature/security`
> **PR:** `feature/security` → `develop`
> **Duração estimada:** 1–2 horas

### Passo a passo

**6.1 — Rate Limiting**

`app/Providers/AppServiceProvider.php`:
```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('auth', function (Request $request) {
    return Limit::perMinute(6)->by($request->ip()); // brute force protection
});
```

> **Por quê 6/min no auth?** Limitar tentativas de login é proteção anti-brute-force. 6 tentativas por minuto é conservador mas não afeta uso legítimo. OWASP API2: Broken Authentication.

**6.2 — CORS**

`config/cors.php` — limitar origins em produção:
```php
'allowed_origins' => env('APP_ENV') === 'production'
    ? explode(',', env('CORS_ALLOWED_ORIGINS', ''))
    : ['*'],
```

**6.3 — Exception Handler global**

`bootstrap/app.php` — tratar exceções de forma consistente:
```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (ExchangeRateException $e) {
        return response()->json(['message' => $e->getMessage()], 503);
    });
    $exceptions->render(function (AuthorizationException $e) {
        return response()->json(['message' => 'Forbidden'], 403);
    });
    $exceptions->render(function (ModelNotFoundException $e) {
        return response()->json(['message' => 'Resource not found'], 404);
    });
    $exceptions->render(function (ValidationException $e) {
        return response()->json([
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);
    });
})
```

> **Por quê handler global?** Garante que **todas** as exceções retornam JSON consistente — não HTML do Laravel (que vaza stack trace em produção). OWASP A05: Security Misconfiguration.

**6.4 — Checklist OWASP no código**
- Confirmar que nenhum endpoint retorna dados além do Resource definido
- Confirmar que `APP_DEBUG=false` está no `.env.example` comentado para produção
- Confirmar que `storage/` não está acessível via nginx
- Confirmar que `.env` está no `.gitignore`

**6.5 — Commits e PR**
```bash
git commit -m "security(rate-limit): configure 60/min general and 6/min auth rate limits"
git commit -m "security(cors): restrict allowed origins in production"
git commit -m "security(exception): add global JSON exception handler"
git commit -m "security(owasp): apply OWASP API security checklist"
```

---

## Fase 7 — Tarefas Agendadas

> **Objetivo:** Command de expiração implementado, agendado e testado.
> **Branch:** `feature/scheduled-tasks`
> **PR:** `feature/scheduled-tasks` → `develop`
> **Duração estimada:** 1 hora

### Passo a passo

**7.1 — Criar o command**
```bash
docker compose exec app php artisan make:command ExpirePaymentRequests
```

```php
protected $signature   = 'payments:expire-pending';
protected $description = 'Expire payment requests pending for more than 48 hours';

public function handle(): int
{
    $expired = PaymentRequest::where('status', PaymentStatus::Pending->value)
        ->where('expires_at', '<', now())
        ->update(['status' => PaymentStatus::Expired->value]);

    $this->info("Expired {$expired} payment request(s).");
    return Command::SUCCESS;
}
```

**7.2 — Registrar no agendador**

`routes/console.php`:
```php
Schedule::command('payments:expire-pending')->hourly();
```

> **Por quê hourly e não every minute?** Pedidos expiram após 48 horas — uma granularidade horária é mais que suficiente e reduz carga. Rodar a cada minuto seria desperdício de recursos sem benefício real.

**7.3 — Testes**
```php
// Criar pedido com expires_at no passado
// Rodar o command
// Assertar que status mudou para 'expired'
// Assertar que pedidos não-expirados não foram tocados
```

**7.4 — Commits e PR**
```bash
git commit -m "feat(command): add ExpirePaymentRequests artisan command"
git commit -m "feat(schedule): register expire command to run hourly"
git commit -m "test(command): add tests for payment request expiration logic"
```

---

## Fase 8 — Qualidade e Cobertura

> **Objetivo:** PHPStan passando, Pint aplicado, todos os testes verdes, sem warnings.
> **Branch:** `feature/testing-quality`
> **PR:** `feature/testing-quality` → `develop`
> **Duração estimada:** 2–3 horas

### Passo a passo

**8.1 — Rodar PHPStan e corrigir**
```bash
docker compose exec app composer analyse
# Corrigir todos os erros reportados no nível 6
```

**8.2 — Rodar Pint e formatar**
```bash
docker compose exec app composer format
# Nenhum arquivo deve ter diff após isso
```

**8.3 — Rodar todos os testes**
```bash
docker compose exec app composer test
# Todos devem passar em verde
```

**8.4 — Revisar cobertura dos testes**

Confirmar que estão cobertos:
- [ ] Todos os endpoints de auth (registro, login, logout)
- [ ] Criação de payment request (com câmbio mockado)
- [ ] Listagem com e sem filtro de status
- [ ] Detalhe com ownership check
- [ ] Aprovação/rejeição (finance)
- [ ] Tentativa de aprovação por employee (403)
- [ ] ExchangeRateService (unit test com Http::fake)
- [ ] Command de expiração

**8.5 — Commits e PR**
```bash
git commit -m "refactor: apply pint formatting across all PHP files"
git commit -m "test(quality): fix phpstan level 6 warnings"
git commit -m "test(coverage): ensure all critical paths are covered"
```

---

## Fase 9 — Documentação Final

> **Objetivo:** README completo, documentação de endpoints, ADRs finalizados e instruções de setup.
> **Branch:** `docs/final-documentation`
> **PR:** `docs/final-documentation` → `develop`
> **Duração estimada:** 2–3 horas

### README.md deve conter

1. **Visão geral** do projeto
2. **Stack** com versões exatas
3. **Setup local** — passo a passo com Docker
4. **Variáveis de ambiente** — descrição de cada uma
5. **Endpoints da API** — todos com método, URL, body, resposta de exemplo
6. **Credenciais dos seeders** — emails e senhas para testar
7. **Comandos úteis** — rodar testes, PHPStan, Pint, artisan
8. **Decisões arquiteturais** — link para `docs/adr/`

### Documentação de Endpoints (dentro do README)

Para cada endpoint:
```
### POST /api/v1/payment-requests

**Auth:** Bearer token requerido

**Body:**
| Campo       | Tipo   | Obrigatório | Descrição           |
|-------------|--------|-------------|---------------------|
| amount      | number | sim         | Valor na moeda local|
| description | string | sim         | Descrição do pedido |

**Resposta 201:**
{
  "data": {
    "id": 1,
    "amount": "500.00",
    "currency_code": "BRL",
    "amount_in_eur": "92.25",
    "exchange_rate": "5.420000",
    ...
  }
}

**Erros:**
- 401 — não autenticado
- 422 — validação falhou
- 503 — serviço de câmbio indisponível
```

### ADRs a finalizar

- `001-sanctum-over-passport.md`
- `002-action-pattern-over-service-pattern.md`
- `003-interface-for-exchange-rate-service.md`
- `004-decimal-over-float-for-monetary-values.md`
- `005-api-versioning-v1.md`

**Commits e PR:**
```bash
git commit -m "docs(readme): add complete setup and API documentation"
git commit -m "docs(adr): finalize all architecture decision records"
git commit -m "docs(endpoints): add request/response examples for all endpoints"
```

---

## Fase 10 — Entrega Final

> **Objetivo:** `develop` → `main`, tag de versão, vídeo/URL de demonstração.
> **Branch:** PR `develop` → `main`
> **Duração estimada:** 1 hora

### Passo a passo

**10.1 — PR final: develop → main**
```bash
gh pr create \
  --base main \
  --head develop \
  --title "feat: multi-currency payment API v1.0.0" \
  --body "Release completo do teste técnico Buzzvel 2026..."
```

**10.2 — Tag de versão semântica**
```bash
git tag -a v1.0.0 -m "Multi-currency Payment API — initial release"
git push origin v1.0.0
```

> **Por quê tag?** Semantic Versioning é padrão da indústria. Uma tag `v1.0.0` no GitHub cria um Release visual, mostrando profissionalismo no versionamento.

**10.3 — Testar do zero (smoke test final)**
```bash
# Simular o avaliador clonando o projeto
git clone <repo-url> teste-final
cd teste-final
cp .env.example .env
# Editar .env com credenciais locais
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
# Todos os testes devem passar
```

**10.4 — Gravar vídeo de demonstração**

Mostrar:
1. `docker compose up` rodando
2. Register + login com Postman/curl
3. Criar payment request → mostrar taxa de câmbio salva
4. Login como finance → aprovar pedido
5. Tentar aprovar como employee → ver 403
6. Rodar `php artisan payments:expire-pending` → ver pedidos expirarem
7. `php artisan test` → todos verdes

---

## Resumo do Plano

| Fase | O que entrega | Branch | PR para |
|------|--------------|--------|---------|
| 0 | GitHub + proteção de branches | — (main direto) | — |
| 1 | Laravel + Docker + ferramentas | `feature/project-setup` | develop |
| 2 | Migrations + Models + Seeders | `feature/database-domain` | develop |
| 3 | Auth (register/login/logout) | `feature/authentication` | develop |
| 4 | Exchange Rate Service | `feature/exchange-rate-service` | develop |
| 5 | Payment Requests (CRUD + Policy) | `feature/payment-requests` | develop |
| 6 | Segurança + Rate Limit + OWASP | `feature/security` | develop |
| 7 | Command de expiração | `feature/scheduled-tasks` | develop |
| 8 | PHPStan + Pint + Cobertura | `feature/testing-quality` | develop |
| 9 | README + ADRs + Docs | `docs/final-documentation` | develop |
| 10 | Tag v1.0.0 + vídeo | PR develop → main | main |

---

## Cronograma Sugerido (5 dias)

| Dia | Fases |
|-----|-------|
| Dia 1 (manhã) | Fase 0 + Fase 1 |
| Dia 1 (tarde) | Fase 2 + Fase 3 |
| Dia 2 | Fase 4 + Fase 5 |
| Dia 3 | Fase 5 (continuação) + Fase 6 |
| Dia 4 | Fase 7 + Fase 8 |
| Dia 5 | Fase 9 + Fase 10 |

---

*Referências: [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) · [Gitflow Workflow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow) · [ADR Guide](https://adr.github.io/) · [OWASP Laravel Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Laravel_Cheat_Sheet.html) · [Laravel Testing Best Practices](https://benjamincrozat.com/laravel-testing-best-practices)*
