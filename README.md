# ALTIVOX

[![Project checks](https://github.com/KubaRund/ALTIVOX/actions/workflows/check.yml/badge.svg)](https://github.com/KubaRund/ALTIVOX/actions/workflows/check.yml)

Osobní web Jakuba Runda propojující výpravy, vybavení, techniku, experimenty, výuku a vlastní nástroje. Součástí je také administrace obsahu, společné barevné schéma, dočasné třídní místnosti a halloweenský vyvolávací systém s řízením kapacity.

Projekt běží jako HTML, CSS a JavaScript doplněný PHP a souborovým JSON úložištěm. Nepotřebuje databázi ani sestavení přes Node. Repozitář obsahuje veřejné zdroje a dokumentaci; živá soukromá data a přístupy k hostingu jsou oddělené.

- Hlavní web: [www.altivox.cz](https://www.altivox.cz/)
- Výukový web: [zs-sokolov.altivox.cz](https://zs-sokolov.altivox.cz/)
- Nastavení vzhledu: [Administrace → Vzhled webu](https://www.altivox.cz/admin/appearance.php)

Repozitář zachycuje nasazenou verzi z 22. října 2026, včetně bezpečnostních oprav, společné navigace a správy barevných schémat.

## Co ALTIVOX obsahuje

| Část | Co nabízí |
|---|---|
| FIELD / GEAR / SIGNAL / LAB | Výpravy, zkušenosti s vybavením, technika a experimenty, doplněné fotografiemi a ilustracemi |
| Učebna | 15 nástrojů: třídní služby s tiskem do PDF, hlasování, časovače, skupiny, QR, sdílení souborů a společná tabule |
| Výukový web | Témata fyziky, informatiky a výtvarné výchovy pro základní školu, vysvětlení, obrázky a vložená videa |
| Halloween | Prodej skupin po 1–6 lidech, FIFO fronta, rozestupy, evidence průchodu, kapacita 30 lidí a veřejná tabule |
| Hospody | Celostátní výřez OSM pro hospody, bary a pivní zahrádky, filtry a ukázka práce s trasou |
| Control Center | Publikování záznamů, média, přehled návštěvnosti, export a čtyři barevné předvolby s vlastní paletou |

## Dokumentace

Úplný rozcestník je v [docs/README.md](docs/README.md).

- **Používání:** [funkce a nástroje](docs/funkce.md), [administrace](docs/administrace.md), [vzhled](docs/vzhled.md), [Halloween](docs/halloween.md).
- **Vývoj a provoz:** [architektura](docs/architektura.md), [místní vývoj](docs/vyvoj.md), [API](docs/api.md), [nasazení a obnova](docs/nasazeni.md).
- **Veřejný projekt:** [bezpečnost a data](docs/bezpecnost.md), [licence a původ podkladů](docs/licence-a-zdroje.md), [kontrola zveřejnění](docs/zverejneni.md), [přispívání](CONTRIBUTING.md).

## Struktura

```text
www/                         hlavní web; kořen veřejného hostingu
  admin/                     Control Center a správa vzhledu
  api/                       veřejná API a dynamické CSS schématu
  assets/                    obrázky, fotografie, ikony a ilustrace
  css/, js/                  společný vzhled a ovládání
  expeditions/, gear/         výpravy a vybavení
  tech/, lab/, journal/       technika, měření a zápisky
  tools/                     terénní kalkulačky a generátor reliéfu
  nastroje/                  15 nástrojů pro výuku včetně třídních služeb
  halloween/                 vyvolávací systém, vstupenky a kapacita
  hospody/                   mapa podniků a podklady OpenStreetMap
  meteo/, gallery/            počasí, webkamery a fotografie
subdoms/zs-sokolov/           aktivní výuková aplikace a učivo
config-examples/             pouze veřejné podklady pro první místní instalaci
tools/                       místní inicializace a kontrola repozitáře
tests/                       funkční testy jádra a API
docs/                        návody k provozu a nasazení
```

## Spuštění pro vývoj

Potřebujete aktuální opravené PHP (na produkci je ověřené PHP 8.5.12) a Python 3.

```sh
git clone https://github.com/KubaRund/ALTIVOX.git
cd ALTIVOX
python tools/init-admin.py
php -S 127.0.0.1:8000 -t www
```

Web poté otevřete na `http://127.0.0.1:8000/`. Iniciátor se zeptá na vlastní místní uživatelské jméno a heslo. Existující účet nikdy nepřepisuje. Heslo se neukládá do Gitu; na disk se zapisuje pouze jeho hash do chráněné konfigurace. Pokud PHP není v PATH, použijte `python tools/init-admin.py --php /cesta/k/php`.

Výukovou aplikaci lze spustit zvlášť:

```sh
php -S 127.0.0.1:8001 -t subdoms/zs-sokolov
```

Výukový web používá společné barvy a navigaci z hlavního Altivoxu. Na místním PHP serveru se pravidla Apache z `.htaccess` nevykonávají; tento server používejte pouze pro vývoj na localhostu.

## Kontroly

```sh
python tools/check_repository.py
python tools/audit_publication.py
python tools/audit_publication.py --history
php tests/halloween-core.php
php tests/halloween-groups.php
php tests/theme-presets.php
```

Pro integrační test fronty spusťte místní PHP server a potom:

```sh
python tests/halloween-api.py http://127.0.0.1:8000/halloween/
```

Test vytvoří vlastní testovací akci a na konci ji uzavře. Nepoužívejte adresu skutečné návštěvnické akce. GitHub Actions kontroluje PHP, strukturu repozitáře a testy jádra; web se z GitHubu automaticky nenahrává na hosting.

## Nasazení

Adresář `www/` odpovídá `/www` na FORPSI. `subdoms/zs-sokolov/` odpovídá stejné subdoméně na hostingu. Podrobnosti a pravidla zachování dat jsou v [návodu k nasazení](docs/nasazeni.md).

Při běžné aktualizaci nepřepisujte serverové účty, provozní JSON data ani knihovnu nahraných souborů. Zdrojový kód v Gitu není náhradou samostatné neveřejné zálohy provozních dat.

## Data a licence

Součástí jsou statické podklady, již zveřejněné texty a fotografie. Přístupy k hostingu, skutečná administrátorská konfigurace, analytika, obsah třídních místností a halloweenské provozní akce součástí nejsou.

Zdrojová data mapy podniků podléhají [OpenStreetMap ODbL](https://www.openstreetmap.org/copyright). Licenční oznámení použitých knihoven zůstávají u jejich souborů. Texty, fotografie a vlastní aplikace nejsou tímto repozitářem automaticky poskytované pod otevřenou licencí.

Před veřejným importem byly odstraněny GPS a údaje o zařízení ze šesti fotografií a nepotřebné kontaktní tagy z mapové ukázky. Veřejná historie začíná očištěným importem. Automatické kontroly doplňují ruční posouzení; nejsou zárukou úplné bezpečnosti nebo právního vypořádání každého podkladu. Podrobnosti a provozní omezení jsou v dokumentaci výše.
