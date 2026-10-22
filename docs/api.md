# API: rozhraní a kontrakty

Toto je přehled současného interního rozhraní webu, nikoli příslib verzované služby pro třetí strany. Zdrojové soubory jsou rozhodující pro přesná pole a validační pravidla. JSON používá UTF-8. Soukromé klíče v následujících příkladech nejsou uvedené; skutečné klíče nevkládejte do dokumentace ani hlášení chyb.

## Veřejný obsah

| Metoda a cesta | Parametry / odpověď |
|---|---|
| `GET /api/content.php` | `type` volitelný filtr, `limit` 1–50, výchozí 12; `{ok, records}` |
| `GET /api/record.php` | `type`, `slug`; `{ok, record}`, nenalezený či neveřejný záznam 404 |
| `GET /api/gallery.php` | `{ok, count, images}` s URL, kategorií, popiskem a časem změny |
| `GET /api/theme.php` | Ověřené veřejné CSS proměnné, nikoli JSON |
| `POST /api/track.php` | JSON s `vid`, `path`, `referrer`; jednoduché `{ok}` |

Seznam obsahu zahrnuje pouze publikované záznamy. Nejprve se třídí sestupně podle data a potom omezuje limitem. Typy používané administrací zahrnují `field`, `gear`, `objective`, `signal`, `lab`, `make`. Detail obsahuje kromě titulku, textu a data také štítky, fotografie, související záznamy a oborová pole. Seznam a detail nemají totožný výběr polí.

```sh
curl 'http://127.0.0.1:8000/api/content.php?limit=5'
curl 'http://127.0.0.1:8000/api/gallery.php'
```

Obsah a galerie mají veřejnou cache 60 sekund. Detail a barevné CSS jsou bez ukládání do cache. Galerie publikuje podporované obrázky v `assets/photos` a `uploads`; nevydává soukromé třídní soubory.

Tracker pracuje až po místní instalaci. Vyžaduje identifikátor `vid` o délce 12–100 znaků po filtraci, omezuje cestu na 500 znaků a ukládá doménu odkazující stránky a kategorii zařízení. Klient v `js/analytics.js` odesílá až po souhlasu uloženém v localStorage. Cesta zahrnuje i query string: do veřejných URL proto nevkládejte tajné hodnoty. Serverový endpoint není autentizovaná statistická služba a jeho čísla nejsou nezávisle ověřená.

## Třídní místnosti — `/nastroje/api.php`

Používejte POST JSON; `upload` používá multipart/form-data s polem `file`. Rozhraní odmítá cross-site požadavky. JSON limit je 2 MiB, celkový deklarovaný požadavek nejvýše 12 MiB. Úspěšné `read` vrací přímo veřejný stav místnosti, nikoli stejnou obálku jako obsahové API.

| Akce | Základní vstupy / význam |
|---|---|
| `create` | `type`: `poll`, `ideas`, `files`, `collab`; `title`; pro poll `options` |
| `read` | `id`, `key`; očištěný stav, počty hlasů a příspěvky |
| `download` | `id`, `key`, `file`; binární příloha |
| `close` | `id`, `key`, `owner`; přepnutí uzavření místnosti |
| `vote` | `client`, celočíselný index `vote`; nastavení hlasu účastníka |
| `idea` / `like` | `text` / ID v `idea`; vytvoření nápadu / přepnutí hlasu |
| `removeIdea` | `owner`, ID v `idea`; moderace |
| `upload` / `removeFile` | nahrání / odebrání ID v `file` (odebrání vyžaduje owner) |
| `stroke` | `stroke` s `points`, `color`, `width`, volitelně `text` |
| `undo` / `clear` | poslední vlastní tah / vymazání správcem |
| `doc` | `text`, `version`; změna sdíleného dokumentu s kontrolou verze |

Všechny akce po vytvoření vyžadují `id` a účastnický `key`. Změny kromě `close` vyžadují také `client` jako 32 hexadecimálních znaků. Oprávnění správce se předává polem `owner`. `create` vrací nově vytvořené identifikátory a tajné klíče; nesmějí se logovat do veřejných výstupů.

Místnost žije 24 hodin. Hlasování má 2–10 unikátních možností a nejvýše 300 hlasujících klientských identifikátorů. Nápadů může být 150, text je omezený na 1 200 bajtů. Sdílený dokument má limit 24 000 bajtů; zastaralá verze vrací 409. Tabule má nejvýše 1 200 tahů, 1–1 500 bodů na tah, souřadnice 0–1600 × 0–1000, barvu `#RRGGBB`, šířku 1–50. Stav místnosti je omezen na 8 MiB.

U souborů je dovoleno nejvýše 15 položek po 10 MiB, celkem 30 MiB. Povolené přípony: pdf, txt, csv, docx, xlsx, pptx, odt, ods, odp, png, jpg, jpeg, webp, gif, zip, mp3, mp4. Přípony neznamenají kontrolu neškodnosti. Stažení vyžaduje přístup k místnosti a vrací attachment s generickým binárním MIME typem.

## Halloween — `/halloween/api.php`

POST JSON, nejvýše 32 KiB, hlavičky no-store a nosniff. `create` přijímá `name`, `capacity` (1–100), `minutes` (0,5–60), `start` (1–999999) a vrací `{state}` správce. Akce platí 30 dnů. Provozní strop je 300 uložených akcí a 12 vytvoření z jedné IP za hodinu.

`read` přijímá `id`, volitelně obslužný `key` a návštěvnické `number`. Bez platného klíče poskytuje veřejný přehled; veřejný výstup neobsahuje soukromé role. Ostatní akce vyžadují oprávnění příslušné role a unikátní `request` o délce 16–80 znaků z písmen, číslic a pomlček.

| Akce | Účel / další pole |
|---|---|
| `sell` | `count`, `together`; vydání čísel a vytvoření skupin |
| `merge` | `numbers`; spojení celých čekajících skupin |
| `next` | vyvolání další skupiny FIFO s rezervací míst |
| `repeat` | `group`; opakování vyvolání |
| `enter`, `exit` | `group`; potvrzení vstupu a odchodu |
| `missed`, `return` | `group`; nedostavení / návrat na konec fronty |
| `cancel` | `group`; zrušení čekající skupiny |
| `settings` | `name`, `capacity`, `minutes`; nastavení správcem |
| `pause` | přepnutí pozastavení vyvolávání |
| `close` | uzavření akce správcem |

Odpověď na čtení nebo změnu je `{state, result}`. Chyba má `{error}` a HTTP kód; typicky 400 neplatný vstup, 403 nepovolený přístup, 404 neznámá akce, 409 konflikt stavu, 410 vypršení, 413 velikost, 429 limit a 503 chyba úložiště. Nepředpokládejte, že všechny moduly používají totožný formát chyb.

Po ztrátě odpovědi opakujte **stejný payload se stejným `request`**. Opětovné použití identifikátoru pro jinou operaci vrací 409. Uchovává se posledních 1 000 požadavků; není to neomezená trvalá deduplikační databáze. Oprávnění jednotlivých rolí určuje `hw_apply` v `core.php`; správce má plný přístup, pokladna vydává čísla, vyvolávání obsluhuje frontu a sklep eviduje průchod.

## Administrace

Administrace používá formuláře, session a CSRF; neposkytuje obecné API pro vzdálené přihlášení nebo správu obsahu. Neobcházejte tuto ochranu přímou úpravou živých JSON souborů během provozu. Při automatizaci respektujte zámky, validace a neveřejnost konfigurace.
