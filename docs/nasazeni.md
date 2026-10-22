# Nasazení na FORPSI

Repozitář má stejné rozdělení jako hosting:

| Zdroj | Cíl hostingu |
|---|---|
| `www/` | `/www/` |
| `subdoms/zs-sokolov/` | `/subdoms/zs-sokolov/` |

Používejte FTPS s ověřením certifikátu. Přístupové údaje patří do místního správce přihlášení nebo zabezpečeného pole, nikoli do repozitáře, argumentů příkazů nebo protokolů.

1. Spusťte kontroly z README a připravte konkrétní změněné soubory.
2. Ověřte cílový účet a přesný adresář `/www`. Nevytvářejte další kopii `/www/web`.
3. Původní změněné soubory zálohujte mimo veřejný web a mimo Git.
4. Nahrajte změny a ověřte je přes FTPS i HTTPS. Při chybě obnovte změněné soubory ze zálohy.
5. Nepřepisujte `www/admin/data/`, nahrané fotografie v `www/uploads/`, `.altivox-nastroje/` ani `.altivox-halloween/`. Tyto adresáře obsahují provozní stav.

Pravidla `.htaccess` chrání soukromá data a blokují spustitelné soubory v uploadech. Kopírujte také tečkové soubory. Na prvním prázdném hostingu je nutné samostatně inicializovat administrátora; příkaz `tools/init-admin.py` je určený pro nové místní prostředí a odmítne přepsat existující konfiguraci.

Samostatně zálohujte neveřejná data hostingu. Git obsahuje zdrojový projekt a zveřejněné podklady, nikoli účty návštěvníků nebo živé akce. Automatické nasazení z GitHub Actions zde není zapnuté.

## První instalace

1. Připravte document rooty pro hlavní doménu a výukovou subdoménu. Zkontrolujte aktuální opravené PHP, HTTPS, mod_rewrite a oprávnění `.htaccess`.
2. Nahrajte zdroje do odpovídajících kořenů. Adresář projektu jako celek nesmí být veřejný: `.git`, `tools`, `tests`, `docs` a soukromá úložiště nepatří do document rootu.
3. V odděleném neveřejném prostředí inicializujte vlastní účet pomocí `tools/init-admin.py`. Bezpečně přeneste konfiguraci do chráněného datového adresáře nebo použijte oprávněný CLI přístup přímo na serveru. Konfigurace se nikdy nepřenáší přes veřejnou URL.
4. Nastavte zapisovatelnost `admin/data`, `uploads` a neveřejných adresářů místností a akcí. Nepoužívejte plošná oprávnění pro zápis všem. Bez možnosti zapisovat mimo document root nastavte vhodné neveřejné cesty přes proměnné prostředí.
5. Ověřte přihlášení, uložení testovacího záznamu, schéma, místnost a testovací akci. Před použitím s návštěvníky testovací data bezpečně ukliďte.

## Kontrola po aktualizaci

| Oblast | Očekávaný výsledek |
|---|---|
| Hlavní web a výuka | HTTPS, správná navigace, obrázky a témata |
| Administrace | Nepřihlášený uživatel nevidí soukromý obsah, správce může uložit změnu |
| Soukromá data | Přímé URL `admin/data` a konfiguračních souborů nejsou dostupné |
| Uploady | Obrázky jsou dostupné, skripty se v adresáři nespouštějí |
| Třídní nástroje | Dvě okna vidí společný stav; účastník nemá moderátorská oprávnění |
| Halloween | Prodej a průchod nepřekročí kapacitu; veřejná tabule neodhaluje klíče |
| Cache | Po obnovení se načtou nové CSS/JS a zvolená paleta |

Změnu ověřujte také při chybějícím přihlášení a na mobilním rozměru. Neodesílejte testovací hesla nebo klíče do veřejné analytiky, issue ani protokolů.

## Zálohování

Záloha má dvě nezávislé části: konkrétní commit zdrojů a neveřejný konzistentní stav. Zálohujte `admin/data` včetně konfigurace, `uploads`, třídní úložiště a halloweenské úložiště. Přístupové klíče v těchto souborech vyžadují stejnou ochranu jako hesla.

Při důležité akci zálohujte před zahájením a po skončení. Četnost a retenci běžných záloh nastavte podle přijatelné ztráty obsahu. U souborového úložiště použijte snapshot hostingu nebo krátce zastavte změnové operace; prosté kopírování právě měněných souborů nemusí dát konzistentní celek. Archiv uložte šifrovaně mimo veřejný hosting, omezte přístup a pravidelně vyzkoušejte obnovu.

## Obnova a návrat verze

Pokud selže aktualizace zdrojů, obnovte přesně změněné soubory z předchozí verze a znovu ověřte kritické cesty. Nepřepisujte přitom živá data starou kopií. Pokud nová verze mění datový formát, musí mít vlastní postup migrace a návratu ještě před nasazením; automatické migrace obecně nejsou součástí tohoto projektu.

Při obnově po ztrátě hostingu nejprve obnovte zdroje konkrétního commitu do izolovaného prostředí, potom soukromá data a média. Zkontrolujte práva a ochranná pravidla, přihlášení, obsah a stav akcí. Obnova starší fronty může vrátit již vydaná čísla nebo staré obsazení; před znovuotevřením ji musí provozně zkontrolovat obsluha. Při podezření na únik vyměňte přístupy a dotčené tajné odkazy; pouhé obnovení souborů nestačí.

## Časté potíže

- **HTTP 500:** ověřte syntaxi PHP, `.htaccess`, dostupné moduly a neveřejný chybový log. Log nezveřejňujte bez redakce.
- **Změna se neukládá:** ověřte zapisovatelnost cílového adresáře, volné místo, session a platný CSRF token.
- **Místnost nebo akce vypršela:** expirace je součást aplikace; prodlužování přímou editací dat může porušit očekávání účastníků.
- **Nelze založit Halloween:** zkontrolujte hodinový limit a počet uložených akcí. Vypršení přístupu soubory automaticky nemaže.
- **Chybí video, mapa nebo hlas:** ověřte externí službu, školní síť, oprávnění a dostupnost hlasu na daném zařízení.
- **Odlišné místní chování:** vestavěný PHP server ignoruje pravidla Apache a některé stránky používají absolutní adresy skutečného webu.
