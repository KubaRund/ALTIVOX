# Kontrola veřejného importu

Před zveřejněním 22. října 2026 byl připraven oddělený čistý import zdrojů. Původní soukromá pracovní historie se nepoužívá jako historie veřejného projektu.

## Rozsah kontroly

- Soubory projektu i Git objekty dosažitelné z větví a dalších lokálních referencí.
- Typické formáty přístupových tokenů, privátní klíče, hashe hesel, přístupy vložené v URL a lokální uživatelské cesty.
- Zakázané názvy konfigurací, provozní data, uploady, exporty a zálohy.
- Obsah přibaleného ZIPu včetně demonstračních podkladů.
- Metadata obrázků JPEG, PNG a WebP.
- Původ přibalených knihoven a zachování dostupných licenčních oznámení.

## Provedené úpravy

Šest JPEG obsahovalo metadata o poloze a zařízení. Byla odstraněna bez změny dekódovaného obrazu; zůstal pouze údaj orientace, pokud byl potřebný. Z mapového demonstračního XML uvnitř ZIPu bylo odstraněno 128 nepotřebných kontaktních tagů. Původní import s neočištěnými soubory zůstává soukromý; do veřejné historie se přenáší očištěný strom.

Skutečná konfigurace administrace, účty, tajné odkazy, data třídních místností a akcí, analytika, soukromé exporty a přístupy k hostingu nejsou součástí veřejného stromu. Git uchovává veřejný vzorek obsahu, nikoli kopii živé databáze administrace.

## Opakování kontroly

```sh
python tools/check_repository.py
python tools/audit_publication.py
python tools/audit_publication.py --history
```

Kontrola hlásí cestu a typ nálezu, nikoli nalezenou tajnou hodnotu. Výsledek je nutné vyhodnotit; nové formáty tajných údajů, obsah fotografií, neznámé archivy nebo právní oprávnění k obsahu nelze plně určit regulárními výrazy. Kontrola repozitáře neprokazuje bezpečnost vzdáleného hostingu a nemaže data z běžícího webu. Očištěné soubory je při aktualizaci produkce nutné nasadit samostatně.

Před každým dalším odesláním prohlédněte také změny ručně. Pokud je potřeba sdílet nález, sdílejte redigovaný popis, nikoli skutečné heslo, klíč nebo export.
