# Architektura aplikace

Aplikace Seznam úkolů v **čistém PHP 8.3 (OOP)** — bez frameworku a bez Composeru.
Cílem je ukázat čistou **vrstvenou architekturu** (Controller → Service →
Repository), ruční PSR-4 autoloading, SQLite přes PDO a testovatelnost přes
dependency inversion — backend bez jediné externí závislosti. Jediná externí
závislost je na **frontendu**: šablona linkuje **Bootstrap 5** z CDN (samotné
CSS, žádný build ani npm).

---

## Adresářová struktura

```
t420-02-slash-prikazy-cld/
├── public/                  # Webroot (DocumentRoot Apache)
│   ├── index.php            # Front controller — jediný vstupní bod, registrace rout
│   └── .htaccess            # Rewrite všech requestů na index.php
├── src/                     # Aplikační kód, namespace App\
│   ├── Controller/
│   │   └── TaskController.php
│   ├── Service/
│   │   └── TaskService.php
│   ├── Repository/
│   │   ├── TaskRepositoryInterface.php
│   │   └── TaskRepository.php
│   ├── Model/
│   │   └── Task.php
│   └── Core/
│       ├── Router.php       # Mapování (metoda + cesta) → handler
│       └── Database.php     # PDO/SQLite singleton + migrace + seed
├── templates/
│   └── tasks.php            # HTML šablona seznamu úkolů (Bootstrap 5 z CDN, escapovaný výstup)
├── tests/                   # Vlastní test runner, namespace Tests\
│   ├── run.php              # Spouštěč: php tests/run.php
│   ├── TaskServiceTest.php  # 6 jednotkových testů business logiky
│   └── Fake/
│       └── InMemoryTaskRepository.php
├── data/                    # SQLite databáze (tasks.sqlite vzniká za běhu)
├── autoload.php             # Ruční PSR-4 autoloader (App\ → src/)
├── Dockerfile               # PHP 8.3 + Apache, pdo_sqlite, mod_rewrite
├── docker-compose.yml       # Služba web na portu 8080
├── docker-entrypoint.sh     # Nastaví práva na data/ při startu
└── .claude/                 # Konfigurace Claude Code (skills, settings)
```

---

## Vrstvená architektura

Tok requestu jde **striktně jedním směrem**; každá vrstva zná jen tu těsně pod
sebou a komunikuje s ní přes její veřejné API (u repository přes rozhraní).

```
HTTP request
   │
   ▼
public/index.php ──► Router ──► TaskController ──► TaskService ──► TaskRepositoryInterface
                    (Core)      (Controller)       (Service)            │
                                     │                          ┌───────┴───────┐
                                     ▼                          ▼               ▼
                              templates/tasks.php       TaskRepository   InMemoryTaskRepository
                                   (View)               (Core\Database)     (Tests\Fake)
                                                          PDO / SQLite      paměť (testy)
```

| Vrstva | Třída | Zodpovědnost | Co smí volat |
|---|---|---|---|
| **Front controller** | `public/index.php` | Bootstrap (autoload), sestavení Routeru, registrace rout, dispatch | Router, Controller |
| **Routing** | `Core\Router` | Mapuje HTTP metodu + cestu na callable (podpora `{id}`), jinak 404 | — |
| **Controller** | `Controller\TaskController` | Orchestrace requestu: čtení `$_POST`, volání Service, render/redirect | Service |
| **Service** | `Service\TaskService` | Business logika: validace názvu, výpočet `progress()` | Repository (přes rozhraní) |
| **Repository** | `Repository\TaskRepository` | Jediné místo s SQL; prepared statements; mapování řádků na `Task` | `Core\Database` (PDO) |
| **Model** | `Model\Task` | Doménová entita + tovární `fromRow()` | — |
| **View** | `templates/tasks.php` | Vykreslení HTML (Bootstrap 5 z CDN), escapování výstupu | — |

### Klíčové principy

1. **Separation of concerns** — Controller nikdy nesahá na Repository ani na
   PDO přímo, vždy přes Service. Business logika nepatří do Controlleru.
   Repository je **jediné** místo s SQL.
2. **Dependency Inversion** — `TaskService` závisí na
   `Repository\TaskRepositoryInterface`, ne na konkrétní implementaci. Díky tomu
   lze v testech podstrčit `InMemoryTaskRepository` místo databáze.
