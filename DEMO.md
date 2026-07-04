# DEMO — jak tuto aplikaci prezentovat úplnému laikovi

> **Pro koho je tento návod:** pro tebe, kdo bude prezentovat. Publikum je člověk (nebo skupina),
> který **nikdy neslyšel o AI ani o Claude Code**. Nepředpokládej žádné znalosti programování.
> Cíl není naučit je psát kód — cíl je, aby **pochopili, co se tu děje a proč je to zajímavé**.
>
> **Délka:** 20–35 minut podle toho, kolik živých ukázek uděláš.
> **Formát:** mluvíš, ukazuješ na obrazovce, necháváš je reagovat.

---

## 0. Co si připravit PŘED prezentací (10 minut předem)

Aby ses během dema nezdržoval technikou, udělej tohle dřív, než publikum přijde:

```bash
cd t420-02-slash-prikazy-cld
docker compose up -d --build          # nastartuje aplikaci na http://localhost:8080
docker exec todo-t420-02 php tests/run.php   # ověř, že testy projdou (Prošlo: 6, Selhalo: 0)
```

Checklist připravenosti:

- [ ] V prohlížeči mám otevřený **http://localhost:8080** a vidím aplikaci Seznam úkolů se seznamem úkolů a řádkem „Hotovo: … %".
- [ ] Mám otevřený terminál s běžícím **`claude`** v kořeni projektu.
- [ ] Písmo v terminálu i prohlížeči je **zvětšené** (Ctrl+`+`), aby to bylo čitelné i zezadu.
- [ ] Vím, že aplikace při startu má 3 ukázkové úkoly (naseedují se samy) — takže i „prázdné" demo něco ukáže.
- [ ] Mám po ruce tuhle stránku jako tahák.

> 💡 **Zásada čísla jedna:** vše, co budeš ukazovat, si **jednou nanečisto vyzkoušej**. Živé demo, které
> spadne, je horší než žádné. Kdyby AI vrátila něco jiného než čekáš, není to chyba — jen řekni
> „AI není kalkulačka, pokaždé to formuluje trochu jinak" a jeď dál.

---

## 1. Rámování na úvod — co je AI a co je Claude (3–4 min)

Tohle je nejdůležitější část pro laika. Nezačínej kódem. Začni analogií.

**Řekni zhruba tohle (vlastními slovy):**

> „Znáte to, když si píšete se zákaznickou podporou v chatu a na druhé straně odpovídá počítač?
> To je jednoduchá **AI** — umělá inteligence. Je to program, který rozumí běžné řeči a odpovídá
> běžnou řečí. Nejznámější je asi ChatGPT.
>
> **Claude** je podobný pomocník od firmy Anthropic. A **Claude Code**, který teď uvidíte, je
> jeho speciální verze, která umí nejen povídat, ale i **sama pracovat s počítačem** — číst soubory,
> psát text, spouštět programy. Představte si šikovného asistenta, kterému řeknete česky, co
> potřebujete, a on to udělá — a přitom vám vysvětlí, co dělá."

**Klíčová analogie, kterou si zapamatují:**

> „Doteď se s počítačem mluvilo přes **tlačítka a menu**. S Claude Code se s ním mluví **jako s kolegou** —
> normálními větami."

Vyhni se slovům: *model, token, prompt, LLM, kontext, API*. Ta si necháš na dotazy.

---

## 2. Nejdřív výsledek, potom kouzlo — ukázka hotové aplikace (3–5 min)

Laik ocení, když nejdřív vidí **něco hmatatelného**. Přepni do prohlížeče na `http://localhost:8080`.

**Co říct a ukázat:**

1. „Tohle je úplně obyčejná aplikace na **seznam úkolů** — todo list. Nahoře vidíte, kolik procent
   úkolů je hotových."
2. Do políčka napiš třeba `Koupit mléko` a klikni **Přidat**. → objeví se v seznamu.
3. Klikni na **✓** u jednoho úkolu → přeškrtne se a procento hotových se změní.
4. Klikni na **✕** → úkol zmizí.

**Pointa, kterou řekni nahlas:**

> „Tahle appka není nic výjimečného — takových jsou miliony. Zajímavé je něco jiného:
> **jak vznikla a jak se dá měnit.** Celou ji nepsal řádek po řádku programátor v editoru —
> vznikala rozhovorem s AI. A teď vám ukážu, jak takový rozhovor vypadá."

