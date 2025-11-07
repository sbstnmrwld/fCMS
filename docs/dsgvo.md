# DSGVO-Konformität

fCMS ist von Grund auf DSGVO-konform gestaltet. Diese Seite erklärt, wie fCMS die Datenschutz-Grundverordnung einhält.

## Frontend (Website)

### ✅ Vollständig Cookie-frei

Das Frontend verwendet:
- ❌ **Keine Cookies**
- ❌ **Keine Tracking-Pixel**
- ❌ **Keine Analytics**
- ❌ **Keine externe Ressourcen** (CDN, Google Fonts, etc.)

**Ergebnis**: Kein Cookie-Banner erforderlich!

### ✅ Keine Datenerhebung

- ❌ Keine IP-Adressen werden gespeichert
- ❌ Keine Nutzeridentifikation
- ❌ Keine Verhaltensanalyse
- ❌ Keine Tracking-Scripts

### ✅ Lokale Ressourcen

Alle Assets (CSS, JS, Fonts) werden lokal ausgeliefert:
- Bootstrap: lokal
- Icons: lokal
- Fonts: System-Fonts oder lokal

## Admin-Bereich

### ⚠️ Technisch notwendige Cookies

Der Admin-Bereich verwendet **einen** Session-Cookie:

**Cookie-Name**: `fcms_session`
**Zweck**: Admin-Login (technisch notwendig)
**Attribute**:
- `httponly` - Nicht per JavaScript lesbar
- `secure` - Nur über HTTPS
- `samesite=Strict` - Schutz vor CSRF

**Rechtliche Einordnung**: Technisch notwendig, keine Einwilligung erforderlich.

**Optional**: Minimaler Hinweis im Login:
> "Für die Nutzung des Admin-Bereichs wird ein technisch notwendiger Session-Cookie gesetzt."

## Logs

### ✅ Datenschutzkonforme Protokollierung

- ✅ Nur technische Fehler
- ❌ Keine IP-Adressen
- ❌ Keine personenbezogenen Daten
- ✅ Automatische Löschung nach 30 Tagen

**Log-Beispiel**:
```
[2024-11-07 18:20:00] ERROR: Seite nicht gefunden: /non-existent
[2024-11-07 18:20:15] WARNING: Block-Renderer nicht gefunden: custom-block
```

## Formulare (FormBuilder-Modul)

### Formular-Einsendungen

Falls Sie das FormBuilder-Modul verwenden:

**Gespeicherte Daten**:
- Formular-Daten (Name, E-Mail, Nachricht, etc.)
- Zeitstempel
- Optional: IP-Adresse (deaktivierbar)

**Empfehlungen**:
1. ✅ Explizite Einwilligung einholen (Checkbox)
2. ✅ Datenschutzhinweis verlinken
3. ✅ Zweck klar kommunizieren
4. ✅ Daten zeitnah löschen

**Beispiel-Checkbox**:
```
☐ Ich stimme der Verarbeitung meiner Daten gemäß der
  Datenschutzerklärung zu. *
```

### Formular-Konfiguration

In der Formular-Konfiguration:

```php
'forms' => [
    'store_ip' => false,           // IP-Adresse NICHT speichern
    'retention_days' => 30,         // Nach 30 Tagen automatisch löschen
    'require_consent' => true,      // Einwilligung erforderlich
],
```

## Empfohlene Seiten

### Datenschutzerklärung

Erstellen Sie eine Datenschutz-Seite mit:

1. **Verantwortlicher**
   - Name und Kontaktdaten

2. **Datenerhebung**
   - "Diese Website erhebt keine personenbezogenen Daten im Frontend"
   - Falls Formulare: Beschreiben Sie welche Daten, Zweck, Rechtsgrundlage

3. **Cookies**
   - "Im Admin-Bereich wird ein technisch notwendiger Session-Cookie gesetzt"

4. **Server-Logs**
   - "Technische Logs werden 30 Tage gespeichert und enthalten keine IP-Adressen"

5. **Betroffenenrechte**
   - Auskunft, Berichtigung, Löschung, etc.

**Template**: Siehe `docs/templates/datenschutz-vorlage.md`

### Impressum

Erstellen Sie eine Impressums-Seite mit Pflichtangaben nach TMG.

**Erforderlich für**:
- Vereine
- Unternehmen
- Gewerbliche Websites

## SSL/TLS

### HTTPS aktivieren

✅ **Unbedingt empfohlen!**

**Option 1: Let's Encrypt** (kostenlos)
```bash
# Mit Certbot
sudo certbot --apache -d ihre-domain.de
```

**Option 2: Provider**
- Viele Provider bieten kostenloses SSL

**In config.php**:
```php
'security' => [
    'force_https' => true,
],
```

## Externe Dienste

### ⚠️ Vermeiden Sie

Folgende Dienste können DSGVO-Probleme verursachen:

- ❌ Google Analytics
- ❌ Google Fonts (CDN)
- ❌ Facebook Pixel
- ❌ YouTube Embeds (ohne Privacy-Mode)
- ❌ Jegliche US-Server (Schrems II)

### ✅ Verwenden Sie

- ✅ Lokale Assets (fCMS macht das automatisch)
- ✅ Selbst-gehostete Analytics (z.B. Matomo mit IP-Anonymisierung)
- ✅ Privacy-freundliche Alternativen

## Checkliste DSGVO-Konformität

### Website

- [ ] Keine externe CDNs
- [ ] Keine Tracking-Scripts
- [ ] Datenschutzerklärung erstellt
- [ ] Impressum erstellt
- [ ] HTTPS aktiviert

### Formulare (falls verwendet)

- [ ] Einwilligungs-Checkbox
- [ ] Datenschutzhinweis verlinkt
- [ ] Zweck klar kommuniziert
- [ ] IP-Speicherung deaktiviert
- [ ] Automatische Löschung aktiviert

### Server

- [ ] Logs ohne IP-Adressen
- [ ] Automatische Log-Rotation
- [ ] Sichere Session-Konfiguration

## Rechtlicher Hinweis

⚠️ **Disclaimer**: Diese Dokumentation ist keine Rechtsberatung. Konsultieren Sie im Zweifel einen Datenschutzbeauftragten oder Anwalt.

fCMS bietet technische Voraussetzungen für DSGVO-Konformität, aber Sie sind als Betreiber verantwortlich für:
- Korrekte Datenschutzerklärung
- Rechtmäßige Datenverarbeitung
- Einhaltung von Betroffenenrechten

## Weiterführende Links

- [DSGVO-Gesetzestext](https://dsgvo-gesetz.de/)
- [Datenschutz.org](https://www.datenschutz.org/)
- [LfDI Baden-Württemberg](https://www.baden-wuerttemberg.datenschutz.de/)