3. **`declare(strict_types=1)`** ve všech PHP souborech.
4. **`final` třídy** — žádná aplikační třída není určená k dědění.
5. **`readonly` vlastnosti** tam, kde se po konstrukci nemění (`Task::$id`,
   `Task::$createdAt`, injektované závislosti).
6. **Bezpečnost** — repository používá výhradně **prepared statements**
   (`:param`), nikdy konkatenaci; šablona escapuje veškerý výstup přes
   `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` (ochrana proti XSS).

---

## Životní cyklus requestu

Na příkladu „přidání úkolu" (POST `/tasks`):

1. **Apache + `.htaccess`** přesměrují každý request (mimo existující soubory)
   na `public/index.php`.
2. **`index.php`** načte `autoload.php`, vytvoří `TaskController` a `Router`,
   zaregistruje routy a zavolá `dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])`.
3. **`Router`** najde shodu `POST /tasks` a zavolá handler → `TaskController::store()`.
4. **`Controller::store()`** přečte `$_POST['title']`, předá ho `TaskService::add()`.
   Při neplatném vstupu (`InvalidArgumentException`) chybu tiše spolkne a jen
   přesměruje zpět (v reálu by zde byla flash message).
5. **`Service::add()`** název ořeže (`trim`), prázdný odmítne výjimkou, jinak
   volá `Repository::create()`.
6. **`Repository::create()`** vloží řádek prepared statementem a vrátí znovu
   načtený `Task`.
7. **Controller** odpoví `302 → /` (**PRG** — Post/Redirect/Get, brání
   reodeslání formuláře při refreshi).
8. Následný **GET `/`** projde stejnými vrstvami až k `index()`, který vyrenderuje
   `templates/tasks.php` se seznamem úkolů a procentem hotových.

---

## Komponenty detailně

### `Core\Router`
Drží pole rout `{method, pattern, handler}`. V `dispatch()` z URI vytáhne cestu
(`parse_url`), projde routy a první shodu metody i cesty zavolá. Parametr `{id}`
v patternu se přepíše na `(?P<id>\d+)` a handleru se předá jako `int` (jinak
`null`). Bez shody vrací `404`.

### `Core\Database`
Líný **singleton** (statická `$instance`). Při prvním volání `connection()`:
1. otevře PDO k `data/tasks.sqlite` (`ERRMODE_EXCEPTION`, `FETCH_ASSOC`),
2. spustí `migrate()` — `CREATE TABLE IF NOT EXISTS tasks (...)`,
3. naseeduje 3 ukázkové úkoly, je-li tabulka prázdná.

Žádné samostatné migrace ani seed skripty — schéma se vytvoří samo.

**Schéma `tasks`:** `id` (PK, AUTOINCREMENT), `title` (TEXT NOT NULL), `done`
(INTEGER 0/1, default 0), `created_at` (TEXT, ISO 8601 přes `date('c')`).

### `Model\Task`
Doménová entita s konstruktorovou promocí. `id` a `createdAt` jsou `readonly`,
`title` a `done` měnitelné. Tovární metoda `fromRow()` mapuje asociativní řádek
z DB na typovaný objekt (přetypuje `done` na `bool`, `id` na `int`).

### `Repository\TaskRepository`
Implementuje `TaskRepositoryInterface`. V konstruktoru si vezme PDO z
`Database::connection()`.

| Metoda | SQL | Pozn. |
|---|---|---|
| `all(): list<Task>` | `SELECT * … ORDER BY id DESC` | mapuje řádky přes `Task::fromRow()` |
| `find(int): ?Task` | `SELECT * … WHERE id = :id` | `null`, pokud nenalezeno |
| `create(string): Task` | `INSERT … VALUES (:title, 0, :created_at)` | vrací znovu načtený `Task`; při selhání `?? throw RuntimeException` |
| `toggle(int): void` | `UPDATE … SET done = 1 - done WHERE id = :id` | přepnutí bez čtení |
| `delete(int): void` | `DELETE … WHERE id = :id` | |

### `Service\TaskService`
Konstruktor přijímá `?TaskRepositoryInterface`; je-li `null`, vytvoří si
produkční `TaskRepository` (default pro web, injektovatelný fake pro testy).

