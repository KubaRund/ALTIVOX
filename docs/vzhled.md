# Barevné schéma a společný vzhled ALTIVOXu

Nasazeno 22. října 2026.

Otevřete https://www.altivox.cz/admin/appearance.php a přihlaste se dosavadním účtem správce. V běžné administraci je také nová položka „Vzhled webu“.

1. Zvolte ALTIVOX, Oceán, Papír nebo Písek.
2. Případně upravte sedm barev přes barevný výběr nebo hexadecimální kód.
3. Zkontrolujte živý náhled a čitelnost.
4. Klikněte na „Uložit schéma pro celý web“. Změna se projeví na nově načtených stránkách. Již otevřený web obnovte.
5. „Obnovit výchozí ALTIVOX“ vrátí původní tmavý základ.

Nastavení je společné pro hlavní rubriky, články, dynamické záznamy, Tools, Učebnu, Halloween, Hospody a výukový rozcestník na zs-sokolov.altivox.cz. Významové barvy stavů, ilustrací a grafů si zachovávají svůj smysl. Učebna má nadále také místní přepínač světlého zobrazení.

Připraveno a nahráno 148 souborů včetně 134 veřejných HTML stránek. Společná navigace a patička používají stejnou strukturu, mobilní menu a zvýraznění aktivní rubriky. Samostatné pracovní obrazovky nástrojů zůstaly zachované pod společnou navigací. Opraveno také mobilní odsouvací menu administrace.

Ukládání používá stávající přihlášení, CSRF ochranu a atomický zápis do chráněného admin/data/appearance.json. Veřejný /api/theme.php poskytuje pouze ověřené barvy jako CSS; neveřejná data se neposkytují. Neplatné barvy, vložený CSS kód a kombinace s nedostatečným kontrastem se neuloží.

Ověření: všech 142 veřejných statických souborů odpovídalo balíčku přes HTTPS. Následné opravy společného CSS a JS jsou také ověřené. Ukládání, přístup bez přihlášení, CSRF, kontrast, vložení CSS a reset prošly místním testem na odděleném zkušebním účtu. Na produkci ověřeno chráněné přihlášení a veřejné načítání barev; skutečné přihlašovací údaje správce se při testech nepoužívaly. Mobilní navigace a spuštění/pozastavení odpočtu ověřeny v prohlížeči.

