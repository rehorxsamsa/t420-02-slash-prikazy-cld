# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Jazyk

Komunikuj s uživatelem česky (platí z nadřazeného workspace). Nepushuj na žádný remote — lokální commity jen na vyžádání.

## Co to je

Aplikace Seznam úkolů v **čistém PHP 8.3 (OOP)** — bez frameworku a bez Composeru. Slouží jako codebase pro výukový díl o Claude Code slash příkazech a Skills. Detailní popis architektury je v `ARCHITECTURE.md`.

## Příkazy

**PHP je záměrně jen v Dockeru** — na hostiteli nainstalované není. Všechny `php` příkazy (testy apod.) spouštěj přes `docker exec` v kontejneru `todo-t420-02`; neinstaluj PHP lokálně.

```bash
docker compose up -d --build                        # build + start → http://localhost:8080
docker exec todo-t420-02 php tests/run.php          # celá testovací sada (exit 0 = OK, 1 = selhání)
docker compose down                                 # stop (SQLite data zůstávají v named volume)
```

**Spuštění jediného testu:** runner nemá CLI filtr. Testy jsou pojmenované položky v poli, které vrací každý `tests/*Test.php`. Pro izolaci jednoho testu dočasně zakomentuj ostatní v daném souboru, nebo přesuň ostatní `*Test.php` mimo `glob('tests/*Test.php')`.

## Architektura — co je potřeba vědět

**Striktní vrstvení, jednosměrný tok:**
```
public/index.php → Core\Router → Controller\TaskController → Service\TaskService → Repository\TaskRepository → Core\Database (PDO/SQLite)
```
- Controller **nikdy** nesahá na Repository ani PDO přímo — vždy přes Service.
- Business logika patří do Service, ne do Controlleru.
- Repository je **jediné** místo s SQL.

**Dependency Inversion kvůli testovatelnosti:** `TaskService` závisí na `Repository\TaskRepositoryInterface`. V produkci dostane `TaskRepository` (SQLite), v testech `Tests\Fake\InMemoryTaskRepository` (paměť, bez DB). Při přidávání metod na repository je přidej i do rozhraní a do fake implementace, jinak testy spadnou.

**Bez Composeru — ruční PSR-4 autoloading:** `autoload.php` mapuje `App\` → `src/`. `tests/run.php` přidává druhý autoloader `Tests\` → `tests/`. Nové třídy musí dodržet shodu namespace ↔ cesta.

**Databáze se inicializuje sama:** `Core\Database::connection()` je line singleton, který při prvním volání vytvoří schéma (`CREATE TABLE IF NOT EXISTS`) a naseeduje 3 úkoly, je-li tabulka prázdná. Soubor je `data/tasks.sqlite`. Žádné migrace navíc nejsou.

**Routing:** `Core\Router` mapuje `(metoda + cesta)` na callable, podporuje `{id}` (přijde handleru jako `int`). Routy se registrují v `public/index.php`. Zápisové akce končí redirectem na `/` (PRG).

## Konvence kódu (vynucované přes /php-review)

- `declare(strict_types=1)` v každém PHP souboru.
- Třídy `final`; vlastnosti `readonly`, pokud se po konstrukci nemění.
- SQL výhradně přes **prepared statements** (`:param`), nikdy konkatenace.
- Výstup do šablon přes `htmlspecialchars(..., ENT_QUOTES)`.
- Typové anotace u kolekcí (`@return list<Task>`).

## Skills (vlastní příkazy v `.claude/skills/`)

- `/php-review [cesta]` — code review proti konvencím výše.
- `/run-tests` — spustí `php tests/run.php`; při selhání opravuje **implementaci**, ne test (test definuje očekávané chování — nikdy ho neoslabuj jen aby prošel).

## Docker — na co pozor

SQLite potřebuje zápis do `data/`. `docker-entrypoint.sh` při startu nastaví vlastníka `data/` na `www-data` — to řeší jinak typický permission problém u bind-mountu. DocumentRoot je `public/`, ne kořen projektu.
