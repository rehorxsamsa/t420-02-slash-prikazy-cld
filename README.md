# Díl 02 — Slash příkazy a vlastní příkazy (Skills)

> **Co se naučíš:** vestavěné slash příkazy `/clear`, `/cost`, `/review`, `/memory`, `/agents`,
> `/permissions`, `/vim`, `/doctor`, `/config`, `/model` — a hlavně jak si napsat **vlastní příkaz**
> (dnes formou **Skill**).
>
> **Na čem:** na codebase z dílu 01 + přidaná testovací sada (čistý PHP test runner bez frameworku).

---

## Obsah složky tohoto dílu

```
t420-02-slash-prikazy-cld/
├── .claude/skills/
│   ├── php-review/SKILL.md     # vlastní příkaz /php-review
│   └── run-tests/SKILL.md      # vlastní příkaz /run-tests
├── src/                         # codebase (s přidaným interface pro testovatelnost)
├── tests/
│   ├── run.php                  # minimalistický test runner bez frameworku
│   ├── TaskServiceTest.php      # 6 testů business logiky
│   └── Fake/InMemoryTaskRepository.php
└── autoload.php
```

Spusť testy, ať vidíš, že to celé žije (PHP běží jen v Dockeru, viz níže):
```bash
cd t420-02-slash-prikazy-cld
docker compose up -d --build
docker exec todo-t420-02 php tests/run.php
```
Výstup: `Prošlo: 6   Selhalo: 0   Vše OK ✨`

---

## Předpoklady a instalace

Pro tenhle díl potřebuješ dvě věci — **Claude Code** (kvůli slash příkazům a skills) a **Docker**. Platí pravidlo celého workspace: **PHP je záměrně jen v Dockeru** — na hostiteli se neinstaluje, všechno PHP (aplikace i testy) běží v kontejneru.

### Claude Code

```bash
# Varianta A — přes npm (jakýkoliv OS, potřebuješ Node.js 18+)
npm install -g @anthropic-ai/claude-code

# Varianta B — instalační skript (macOS / Linux / WSL)
curl -fsSL https://claude.ai/install.sh | bash

# Ověření
claude --version
```

V kořeni projektu pak stačí spustit `claude`.

### Spuštění aplikace v Dockeru (doporučeno)

Předpoklad: nainstalovaný **Docker** + **Docker Compose** (`docker --version`). Nic víc — PHP ani rozšíření řešit nemusíš, vše je v image.

```bash
cd t420-02-slash-prikazy-cld
docker compose up -d --build   # build + start na pozadí
docker compose logs -f web     # sledování logů (Ctrl+C ukončí jen sledování)
docker compose down            # zastavení (SQLite data zůstávají ve volume)
```

Pak otevři **http://localhost:8080** — uvidíš aplikaci Seznam úkolů s progress barem; funguje přidávání, odškrtávání i mazání úkolů. Vzhled je nastylovaný přes **Bootstrap 5** (linkovaný z CDN v `templates/tasks.php`), takže prohlížeč pro plný vzhled potřebuje přístup na internet.

**Co Docker dělá za tebe:**
- **`Dockerfile`** — image `php:8.3-apache`; doinstaluje rozšíření `pdo_sqlite`, zapne `mod_rewrite` a přesměruje DocumentRoot na `public/`.
- **`docker-compose.yml`** — služba `web` na portu **8080:80**, bind-mount celého projektu (změny v kódu jsou hned vidět) a named volume `sqlite-data` pro perzistenci databáze (přežije i `down`/rebuild).
- **`docker-entrypoint.sh`** — při startu nastaví práva na `data/`, aby do SQLite mohl zapisovat uživatel `www-data` (řeší typický permission problém u bind-mountu).

### Spouštění PHP příkazů (testy apod.)

Lokální PHP neexistuje — všechny `php` příkazy se pouští přes `docker exec` v běžícím kontejneru `todo-t420-02`:

```bash
docker exec todo-t420-02 php tests/run.php    # testovací sada
docker exec todo-t420-02 php -v              # verze PHP v kontejneru
```

