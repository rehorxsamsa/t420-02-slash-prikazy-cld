# Architektura

Seznam úkolů, **PHP 8.3 OOP, zero-dependency** — bez frameworku, bez Composeru, bez runtime knihoven. Backend stojí na ručním PSR-4 autoloadingu, striktně vrstveném toku `Controller → Service → Repository` a SQLite přes PDO. Jediná externí věc je CDN link na Bootstrap 5 v šabloně (CSS, žádný build).

Účel codebase je didaktický (ukázka Claude Code slash příkazů a skills), takže je záměrně malý, ale drží produkční konvence — viz `CLAUDE.md` a vynucování přes `/php-review`.

---

## Layout

```
public/            Webroot (Apache DocumentRoot). Front controller + .htaccess (rewrite → index.php)
src/               App\ … PSR-4 → src/
  Controller/      TaskController          — request orchestrace, PRG
  Service/         TaskService             — business logika, jediný závislý na rozhraní repo
  Repository/      TaskRepositoryInterface — port
                   TaskRepository          — SQLite adapter (jediné místo s SQL)
  Model/           Task                    — doménová entita + fromRow()
  Core/            Router                  — (method, path) → callable
                   Database                — PDO singleton + self-migrate + seed
templates/         tasks.php               — view (require-ovaná z controlleru)
tests/             Tests\ … vlastní runner (run.php), *Test.php, Fake/InMemoryTaskRepository
data/             tasks.sqlite (vzniká za běhu; named volume v Dockeru)
autoload.php      App\ → src/  (spl_autoload_register)
Dockerfile / docker-compose.yml / docker-entrypoint.sh
.claude/          skills (php-review, run-tests), settings
```

---

## Vrstvení a kontrakty

Tok je jednosměrný; každá vrstva zná jen API vrstvy pod sebou. Hranice mezi Service a Repository je **rozhraní** (`TaskRepositoryInterface`) — jediný švec, přes který se v testech podstrkává fake.

```
HTTP → index.php → Router → TaskController → TaskService → «TaskRepositoryInterface»
                                    │                              ├── TaskRepository        (prod, PDO/SQLite)
                                    └── templates/tasks.php        └── InMemoryTaskRepository (test, paměť)
```

| Vrstva | Třída | Kontrakt / zodpovědnost | Smí volat |
|---|---|---|---|
| Front controller | `public/index.php` | Bootstrap, wiring rout, dispatch | Router, Controller |
| Routing | `Core\Router` | `(method, path)` → callable; `{id}` → `int`; jinak 404 | — |
| Controller | `Controller\TaskController` | Čtení `$_POST`, delegace na Service, render/redirect. **Žádná** business logika ani SQL | Service |
| Service | `Service\TaskService` | Validace, `progress()`, orchestrace domény | Repository (přes rozhraní) |
| Repository | `Repository\TaskRepository` | **Jediné místo s SQL**; prepared statements; row → `Task` | `Core\Database` |
| Model | `Model\Task` | Entita, `readonly` identita, `fromRow()` | — |
| View | `templates/tasks.php` | HTML, `htmlspecialchars(ENT_QUOTES)` na veškerý výstup | — |

**Invarianty, které drží `/php-review`:** `declare(strict_types=1)` všude; třídy `final`; `readonly` na neměnném stavu (`Task::$id`, `$createdAt`, injektované závislosti); SQL výhradně přes `:param` prepared statements; výstup vždy escapovaný.

---

## Design decisions & trade-offs

Věci, které nejsou z kódu na první pohled zřejmé a stojí za pozornost při rozšiřování:

- **Poor-man's DI.** `TaskService::__construct(?TaskRepositoryInterface $repo = null)` a `TaskController::__construct(?TaskService = null)` si při `null` vyrobí produkční závislost samy (`?? new TaskRepository()`). Není žádný kontejner — wiring je implicitní default + explicitní override v testech. Důsledek: produkční kód váže konkrétní `TaskRepository` (Service zná adapter), inverze závislosti se reálně projeví jen v testovací cestě. Pragmatické pro tuto velikost; při růstu je to první místo na skutečný DIC nebo composition root v `index.php`.
- **`Database` = per-proces PDO singleton.** Statická `?PDO $instance`, lazy. Migrace i seed běží přes `connection()` při prvním dotazu — idempotentně (`CREATE TABLE IF NOT EXISTS` + seed jen na prázdné tabulce), takže cena za request je jeden `COUNT(*)`. Singleton je vázán na model „proces = request" (PHP-FPM/Apache mpm-prefork); ve sdíleném/async runtime (Swoole, RoadRunner) by cachovaný `PDO` přežíval mezi requesty a byl by potřeba jiný lifecycle.
- **Repository nezná `Database` jako závislost přes konstruktor** — tahá si `Database::connection()` staticky. Testy proto Repository neinstancují vůbec; jdou přes `InMemoryTaskRepository`. Pokud budeš chtít Repository testovat proti reálnému PDO, je to místo, kde protáhnout PDO konstruktorem.
- **`toggle` je blind write.** `UPDATE … SET done = 1 - done WHERE id = :id` přepne stav bez předchozího čtení — atomicky na úrovni SQL, žádný read-modify-write race.
- **Chybějící produkční hardening (záměr didaktické appky):** žádné CSRF tokeny na POST akcích, `TaskController::store()` **tiše polyká** `InvalidArgumentException` z validace (v reálu flash message), žádné logování. `progress()` je bod, na kterém výukový díl 2 demonstruje `/php-review` a psaní testů.