> ⚠️ Nezabíhej do architektury (vrstvy Controller → Service → Repository, SQLite, PDO…).
> Pro laika je to šum. Ta technická hloubka je v `ARCHITECTURE.md` pro vývojáře, ne pro tuhle prezentaci.

---

## 3. Živé kouzlo — necháme Claude něco udělat (5–8 min)

Tohle je vrchol prezentace. Přepni do terminálu s `claude`.

**Vysvětli, na co se dívají:**

> „Tady dole píšu Claudovi normálním jazykem. Nahoře pak vidíte, jak přemýšlí a co dělá —
> které soubory si čte, co mění. Nic se neděje potají."

### Ukázka A — nechá si vysvětlit vlastní projekt (bezpečná, vždy funguje)

Napiš do promptu:

```
Vysvětli mi jednoduše, česky a bez odborných pojmů, co tahle aplikace dělá.
```

Nech Claude odpovědět a přečti odpověď nahlas. **Pointa:**

> „Všimněte si — nikdo mu nemusel říkat, o jaký projekt jde. On si sám přečetl soubory a zorientoval se."

### Ukázka B — malá viditelná změna (efektní)

Napiš třeba:

```
Přidej nad seznam úkolů krátký uvítací nadpis „Moje úkoly na dnešek".
```

Nech Claude provést změnu, pak **přepni do prohlížeče, obnov stránku (F5)** a ukaž, že tam nadpis je.

> „Řekl jsem to česky jednou větou. Claude našel správné místo v kódu, udělal změnu a hotovo.
> Kdybych to psal ručně, musel bych vědět, ve kterém z desítek souborů to je."

> 💡 Kdyby změna nevyšla napoprvé, je to skvělá učební chvíle: „Vidíte? Občas to napoprvé není ono —
> tak mu řeknu, co upravit, přesně jako kolegovi. Je to **dialog**, ne kouzelné tlačítko."

---

## 4. Zkratky = „slash příkazy" (3–4 min)

Teď přejdi k tématu tohoto dílu — **slash příkazy**. Pro laika je vysvětli jako **zkratky**.

**Řekni:**

> „Některé věci děláme pořád dokola. Aby se to nemuselo pokaždé vypisovat, má Claude **hotové zkratky** —
> píšou se s lomítkem na začátku, třeba `/`. Když napíšu jen lomítko, vyjede nabídka, jako když v mobilu
> začnete psát a on napovídá."

Napiš `/` a ukaž nabídku. Pak předveď **jednu** neškodnou vestavěnou zkratku, ideálně:

```
/cost
```

> „Tahle mi ukáže, **kolik ta dnešní práce s AI zhruba stála.** Ano — AI něco stojí, jako když si platíte
> asistenta. Tady rovnou vidím kolik."

