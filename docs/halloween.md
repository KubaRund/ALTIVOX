# Halloween

[Otevřít aplikaci](https://www.altivox.cz/halloween/)

## Založení akce

Zadejte název, kapacitu sklepa (nejvýše 30 lidí) a rozestup mezi skupinami. Výchozí rozestup jsou dvě minuty; lze zvolit také provoz bez rozestupu. Průchod má plánovanou délku 15 minut. Po založení uložte soukromý odkaz nastavení a každému členu obsluhy předejte jeho vlastní odkaz.

| Role | Co dělá |
|---|---|
| Prodej | Vybere 1–6 lidí a vytvoří jednu očíslovanou skupinu |
| Vyvolávání | Vyvolá další skupinu, případně zopakuje hlášení nebo označí nedostavení |
| Sklep | Potvrdí skutečný vstup a skutečný odchod celé skupiny |
| Tabule | Veřejně ukazuje vyvolanou skupinu a následující čísla |
| Nastavení | Správce nastaví akci, rozdělí odkazy a stáhne přehled |

Návštěvníci nemají vlastní ovládání, vstupní formulář ani návštěvnické QR. Sledují tabuli. Číslo patří skupině, nikoli jednotlivému lístku. I jeden člověk je samostatná skupina. Platby a fyzický prodej se řeší mimo aplikaci.

## Prodej a počty

Vyberte počet lidí a stiskněte „Vytvořit skupinu“. Systém přidělí další číslo. Potvrzení ukazuje číslo i počet lidí; jednoduchý lístek lze vytisknout. Čekající skupinu lze zrušit v rozbalených opravách.

Počty rozlišují lidi ve frontě, vyvolané u vstupu, lidi uvnitř a volnou kapacitu. Součet před vstupem zahrnuje frontu i vyvolané. Celkový prodej je historický počet evidovaných míst, včetně později zrušených skupin; není účetnictvím plateb. Počet skupin a počet lidí jsou samostatné hodnoty.

## Vyvolání a vstup

Vyvolává se v pořadí vytvoření; skupina se nedělí. Vyvolání rezervuje místa a zobrazí číslo na tabuli. Další skupina čeká na potvrzení vstupu předchozí, na uplynutí rozestupu a na dostatečnou kapacitu.

Obsluha sklepa stiskne „Vstoupili“ až při skutečném vstupu. Tím začne patnáctiminutový odpočet a rozestup před další skupinou. Potvrzení, čas vstupu i obsazenost se propíší do ostatních otevřených pohledů. „Odešli“ skutečně uvolní místa.

Zopakování hlášení nerestartuje dobu čekání ani nerezervuje místa znovu. Nedostavení uvolní rezervaci. Návrat nedostavené skupiny ji zařadí na konec fronty. Rozestup může upravit vyvolávání, sklep nebo správce; prodej toto oprávnění nemá.

## Časové údaje

Odhad vstupu spojuje pořadí, rozestup, velikost skupin a předpokládané uvolnění kapacity po 15 minutách. Časy jsou orientační, skutečný vstup i odchod potvrzuje člověk. Při překročení 15 minut se objeví upozornění a údaj přesčas; kapacita se automaticky neuvolní. Pokud další odhad závisí na dosud nepotvrzeném odchodu skupiny po limitu, čas se nezobrazuje jako jistý.

Časy vycházejí ze serveru, takže nepřesně nastavené hodiny telefonu nemění provozní rozestup. Při ztrátě spojení se změnové operace zablokují.

## Notebook a mobil

Na notebooku aplikace využívá celou šířku okna; přehled a hlavní ovládání se zobrazují vedle sebe. Dlouhý seznam prodeje má vlastní posun, takže nezakrývá tlačítko vytvoření skupiny. Na mobilu se panely skládají pod sebe, statistiky do menší mřížky a na nejmenších displejích se výběr 1–6 lidí rozdělí na dva řádky. Rozložení bylo ověřeno v prohlížeči při šířkách 320 a 1 366 CSS pixelů.

## Výpadek a nejistý výsledek

Pokud se odpověď na operaci ztratí, neprodávejte skupinu znovu novým požadavkem. Po obnovení spojení použijte „Ověřit“. Aplikace zopakuje původní identifikátor operace; záznam se nevytvoří dvakrát. Uchovává se posledních 1 000 identifikátorů, nikoli neomezená historie deduplikace.

Aktivní stránka kontroluje změny přibližně každé čtyři sekundy, skrytá méně často. Při chybách se interval prodlužuje. Nezměněný stav používá krátkou odpověď bez opakovaného výpočtu celého přehledu. Zámek má dvousekundový limit, data akce limit 8 MiB. Výpočet průběžně udržuje jen potřebné kapacitní sloty; dlouhá fronta nezvětšuje seznam slotů na celý počet prodaných lidí.

Rozhraní respektuje bezpečnostní pravidla hostingu `script-src 'self'` a `style-src 'self'`: JavaScript i vzhled jsou v samostatných souborech, ukazatel obsazenosti používá nativní `progress`. Kontrola repozitáře odmítá návrat vložených stylů nebo skriptů do tohoto rozhraní.

## Před otevřením návštěvníkům

Na skutečné síti vyzkoušejte prodej, vyvolání, vstup, odchod a synchronizaci dvou zařízení. Na tabuli zvlášť zapněte zvuk; český hlas závisí na prohlížeči a zařízení. Vyzkoušejte celou obrazovku, tisk a mobilní ovládání. Obslužné odkazy jsou přístupové klíče a nepatří na veřejnou tabuli.

Přístup platí 30 dnů. Uzavřená akce zůstává jen ke čtení. Fyzické soubory akcí se automaticky nemažou; provozovatel řeší neveřejné zálohy a úklid. Původní datové akce se automaticky nepřevádějí na nový způsob číslování; pro nové skupiny založte novou akci.

## Ověření

`tests/halloween-groups.php` ověřuje číslování, kapacitu, rozestup, role, nedvojitý vstup, odhady a přesčas. Součástí je výpočet akce s 10 000 skupinami. `tests/halloween-groups-api.py` ověřuje opakované požadavky, souběžné prodeje a soupeřící vyvolávání; při zadání dvou místních adres lze ověřit zámek mezi dvěma nezávislými PHP procesy se společným úložištěm. Testy nejsou zárukou dostupnosti sdíleného hostingu.
