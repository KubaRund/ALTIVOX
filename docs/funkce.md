# Funkce a použití

## Osobní web

FIELD obsahuje výpravy a terénní zkušenosti, GEAR vybavení, SIGNAL techniku a LAB experimenty a vlastní konstrukce. Další části zahrnují zápisky, cíle, galerii, počasí a webkamery. Obsah kombinuje samostatné statické články a dynamické záznamy administrace. Tlačítka a navigace propojují tyto části do jednoho webu.

## Učebna — `/nastroje/`

| Nástroj | K čemu slouží |
|---|---|
| Třídní služby | Rozdělení jmen ke službám a tisk rozpisu do PDF |
| Hlasování | Otázka s možnostmi a sdílenou místností |
| Odpočet | Odměření času aktivity |
| Stopky | Měření uplynulého času |
| Hlukoměr | Orientační odezva na mikrofon, po udělení oprávnění |
| Soubory | Dočasné sdílení podporovaných souborů v místnosti |
| Nápady | Sběr příspěvků a hlasování pro nápady |
| Náhodný výběr | Losování ze zadaného seznamu |
| YouTube | Práce s vloženým videem |
| Skupiny | Rozdělení seznamu do skupin |
| Poznámky | Pracovní text pro výuku |
| Velký text | Výrazná zpráva pro projekci |
| QR kód | Vytvoření QR ze zadaného obsahu |
| Pracovní listy | Příprava výukových podkladů |
| Společná tabule | Sdílené kreslení a společný text |

U třídních služeb vložte seznam jmen, doplňte třídu a školní rok, zkontrolujte rozdělení a otevřete tisk. Uložení PDF probíhá přes tiskové rozhraní prohlížeče. V náhledu ověřte formát stránky, měřítko a úplnost jmen. Výsledek obsahuje osobní údaje, pokud zadáte skutečná jména; sdílejte jej jen v odpovídajícím školním kontextu.

Hlasování, nápady, soubory a společná tabule využívají serverové místnosti. Účastnický odkaz je přístupovým oprávněním; správcovský odkaz navíc umožňuje moderaci. Odkazy nevkládejte do veřejných issue. Místnost má platnost 24 hodin. Uzavření správcem pozastavuje změny a lze je přepnout zpět; není totožné s okamžitým smazáním dat.

Sdílení souborů má aplikační limit 10 MiB na soubor, 15 souborů a 30 MiB celkem na místnost. Hosting může mít menší limit. Povolená přípona nezaručuje bezpečný obsah; služba neprovádí antivirovou analýzu. Po vypršení již místnost není přístupná, fyzický úklid probíhá při vytváření dalších místností.

Mikrofon je potřeba povolit v prohlížeči. Hlukoměr není kalibrovaný měřicí přístroj. Časovače a zvuk závisejí na zařízení, aktivním okně a pravidlech prohlížeče; před projekcí je vyzkoušejte.

## Výukový web

`zs-sokolov.altivox.cz` slouží jako rozcestník fyziky, informatiky a výtvarné výchovy pro základní školu. Učivo je členěné podle předmětů a ročníků; témata obsahují vysvětlení, ilustrace a tam, kde jsou vložená, videa. Konkrétní naplnění jednotlivých ročníků ověřujte ve zdrojových datech výuky, nikoli podle předpokladu, že každé téma má video.

Při doplňování lekce zachovejte věcnou správnost, srozumitelnost pro ročník, označení jednotek a zdroje. YouTube video může být odstraněné, regionálně omezené nebo nedostupné ve školní síti; hlavní vysvětlení má zůstat čitelné i bez něj. Učebna a výuková subdoména jsou dvě propojené, ale samostatné části projektu.

## Mapa podniků

`/hospody/` zobrazuje uložený celostátní výřez OpenStreetMap pro hospody, bary a pivní zahrádky. Lze hledat podle názvu nebo OSM ID, filtrovat kategorii a přibližovat výsledky. Jeden skutečný podnik může mít více OSM objektů. Data nejsou zárukou aktuálního provozu, otevírací doby nebo úplnosti všech skutečných podniků.

Součástí jsou JSON data, metodika, ukázková pražská trasa a samostatný Python projekt pro práci s trasou. Celostátní body nejsou vydávané za vypočítanou automobilovou trasu. U budov a relací zobrazený střed nemusí odpovídat vchodu. Zachovávejte připsání OpenStreetMap a podmínky ODbL.

## Halloween a terénní nástroje

Halloween je samostatná provozní aplikace pro číslované vstupenky, skupiny a řízení kapacity. Podrobný postup je v [návodu](halloween.md). Nezpracovává platby. Terénní kalkulačky a generátor reliéfu jsou dostupné v `/tools/` a souvisejících stránkách; výsledky závisejí na zadaných datech a nenahrazují rozhodnutí podle skutečných podmínek.