(Nemusíš procházet všechny příkazy. Laikovi stačí pochopit princip „lomítko = zkratka".)

---

## 5. Vlastní zkratky na míru — to nejzajímavější (5–8 min)

Tady ukážeš, proč je tenhle projekt pro vývojáře výjimečný: **můžeš si vyrobit vlastní zkratky.**

**Řekni:**

> „A teď to nejlepší. Ty zkratky si můžu **napsat sám** — na věci, které v mém projektu dělám znovu a znovu.
> V tomhle projektu jsou hotové dvě."

### `/php-review` — „zkontroluj mi kvalitu práce"

> „Tahle zkratka znamená: ,Projdi tenhle kód a řekni, jestli splňuje naše pravidla — jestli je bezpečný
> a čistý.' Je to jako když dáte hotový dokument kolegovi na korekturu."

Předveď:

```
/php-review src/Controller/TaskController.php
```

Ukaž, že Claude vrací hodnocení se **semafory** 🔴 🟡 🟢 (závažnost nálezů). To je vizuálně srozumitelné i laikovi.

### `/run-tests` — „ověř, že nic není rozbité" (efektní finále)

Tohle je nejlepší zakončení, protože ukazuje **AI, která sama najde a opraví chybu**.

> „Programy se testují — máme sadu automatických kontrol, které řeknou, jestli appka funguje.
> Teď schválně **něco rozbiju** a necháme Claude, ať to najde a spraví sám."

1. Otevři `src/Service/TaskService.php`, v metodě `progress()` změň `* 100` na `* 1000` (ulož).
   - Řekni: „Tahle drobnost způsobí, že výpočet procent bude úplně mimo."
2. V Claude napiš:

```
/run-tests
```

3. Nech publikum sledovat, jak Claude:
   - spustí kontroly,
   - **jedna selže** (čekalo se 50, vyšlo 500),
   - Claude **sám najde příčinu** a opraví ji zpět,
   - kontroly znovu projdou.

**Pointa na závěr:**

> „Všimněte si jedné důležité věci: Claude neopravil **test**, aby chybu schoval. Opravil **skutečnou
> chybu v aplikaci.** Test říká, jak se to má chovat — a AI dohnala program, aby to tak fungoval.
> Přesně takhle dnes vývojáři s AI pracují: **člověk řekne, co má platit, AI to zařídí.**"

---

## 6. Shrnutí pro publikum (2 min)

Zakonči třemi větami, které si mají odnést:

1. **AI se ovládá řečí, ne tlačítky.** Řeknu česky, co chci — Claude to udělá a ukáže jak.
2. **Nic se neděje za zády.** Vidím každý krok: co si přečetl, co změnil, co spustil.
3. **Dá se to přizpůsobit.** Vlastní zkratky (`/php-review`, `/run-tests`) zapouzdřují to, co dělám často —
   z AI se stává asistent, který zná **pravidla mého projektu**.

> „Není to náhrada za člověka. Je to **zesilovač** — dělám tytéž věci rychleji a s menší chybovostí."

---

## 7. Časté otázky laiků (a jak na ně stručně)

| Otázka | Krátká odpověď pro laika |
|---|---|
| „To nahradí programátory?" | Ne. Je to nástroj, jako kalkulačka pro účetní — člověk pořád rozhoduje, kontroluje a zadává. |
| „Vidí to moje soubory / posílá to někam?" | Claude pracuje s tím, co mu ukážu ve složce projektu. Přemýšlení běží na serverech Anthropicu — proto se s citlivými daty zachází opatrně. |
| „Může to smazat nebo pokazit můj počítač?" | Na citlivé akce se ptá o svolení. A jde nastavit, co smí a nesmí bez ptaní (to jsou ta „oprávnění"). |
| „Odkud to ví, co je správně?" | Naučilo se to z obrovského množství textů a kódu. Není to neomylné — proto se výsledek vždy kontroluje (třeba právě testy). |
| „Stojí to peníze?" | Ano, práce s AI se platí podle objemu. Zkratka `/cost` ukáže kolik. |
| „Umí to i něco jiného než programování?" | Ano, tohle je verze pro práci s kódem. Obecný Claude umí i psát texty, shrnovat, odpovídat na otázky. |

---

## 8. Čeho se během prezentace vyvarovat

- **Žargonu.** Řekni „zkratka" místo „slash command", „kontroly" místo „testy/CI", „pravidla projektu"
  místo „konvence a code review". Odborné pojmy jen když se na ně někdo zeptá.
- **Architektury.** Vrstvy Controller → Service → Repository, SQLite, PDO, PSR-4 autoloading — to je pro
  vývojáře (viz `ARCHITECTURE.md`), ne pro tuhle prezentaci. Laika to ztratí.
- **Dlouhého čekání v tichu.** Když Claude přemýšlí, komentuj, co se děje na obrazovce.
- **Přehánění.** Neříkej „umí všechno". Ukaž reálnou chybu a opravu — důvěryhodnější než dokonalé demo.
- **Improvizovaných příkazů, které jsi nevyzkoušel.** Drž se scénáře, který ti prošel nanečisto.

---

## 9. Rychlý tahák (jen příkazy, v pořadí dema)

```bash
# PŘÍPRAVA (předem)
cd t420-02-slash-prikazy-cld
docker compose up -d --build
docker exec todo-t420-02 php tests/run.php   # má projít: Prošlo 6, Selhalo 0
# → otevři http://localhost:8080 a spusť `claude` v terminálu
```

```
# BĚHEM DEMA (v okně `claude`)
Vysvětli mi jednoduše, česky a bez odborných pojmů, co tahle aplikace dělá.
Přidej nad seznam úkolů krátký uvítací nadpis „Moje úkoly na dnešek".
/cost
/php-review src/Controller/TaskController.php

# Rozbití pro finále: v src/Service/TaskService.php v progress() změň * 100 na * 1000, ulož
/run-tests
```

```bash
# ÚKLID (po prezentaci)
git checkout -- .            # zahodí demo změny v kódu (nadpis, rozbití progress)
docker compose down          # zastaví aplikaci (data v úkolech zůstávají ve volume)
```

> **Reset úkolů do výchozích 3:** smaž databázi ve volume a nech ji naseedovat znovu — nejjednodušeji
> `docker compose down -v` (smaže i named volume `sqlite-data`), pak `docker compose up -d`.
