# Architektura projektu

Todo aplikace v **čistém PHP 8.3 (OOP)** — bez frameworku a bez Composeru.
Demonstruje vrstvenou architekturu (Controller → Service → Repository), ruční
PSR-4 autoloading, SQLite přes PDO a testování bez frameworku.

---

## Adresářová struktura

```
t420-02-slash-prikazy-cld/
├── public/                  # Webroot (DocumentRoot Apache)
│   ├── index.php            # Front controller — jediný vstupní bod
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
│   └── tasks.php            # HTML šablona seznamu úkolů
├── tests/                   # Vlastní test runner, namespace Tests\
│   ├── run.php              # Spouštěč: php tests/run.php
│   ├── TaskServiceTest.php  # Jednotkové testy business logiky
│   └── Fake/
│       └── InMemoryTaskRepository.php
├── data/                    # SQLite databáze (tasks.sqlite za běhu)
├── autoload.php             # Ruční PSR-4 autoloader (App\ → src/)
├── Dockerfile               # PHP 8.3 + Apache, pdo_sqlite, mod_rewrite
├── docker-compose.yml       # Služba web na portu 8080
├── docker-entrypoint.sh     # Nastaví práva na data/ při startu
└── .claude/                 # Konfigurace Claude Code (skills, settings)
```

---

## Vrstvená architektura

Tok requestu jde striktně jedním směrem; každá vrstva zná jen tu pod sebou.

```
HTTP request
   │
   ▼
public/index.php ──► Router ──► TaskController ──► TaskService ──► TaskRepository ──► PDO/SQLite
                    (Core)      (Controller)       (Service)        (Repository)       (Core/Database)
                                     │
                                     ▼
                              templates/tasks.php
```

| Vrstva | Třída | Zodpovědnost | Co smí volat |
|---|---|---|---|
| **Front controller** | `public/index.php` | Bootstrap, registrace rout | Router, Controller |
| **Routing** | `Core\Router` | Mapuje HTTP metodu + cestu na callable (podpora `{id}`) | — |
| **Controller** | `Controller\TaskController` | Orchestrace requestu, čtení `$_POST`, render/redirect | Service |
| **Service** | `Service\TaskService` | Business logika (validace názvu, výpočet `progress()`) | Repository (přes rozhraní) |
| **Repository** | `Repository\TaskRepository` | Jediné místo s SQL; prepared statements | `Core\Database` (PDO) |
| **Model** | `Model\Task` | Doménová entita + `fromRow()` | — |
| **View** | `templates/tasks.php` | Vykreslení HTML, escapování výstupu | — |

### Klíčové principy

1. **Vrstvení (separation of concerns)** — Controller nikdy nesahá na Repository
   ani na PDO přímo. Vždy přes Service. Repository je jediné místo s SQL.
2. **Dependency Inversion** — `TaskService` závisí na
   `TaskRepositoryInterface`, ne na konkrétní implementaci. V produkci dostane
   `TaskRepository` (SQLite), v testech `InMemoryTaskRepository` (paměť).
3. **`declare(strict_types=1)`** ve všech PHP souborech.
4. **`final` třídy** — žádná z aplikačních tříd není určená k dědění.
5. **Bezpečnost** — repository používá výhradně **prepared statements**;
   šablona escapuje výstup přes `htmlspecialchars` (ochrana proti XSS).

---

## Datová vrstva

- **Úložiště:** SQLite soubor `data/tasks.sqlite`.
- **Přístup:** `Core\Database::connection()` — line singleton (statická
  `$instance`), který při prvním volání:
  1. otevře PDO připojení (`ERRMODE_EXCEPTION`, `FETCH_ASSOC`),
  2. spustí `migrate()` — `CREATE TABLE IF NOT EXISTS tasks (...)`,
  3. naseeduje 3 ukázkové úkoly, je-li tabulka prázdná.
- **Schéma `tasks`:** `id` (PK, autoincrement), `title` (TEXT), `done`
  (INTEGER 0/1), `created_at` (TEXT, ISO 8601).

---

## Routing

`Core\Router` drží pole rout a v `dispatch()` hledá první shodu metody i cesty.
Podporuje parametr `{id}` (přepíše se na `(?P<id>\d+)`), který se handleru předá
jako `int`. Bez shody vrací `404`.

| Metoda | Cesta | Handler | Akce |
|---|---|---|---|
| GET | `/` | `TaskController::index` | Výpis úkolů + procento hotových |
| POST | `/tasks` | `TaskController::store` | Přidání úkolu |
| POST | `/tasks/{id}/toggle` | `TaskController::toggle` | Přepnutí hotovo/nehotovo |
| POST | `/tasks/{id}/delete` | `TaskController::destroy` | Smazání úkolu |

Po zápisových akcích Controller přesměruje zpět na `/` (PRG vzor).

---

## Autoloading

Bez Composeru — dva ruční PSR-4 autoloadery (`spl_autoload_register`):

- `autoload.php`: prefix `App\` → adresář `src/`.
- `tests/run.php`: navíc prefix `Tests\` → adresář `tests/` (test helpery a fake).

---

## Testování

Vlastní minimalistický runner bez frameworku — `tests/run.php`:

- Spuštění: `php tests/run.php` (návratový kód 0 = OK, 1 = selhání).
- Každý soubor `tests/*Test.php` vrací `array<string, callable>` pojmenovaných testů.
- Assertion helpery: `assert_same()`, `assert_true()`, `assert_throws()`.
- `TaskServiceTest.php` testuje business logiku izolovaně — místo databáze
  používá `Tests\Fake\InMemoryTaskRepository` (díky závislosti na rozhraní).
  Pokrývá: vytvoření úkolu, ořez mezer, odmítnutí prázdného názvu, toggle a
  výpočet `progress()`.

---

## Běhové prostředí (Docker)

- **`Dockerfile`** — `php:8.3-apache`; doinstaluje `libsqlite3-dev` +
  rozšíření `pdo_sqlite`, zapne `mod_rewrite` a přesměruje DocumentRoot na
  `public/`. ENTRYPOINT (`docker-entrypoint.sh`) nastaví práva na `data/`, aby
  do SQLite mohl zapisovat uživatel `www-data`.
- **`docker-compose.yml`** — služba `web`, port **8080:80**, bind-mount celého
  projektu (live změny kódu) a named volume `sqlite-data` pro perzistenci DB.

```bash
docker compose up -d --build   # build + start → http://localhost:8080
docker compose down            # stop (data zůstávají ve volume)
php tests/run.php              # testy (lokálně i v containeru)
```

---

## Konfigurace Claude Code (`.claude/`)

Projekt je zároveň ukázkou Claude Code slash příkazů a skills:

- `skills/php-review/SKILL.md` — code review PHP dle konvencí projektu.
- `skills/run-tests/SKILL.md` — spuštění testovací sady.
- `settings.local.json` — lokální nastavení oprávnění (trust boundary).