> Databáze se inicializuje sama při prvním requestu/testu (`CREATE TABLE IF NOT EXISTS` + seed 3 úkolů do `data/tasks.sqlite`). Žádné migrace navíc nespouštíš. Detaily architektury jsou v [`ARCHITECTURE.md`](ARCHITECTURE.md).

---

## 1. Vestavěné slash příkazy

Slash příkazy píšeš během session. Napíšeš `/` a vyjede nabídka. Z cheatsheetu projdeme ty důležité na praktických příkladech nad naší codebase.

> 💡 V roce 2026 je vestavěných příkazů přes 60. Napiš `/` a uvidíš všechny dostupné ve tvojí verzi. `/help` je vždy zdroj pravdy.

### `/cost` — kolik tě session stojí

```
> /cost
```
Ukáže spotřebu tokenů a odhad ceny aktuální session. **Praktické použití:** než pustíš velký refaktor přes celou codebase, mrkni na `/cost`, ať máš představu, kolik kontextu už máš snědeno.

### `/review` — code review

Vestavěný `/review` udělá obecné code review. Vyzkoušej na naší service vrstvě:
```
> /review
```
Claude projde změny / kód a dá ti zpětnou vazbu k bezpečnosti, kvalitě a stylu. (Za chvíli si ukážeme, jak udělat **projektově specifický** review přes vlastní příkaz.)

### `/memory` — editace CLAUDE.md

```
> /memory
```
Otevře `CLAUDE.md` k editaci. **Praktické použití:** když si všimneš, že Claude opakovaně dělá něco proti tvým konvencím, přidej pravidlo do `CLAUDE.md` přes `/memory`. Příklad pro náš projekt:
```
> /memory
[přidáš řádek] "Testy se spouští příkazem: docker exec todo-t420-02 php tests/run.php"
```

### `/permissions` — bezpečnostní hranice

```
> /permissions
```
Nastavíš, co Claude smí/nesmí bez ptaní (např. povolit `Bash(docker exec todo-t420-02 php tests/run.php:*)`, zakázat `Bash(rm -rf *)`). Pro tým je to klíčové — definuješ trust boundary jednou.

### `/doctor` — health check

```
> /doctor
```
Diagnostika instalace Claude Code. Když něco nefunguje (MCP server nenaskočí, divné chování), `/doctor` je první krok.

### `/vim` — Vim mód

```
> /vim
```
Zapne Vim klávesy v promptu. Pro vimíře příjemné, ostatní můžou ignorovat.

### `/agents`, `/config`, `/model`

- `/agents` — správa subagentů (k tomu se vrátíme v dílu 06)
- `/config` — zobrazení/změna konfigurace (díl 03)
- `/model` — přepnutí modelu (díl 03)

---

## 2. Vlastní příkazy = Skills (klíčová část dílu)

V roce 2026 byly vlastní příkazy sloučeny se **Skills**:

| | Legacy | Doporučeno 2026 |
|---|---|---|
| Umístění | `.claude/commands/jmeno.md` | `.claude/skills/jmeno/SKILL.md` |
| Invokace | `/jmeno` | `/jmeno` (stejně) |
| Navíc | — | Claude umí Skill zavolat **sám**, když se hodí |
| Struktura | jeden soubor | složka (může mít i pomocné soubory, skripty) |

> Když existuje skill i command stejného jména, **vyhrává skill.** Tvoje staré `.claude/commands/` soubory fungují dál.

### Náš první vlastní příkaz: `/php-review`

Obecný `/review` neví nic o našich konvencích (vrstvení Controller→Service→Repository, prepared statements…). Vyrobíme si **projektový** review. Soubor `.claude/skills/php-review/SKILL.md`:

```markdown
---
name: php-review
description: Provede code review PHP-OOP kódu podle konvencí tohoto projektu...
argument-hint: [cesta-k-souboru-nebo-složce]
allowed-tools: Read, Grep, Glob
---

# PHP Review — projektová pravidla

Proveď důkladné code review pro: $ARGUMENTS

## Architektura
1. Vrstvení — Controller nikdy nesahá na Repository ani PDO přímo...
2. Business logika patří do Service vrstvy...
...
```

(Celý soubor je v tomto dílu — otevři `.claude/skills/php-review/SKILL.md`.)

