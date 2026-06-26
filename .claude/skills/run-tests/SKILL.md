---
name: run-tests
description: Spustí testovací sadu projektu (čistý PHP test runner bez frameworku) a v případě chyb je analyzuje a opraví. Použij, když chceš ověřit, že kód funguje, nebo po změně spustit testy.
allowed-tools: Bash, Read, Edit
---

# Run tests

Spusť testovací sadu a vyhodnoť výsledky:

## Změněné soubory
!`git status --short 2>/dev/null || echo "(mimo git)"`

## Postup
1. Spusť testy příkazem:
   ```
   php tests/run.php
   ```
2. Pokud všechny projdou, napiš stručné shrnutí (kolik testů, vše OK).
3. Pokud nějaký selže:
   - Přečti chybovou hlášku a najdi příčinu v **implementaci** (ne v testu — test definuje očekávané chování).
   - Oprav implementaci.
   - Spusť testy znovu a ověř, že prošly.
4. Nikdy „neopravuj" test tím, že bys ho oslabil, jen aby prošel.
