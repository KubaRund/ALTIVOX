# Administrace a obsah

## Přístup

Control Center se otevírá na `/admin/`. Instalovaný účet je uložen pouze na daném serveru. Přihlášení používá PHP session a hash hesla; změnové formuláře vyžadují CSRF token. Odhlášení ukončí přístup v dané relaci. Neexistuje veřejné registrační rozhraní pro nové správce.

Nové oddělené prostředí připravuje `tools/init-admin.py`. Webový instalační endpoint je vyřazený. Při ztrátě hesla je nutný zásah oprávněného provozovatele do neveřejné konfigurace; nezveřejňujte konfigurační soubor jako přílohu žádosti o pomoc.

## Obrazovky

| Část | Použití |
|---|---|
| Přehled | Souhrn obsahu a návštěvnosti |
| Záznamy | Seznam a úprava dynamicky spravovaných příspěvků |
| Média | Nahrávání a správa veřejných obrázků |
| Analytika | Denní přehledy, stránky, zdroje návštěv a zařízení |
| Systém | Provozní přehled a export |
| Vzhled webu | Celkové barevné schéma |

## Vytvoření a publikování záznamu

1. Otevřete záznamy a založte příspěvek odpovídajícího typu.
2. Vyplňte název, stručné shrnutí, datum, případně místo, štítky a metriky.
3. Doplňte text, obálku a fotografie. Specializované záznamy mají další pole pro značku, kategorii, roli, použití v terénu, přednosti, omezení a technické poznámky.
4. Zkontrolujte slug, odkazy a stav publikování. Slug slouží v URL; jeho změna může zneplatnit dříve sdílené odkazy.
5. Uložte a ověřte veřejný detail v samostatném okně. Nepublikované záznamy veřejná obsahová API nevydávají.

Textový renderer podporuje jednoduchou strukturu včetně nadpisů `##`, `###` a odrážek `-`. Nepředpokládejte plnou implementaci Markdownu nebo možnost vložit libovolné HTML.

Statické články jsou samostatné HTML soubory v repozitáři. Administrace není univerzální editor každé statické stránky. Záznam může odkazovat na existující statický detail přes `static_url`; změny tohoto detailu se provádějí ve zdroji a nasazují zvlášť.

## Obrázky

Nahrané obrázky jsou veřejný obsah. Před nahráním zkontrolujte práva, osoby na snímku, viditelné dokumenty, obrazovky a případná GPS/EXIF metadata. Galerie prochází fotografie v `assets/photos` i podporované obrázky v `uploads`; samotné neodkazování fotografie tedy není ochrana před zveřejněním.

Odstranění obrázku může rozbít záznamy, které na něj odkazují. Před mazáním zjistěte použití a mějte zálohu. Zdrojové fotografie a neveřejné originály uchovávejte mimo web.

## Návštěvnost a export

Analytika počítá klientské identifikátory návštěvníků, cesty, zdrojové domény a typ zařízení. Denní záznamy se omezují na posledních 120 dnů, živý přehled na přibližně hodinu. Nejde o účetnictví ani přesné nezávislé měření: blokování JavaScriptu, manipulace požadavků a souběžné zápisy mohou výsledky ovlivnit.

Export vyžaduje přihlášení a může obsahovat neveřejná provozní data. Ukládejte jej do neveřejné zálohy, nikdy do issue, veřejného repozitáře nebo veřejné složky hostingu. Git není záloha živého obsahu.