**Rozbor klíčových částí:**
- **YAML frontmatter** nahoře: `name`, `description` (podle něj Claude pozná, kdy skill nabídnout), `argument-hint`, `allowed-tools` (omezí, co skill smí — tady jen čtení, žádné editace).
- **`$ARGUMENTS`** — sem se doplní to, co napíšeš za příkaz.
- **Tělo** je prompt, který Claude vykoná.

**Spuštění:**
```
> /php-review src/Controller/TaskController.php
```
Claude udělá review přesně podle našich pravidel a označí závažnost 🔴/🟡/🟢.

Zkus i celou složku:
```
> /php-review src/Repository/
```

### Druhý vlastní příkaz: `/run-tests`

Tenhle používá fintu — **`!` v těle příkazu spustí shell a výstup vloží do promptu**, takže Claude vidí výsledek testů, než začne přemýšlet. Klíčová část `.claude/skills/run-tests/SKILL.md`:

```markdown
---
name: run-tests
description: Spustí testovací sadu projektu a v případě chyb je analyzuje a opraví...
allowed-tools: Bash, Read, Edit
---

# Run tests

## Změněné soubory
!`git status --short`

## Postup
1. Spusť testy: docker exec todo-t420-02 php tests/run.php
2. Pokud projdou — shrnutí.
3. Pokud selžou — najdi příčinu v IMPLEMENTACI (ne v testu), oprav, spusť znovu.
4. Nikdy „neopravuj" test jeho oslabením.
```

**Spuštění:**
```
> /run-tests
```
Claude spustí `docker exec todo-t420-02 php tests/run.php`, a když něco selže, opraví **implementaci** (ne test).

### Vyzkoušej celý workflow (doporučené cvičení)

1. Rozbij úmyslně business logiku — v `src/Service/TaskService.php` v metodě `progress()` změň `* 100` na `* 1000`.
2. Spusť `> /run-tests`.
3. Sleduj, jak Claude najde, že test `progress() spočítá procenta` selhal (čekal 50, dostal 500), a opraví implementaci zpět.

Tohle je esence agentního vývoje: **definuješ očekávané chování testem, Claude dohání implementaci.**

---

## 3. Kdy command (skill) a kdy ne

Vlastní příkaz se vyplatí, když **opakuješ stejný postup**. Náš `/php-review` zapouzdřuje pravidla, která bys jinak psal do promptu pokaždé znovu. Pravidlo:

> Děláš něco potřetí stejně? → udělej z toho skill.

Příklady dalších užitečných skillů pro PHP projekt: `/migration` (vygeneruj DB migraci podle konvence), `/endpoint` (přidej CRUD endpoint napříč vrstvami), `/commit` (vytvoř commit podle Conventional Commits).

---

## 7 zajímavostí o tomto projektu

