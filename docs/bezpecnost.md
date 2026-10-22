# Bezpečnost a soukromí

## Co je veřejné

Zdrojový kód, statické články, výukové podklady, zveřejněné fotografie, obecná dokumentace a veřejný výřez OpenStreetMap. Název autora, názvy výprav, data v článcích a veřejné domény jsou záměrnou součástí osobního webu. Veřejný repozitář lze stáhnout a kopírovat; jeho pozdější zneveřejnění neodstraní cizí kopie.

## Co se nesmí commitovat

Přístupy k hostingu, hesla a jejich skutečné hashe, tajné klíče, session, soukromé odkazy akcí a místností, konfigurační soubory, analytika, seznamy žáků, živé soubory tříd, provozní akce, exporty, zálohy a diagnostické výpisy hostingu. Ochranu tvoří `.gitignore` a kontrolní skripty, ale rozhodující je kontrola změn před odesláním.

`.gitignore` nepůsobí zpětně na již zapsanou historii. Pokud se přístupový údaj dostane do commitu, odstraňte jej také z historie a zneplatněte nebo vyměňte na dotčené službě. Přepsání historie samo nezneplatňuje přihlašovací údaje a nemusí odstranit kopie nebo cache vzdálené služby.

## Stávající ochrany

- HTTPS a pravidla Apache: zákaz výpisu adresářů, omezení interních souborů, chráněná data administrace a zákaz spouštění skriptů v uploadech.
- Přihlášení: hash hesla, session cookie HttpOnly a SameSite Strict, Secure při HTTPS, CSRF formulářů a omezení neúspěšných pokusů.
- Vzhled: striktní validace hexadecimálních barev a čitelnosti, veřejné CSS obsahuje jen ověřenou paletu.
- Místnosti: náhodné přístupové klíče, omezená platnost, kontrola původu, velikostní a kapacitní limity, soukromé úložiště mimo web.
- Halloween: oddělené role, kontrola původu a stavu, zámky, atomické zápisy a idempotentní operace.

Tyto vlastnosti závisejí na správném nasazení. Server, který ignoruje `.htaccess`, musí mít ekvivalentní pravidla v konfiguraci. Vestavěný PHP server není produkční náhrada Apache. PHP musí mít dostupné používané funkce včetně JSON, session, mbstring, iconv a fileinfo a právo zapisovat do určených datových adresářů.

## Limity, které je potřeba znát

Odkazy místností a obsluhy fungují jako přístupový klíč. Nejde o individuální účty účastníků ani o ochranu proti sdílení odkazu. Aplikace nemá centrální auditní identitu všech obsluhujících osob, MFA, antivirovou kontrolu souborů ani distribuovanou databázi. Lokální identifikátor účastníka není ověřená osoba.

Omezení vytváření podle IP není plnohodnotná ochrana proti distribuovanému zneužití a více uživatelů ve školní síti sdílí limit. Jednoduchá analytika a některé administrativní aktualizace mohou při souběhu ztratit část aktualizací. Pro vysokou návštěvnost nebo kritický provoz je nutné přehodnotit úložiště, monitoring a kapacitu.

## Uchovávání dat

| Data | Současné chování |
|---|---|
| Třídní místnosti | Přístup vyprší za 24 hodin; úklid souborů se spouští při založení další místnosti |
| Halloween | Přístup vyprší za 30 dnů; soubory akcí se automaticky nemažou |
| Analytika | Nejvýše 120 denních záznamů; živý přehled přibližně hodinu |
| Obsah a uploady | Zůstávají do odstranění správcem |
| Zálohy | Závisí na samostatném retenčním plánu provozovatele |

Provozovatel musí plánovat neveřejné zálohy a bezpečný úklid vypršelých dat. U Halloween se staré soubory započítávají do limitu 300 uložených akcí. Před úklidem ověřte expiraci, potřebu exportu a přesný neveřejný adresář; nemažte aktivní akce.

## Statistiky a externí služby

Klientská analytika ukládá volbu souhlasu a náhodný identifikátor do localStorage. Server vede identifikátory, cesty včetně query stringu, odkazující domény a typ zařízení; není správné automaticky považovat tato data za nevratně anonymní. Hosting může vést vlastní přístupové a chybové logy nezávisle na aplikaci.

Vložená videa, mapy, CDN a meteorologické či kamerové zdroje mohou předávat síťové údaje třetím stranám. Souhlas s vlastní analytikou sám neřídí všechny tyto požadavky. Školní provoz musí odpovídat pravidlům instituce a skutečnému nastavení služeb; tento soubor není právní posouzení.

## Nález problému

Postupujte podle [SECURITY.md](../SECURITY.md). Ve veřejném hlášení nezveřejňujte funkční klíče, osobní údaje, skutečné exporty ani návod propojený se soukromými daty. Zveřejnění zdrojů a automatický scan nejsou certifikací bezpečnosti produkčního hostingu.
