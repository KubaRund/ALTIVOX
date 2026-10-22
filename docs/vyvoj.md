# Místní vývoj a ověřování

## Požadavky

- Git a Python 3 pro stažení zdrojů, inicializaci a kontrolní skripty.
- Aktuální opravené PHP; produkční výchozí prostředí při importu používá řadu 8.5. Kód používá moderní PHP funkce, nespoléhejte na kompatibilitu se starými řadami.
- Prohlížeč s JavaScriptem; mikrofon vyžaduje oprávnění a bezpečný kontext, typicky HTTPS nebo localhost.
- Apache s povoleným zpracováním `.htaccess` pro produkční rozložení. Vestavěný PHP server tato pravidla nepoužívá.

## První spuštění

Z kořene repozitáře:

```sh
python tools/init-admin.py
php -S 127.0.0.1:8000 -t www
```

Iniciátor se interaktivně zeptá na vlastní jméno a heslo. Jméno musí mít alespoň 3 znaky, heslo alespoň 12. Neukládejte přihlašovací údaje do skriptů ani příkazové historie. Pokud PHP není v PATH:

```sh
python tools/init-admin.py --php /cesta/k/php
```

Vytvoří se lokální soukromá konfigurace a datové soubory v `www/admin/data/`; zveřejněný vzorek obsahu se použije pouze jako počáteční stav. Existující konfiguraci iniciátor odmítne přepsat. Veřejná část je na `http://127.0.0.1:8000/`, administrace na `/admin/`. Lokální účet nemá žádnou vazbu na účet skutečného hostingu.

Výuku spusťte v druhém terminálu:

```sh
php -S 127.0.0.1:8001 -t subdoms/zs-sokolov
```

Některé odkazy a prostředky směřují na skutečné domény; běžný lokální start není plně izolovaný offline režim. Nepoužívejte produkční soukromé odkazy při vývoji.

## Kontroly před změnou

```sh
python tools/check_repository.py
python tools/audit_publication.py
php tests/halloween-core.php
php tests/theme-presets.php
```

Všechny PHP soubory zkontrolujte také pomocí `php -l`. CI provádí tuto syntaktickou kontrolu nad `www`, `subdoms` a `tests` spolu s kontrolou repozitáře a testy jádra. Statická kontrola nenahrazuje prohlídku veřejné stránky ani audit oprávnění hostingu.

Integrační test Halloween API vyžaduje spuštěný místní server:

```sh
python tests/halloween-api.py http://127.0.0.1:8000/halloween/
```

Test vytváří vlastní akci a nakonec ji uzavře. Spouštějte jej v odděleném vývojovém úložišti; provozní limity a soubory akcí se na testy také vztahují.

## Ruční ověření podle typu změny

| Změna | Co vyzkoušet |
|---|---|
| Vzhled | Tmavé i světlé schéma, mobilní menu, klávesnice, dlouhé nadpisy |
| Obsah | Rozcestník, detail, obrázky, odkazy a mobilní zalomení |
| Administrace | Nepřihlášený přístup, uložení s CSRF, náhled a export |
| Místnosti | Dvě okna, účastník versus správce, uzavření a opětovné otevření |
| Halloween | Prodej, společná skupina, rezervace, vstup, odchod a výpadek spojení |
| Výuka | Ročník, téma, obrázek, video a návrat na rozcestník |

## Praktická pravidla

Upravujte společné prvky ve společném CSS/JS; při zásahu do vložených hlaviček ověřte všechny dotčené aplikace. Zachovávejte UTF-8 a relativní cesty odpovídající document rootu. Živá JSON data nejsou testovací fixture. Změna na GitHubu se na hosting sama nepřenese; postup je v [nasazení](nasazeni.md).