1. **Žádný framework, žádný Composer.** Celá aplikace stojí na ručně psaném PSR-4 autoloaderu — `autoload.php` má pouhých 26 řádků a mapuje `App\` → `src/`. Nové třídy stačí pojmenovat podle cesty a fungují.
2. **Vlastní test runner místo PHPUnit.** `tests/run.php` má 108 řádků a zvládá vše potřebné: testy jsou pojmenované closures v poli, k dispozici jsou asserty `assert_same`, `assert_true` i `assert_throws` — s českými chybovými hláškami („očekáváno / skutečnost").
3. **Testy běží úplně bez databáze.** Díky Dependency Inversion závisí `TaskService` na `TaskRepositoryInterface`; testy mu podstrčí `InMemoryTaskRepository` (obyčejné pole v paměti), takže sada doběhne za zlomek sekundy i bez SQLite.
4. **Databáze se nainstaluje sama.** `Core\Database::connection()` je lazy singleton — při prvním dotazu vytvoří schéma (`CREATE TABLE IF NOT EXISTS`) a do prázdné tabulky naseeduje 3 ukázkové úkoly. Žádné migrace, žádný setup skript.
5. **Router se vejde do 46 řádků.** Přesto umí placeholdery jako `{id}`, které handleru předá už přetypované na `int`. Všechny routy se registrují na jednom místě v `public/index.php`.
6. **Celý projekt je miniaturní schválně.** Zdrojový kód (`src/` + `tests/`) má dohromady ~600 řádků — dost málo na přečtení za večer, dost na ukázku plného vrstvení Controller → Service → Repository včetně PRG redirectů po zápisu.
7. **Docker entrypoint řeší jeden záludný detail.** Bind-mount projektu do kontejneru typicky rozbije zápis do SQLite kvůli právům; `docker-entrypoint.sh` proto při startu přenastaví vlastníka `data/` na `www-data` — bez toho by aplikace v Dockeru spadla na prvním INSERT.

---

## Shrnutí dílu 02

Umíš vestavěné příkazy (`/cost`, `/review`, `/memory`, `/permissions`, `/doctor`, `/vim`…) a hlavně **vlastní příkazy jako Skills** — `.claude/skills/<jméno>/SKILL.md` s YAML frontmatterem, `$ARGUMENTS` a `!` pro vložení shell výstupu. Postavili jsme `/php-review` (projektové review) a `/run-tests` (spuštění + oprava testů). Víš, že legacy `.claude/commands/` funguje, ale Skills jsou cesta 2026.

---

## ✅ Test dílu 02

**1. Jaký je dnes doporučený formát vlastních příkazů a kam se ukládá? Funguje ještě formát z cheatsheetu?**

<details><summary>Odpověď</summary>

Doporučený formát jsou **Skills**: `.claude/skills/<jméno>/SKILL.md`. Invokují se přes `/<jméno>`, navíc je Claude umí volat sám. Formát z cheatsheetu (`.claude/commands/jmeno.md`) je **legacy**, ale stále funguje. Když existuje skill i command stejného jména, vyhrává skill.
</details>

**2. K čemu slouží `$ARGUMENTS` v SKILL.md?**

<details><summary>Odpověď</summary>

Zástupný symbol, kam se vloží text, který napíšeš za název příkazu. Např. `/php-review src/Foo.php` → `$ARGUMENTS` = `src/Foo.php`.
</details>

**3. Co dělá `!` na začátku řádku uvnitř SKILL.md (např. `!`git status`)?**

<details><summary>Odpověď</summary>

Spustí shell příkaz a jeho **výstup vloží do promptu** ještě před tím, než Claude začne pracovat. Claude tak vidí aktuální stav (změněné soubory, výsledek testů) jako vstup. Přesně tohle používá náš `/run-tests`.
</details>

**4. Před velkým refaktorem napříč celou codebase — který příkaz mrkneš a proč?**

<details><summary>Odpověď</summary>

`/cost` — ukáže spotřebu tokenů a odhad ceny aktuální session, takže víš, kolik kontextu už máš snědeno, než pustíš drahou operaci.
</details>

**5. Claude opakovaně porušuje tvoji konvenci „business logika patří do Service". Co s tím systémově uděláš?**

<details><summary>Odpověď</summary>

Přidáš pravidlo do `CLAUDE.md` přes `/memory`. CLAUDE.md se načítá na začátku každé session, takže pravidlo platí napříč.
</details>

**6. K čemu je `allowed-tools` ve frontmatteru skillu? Uveď příklad rozdílu mezi `/php-review` a `/run-tests`.**


<details><summary>Odpověď</summary>

Omezuje, jaké nástroje smí skill použít. `/php-review` má `allowed-tools: Read, Grep, Glob` (jen čte, nic neupravuje — review nemá měnit kód). `/run-tests` má `Bash, Read, Edit` (potřebuje spustit testy a opravit implementaci).
</details>

**7. Test selže: očekáváno 50, skutečnost 500. Podle pravidel `/run-tests` — opraví Claude test, nebo implementaci?**

<details><summary>Odpověď</summary>

**Implementaci.** Test definuje očekávané chování; pravidlo skillu výslovně zakazuje „opravit" test jeho oslabením. Claude najde chybu v implementaci (`progress()`) a opraví ji.
</details>

→ Pokračuj na [Díl 03 — Konfigurace, modely a klíčové soubory](../t420-03-konfigurace-modely-cld/README.md)