---

## Request lifecycle (POST `/tasks`)

1. Apache + `.htaccess` přepíšou vše (mimo existující soubory) na `public/index.php`.
2. `index.php` — autoload, sestavení `Router` + `TaskController`, registrace rout, `dispatch(REQUEST_METHOD, REQUEST_URI)`.
3. `Router` matchne `POST /tasks` → `TaskController::store()`.
4. Controller přečte `$_POST['title']`, zavolá `TaskService::add()`; případnou `InvalidArgumentException` spolkne.
5. `Service::add()` — `trim`, prázdné → výjimka, jinak `Repository::create()`.
6. `Repository::create()` — prepared `INSERT`, pak `find(lastInsertId())`; při nenalezení `?? throw RuntimeException`.
7. Controller odpoví `302 → /` (PRG).
8. Následný `GET /` projde stejnými vrstvami do `index()`, vyrenderuje `tasks.php` + `progress()`.

---

## Routing

Registrace v `public/index.php`; `{id}` v patternu → `(?P<id>\d+)`, předáno handleru jako `int`.

| Method | Path | Handler | Akce |
|---|---|---|---|
| GET | `/` | `index` | výpis + procento hotových |
| POST | `/tasks` | `store` | přidání |
| POST | `/tasks/{id}/toggle` | `toggle` | přepnutí done |
| POST | `/tasks/{id}/delete` | `destroy` | smazání |

Zápisové akce končí redirectem na `/` (PRG).

---

## Persistence

Schéma `tasks`: `id` PK AUTOINCREMENT, `title` TEXT NOT NULL, `done` INTEGER 0/1 default 0, `created_at` TEXT (ISO 8601, `date('c')`). Žádné migrace navíc — schéma vzniká v `Database::migrate()`.

Repository ↔ SQL:

| Metoda | SQL | Pozn. |
|---|---|---|
| `all(): list<Task>` | `SELECT * … ORDER BY id DESC` | map přes `Task::fromRow()` |
| `find(int): ?Task` | `SELECT * … WHERE id = :id` | `null` když nic |
| `create(string): Task` | prepared `INSERT`, pak `find(lastInsertId())` | re-read, jinak `RuntimeException` |
| `toggle(int): void` | `UPDATE … SET done = 1 - done WHERE id = :id` | blind write |
| `delete(int): void` | `DELETE … WHERE id = :id` | |

`Task::fromRow()` přetypuje `id`→`int`, `done`→`bool`.

---

## Autoloading

Dva ruční `spl_autoload_register` PSR-4 mappery, bez Composeru:

- `autoload.php`: `App\` → `src/`
- `tests/run.php`: navíc `Tests\` → `tests/` (helpery, fake)

Nová třída = shoda **namespace ↔ cesta**, jinak ji autoloader nenajde.

---

## Testy

Vlastní runner `tests/run.php`, žádný framework. Exit `0` = OK, `1` = fail (CI-friendly).

- Každý `tests/*Test.php` vrací `array<string, callable>`; runner projde přes `glob('tests/*Test.php')`.
- Asserty: `assert_same()`, `assert_true()`, `assert_throws()`.
- `TaskServiceTest.php` testuje Service **izolovaně** přes `Tests\Fake\InMemoryTaskRepository` (6 případů: create, trim, prázdný název, toggle, `progress()`, `progress()` nad prázdným).
- Runner nemá CLI filtr — izolace jednoho testu = zakomentovat ostatní, nebo přesunout ostatní `*Test.php` mimo glob.

> **Kontrakt při rozšíření repository:** novou metodu přidej do `TaskRepositoryInterface` **i** do `InMemoryTaskRepository`, jinak fake přestane implementovat rozhraní a sada spadne. Testy definují očekávané chování — při selhání se opravuje implementace, ne test.

---

## Runtime (Docker)

PHP je záměrně **jen v kontejneru** (`todo-t420-02`) — na hostiteli není a spouští se přes `docker exec`.

- **Dockerfile** — `php:8.3-apache`, `pdo_sqlite`, `mod_rewrite`, DocumentRoot → `public/` (`AllowOverride All` kvůli `.htaccess`). ENTRYPOINT `docker-entrypoint.sh`.
- **docker-entrypoint.sh** — nastaví `data/` na `www-data`, aby Apache mohl psát do SQLite (řeší permission problém bind-mountu).
- **docker-compose.yml** — služba `web`, `8080:80`, bind-mount projektu (live kód) + named volume `sqlite-data` (DB přežije rebuild i `down`).

```bash
docker compose up -d --build                 # → http://localhost:8080
docker exec todo-t420-02 php tests/run.php   # testy
docker compose down                          # data zůstávají ve volume
```

---

## `.claude/`

Codebase je zároveň ukázkou Claude Code skills:

- `skills/php-review/SKILL.md` — review proti konvencím výše (read-only: `Read, Grep, Glob`).
- `skills/run-tests/SKILL.md` — běh sady + oprava **implementace** při selhání (`Bash, Read, Edit`).
- `settings.local.json` — lokální oprávnění (trust boundary).
