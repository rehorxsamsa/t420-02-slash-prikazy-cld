---
name: php-review
description: Provede code review PHP-OOP kódu podle konvencí tohoto projektu (vrstvení Controller→Service→Repository, prepared statements, strict_types, final třídy). Použij, když chceš zkontrolovat kvalitu a bezpečnost PHP souboru nebo složky.
argument-hint: [cesta-k-souboru-nebo-složce]
allowed-tools: Read, Grep, Glob
---

# PHP Review — projektová pravidla

Proveď důkladné code review pro: $ARGUMENTS

Zkontroluj kód proti těmto pravidlům našeho PHP-OOP projektu:

## Architektura
1. **Vrstvení** — Controller nikdy nesahá na Repository ani PDO přímo. Tok musí být Controller → Service → Repository → PDO.
2. **Business logika** patří do Service vrstvy, ne do Controlleru.
3. **Repository** je jediné místo, kde se sahá na databázi.

## Bezpečnost
4. **Prepared statements** — všechny SQL dotazy s uživatelským vstupem musí jít přes prepared statements (`:param`), nikdy ne string concatenation.
5. **XSS** — výstup do HTML šablon přes `htmlspecialchars(..., ENT_QUOTES)`.

## Styl (konvence projektu)
6. Každý PHP soubor má `declare(strict_types=1)`.
7. Třídy jsou `final`, kde to dává smysl.
8. Vlastnosti `readonly`, pokud se po konstrukci nemění.
9. Typové anotace (`@return list<Task>` apod.) u kolekcí.

## Výstup
Pro každý nalezený problém uveď:
- **Závažnost** (🔴 kritická / 🟡 střední / 🟢 drobnost)
- Konkrétní řádek nebo metodu
- Návrh opravy s ukázkou kódu

Pokud je kód v pořádku, napiš to jasně. Nevymýšlej problémy, kde nejsou.