- `list()` — proxy na `repository->all()`.
- `add(string)` — `trim`, prázdný název odmítne `InvalidArgumentException`,
  jinak `repository->create()`.
- `toggle(int)` / `remove(int)` — delegace na repository.
- `progress(): int` — spočítá procento hotových úkolů
  (`round($done / count * 100)`); pro prázdný seznam vrací `0` (ochrana proti
  dělení nulou).

### `Controller\TaskController`
Konstruktor s default `new TaskService()` (lze přepsat pro testy). Akce
`index/store/toggle/destroy` orchestrují request; zápisové končí
`redirect('/')`. Privátní `render()` extrahuje data a `require`-ne šablonu.

---

## Routing

| Metoda | Cesta | Handler | Akce |
|---|---|---|---|
| GET | `/` | `TaskController::index` | Výpis úkolů + procento hotových |
| POST | `/tasks` | `TaskController::store` | Přidání úkolu |
| POST | `/tasks/{id}/toggle` | `TaskController::toggle` | Přepnutí hotovo/nehotovo |
| POST | `/tasks/{id}/delete` | `TaskController::destroy` | Smazání úkolu |

Po zápisových akcích Controller přesměruje zpět na `/` (**PRG** vzor).

---

## Autoloading

Bez Composeru — dva ruční PSR-4 autoloadery (`spl_autoload_register`):

- `autoload.php`: prefix `App\` → adresář `src/`
  (`App\Controller\TaskController` → `src/Controller/TaskController.php`).
- `tests/run.php`: navíc prefix `Tests\` → adresář `tests/` (test helpery a fake).

Nové třídy musí dodržet shodu **namespace ↔ cesta**, jinak je autoloader nenajde.

---

## Testování

Vlastní minimalistický runner bez frameworku — `tests/run.php`:

- Spuštění: `php tests/run.php` (návratový kód **0 = OK, 1 = selhání**).
- Každý soubor `tests/*Test.php` vrací `array<string, callable>` pojmenovaných
  testů; runner je projde přes `glob('tests/*Test.php')`.
- Assertion helpery: `assert_same()`, `assert_true()`, `assert_throws()`.
- **`TaskServiceTest.php`** testuje business logiku **izolovaně** — místo
  databáze používá `Tests\Fake\InMemoryTaskRepository` (paměťová implementace
  rozhraní). Pokrývá 6 případů: vytvoření úkolu, ořez mezer, odmítnutí prázdného
  názvu, přepnutí stavu, výpočet `progress()` a `progress()` nad prázdným seznamem.

> Při přidávání metody na repository ji přidej **i do `TaskRepositoryInterface`
> a do `InMemoryTaskRepository`**, jinak testy spadnou na nekompletní
> implementaci rozhraní.

---

## Běhové prostředí (Docker)

- **`Dockerfile`** — `php:8.3-apache`; doinstaluje `libsqlite3-dev` + rozšíření
  `pdo_sqlite`, zapne `mod_rewrite` a přesměruje DocumentRoot na `public/`
  (`AllowOverride All` kvůli `.htaccess`). ENTRYPOINT je `docker-entrypoint.sh`.
- **`docker-entrypoint.sh`** — při startu vytvoří `data/` a nastaví vlastníka na
  `www-data`, aby do SQLite mohl zapisovat uživatel Apache (řeší typický
  permission problém u bind-mountu).
- **`docker-compose.yml`** — služba `web` (container `todo-t420-02`), port
  **8080:80**, bind-mount celého projektu (live změny kódu) a named volume
  `sqlite-data` pro perzistenci DB (přežije rebuild i `down`).

```bash
docker compose up -d --build   # build + start → http://localhost:8080
docker compose down            # stop (data zůstávají ve volume)
php tests/run.php              # testy (lokálně i v containeru)
```

---

## Konfigurace Claude Code (`.claude/`)

Projekt je zároveň ukázkou Claude Code slash příkazů a skills:

- `skills/php-review/SKILL.md` — code review PHP dle konvencí projektu
  (read-only: `Read, Grep, Glob`).
- `skills/run-tests/SKILL.md` — spuštění testovací sady a oprava **implementace**
  při selhání (`Bash, Read, Edit`).
- `settings.local.json` — lokální nastavení oprávnění (trust boundary).
