# DSGVO-Konformität von fCMS

## Übersicht

fCMS wurde von Grund auf mit Fokus auf Datenschutz und DSGVO-Konformität entwickelt. Dieses Dokument erläutert die datenschutzrelevanten Aspekte des Systems.

## Keine personenbezogenen Daten im Frontend

### Cookies

**Status**: ✅ Keine Cookies im Frontend

- Das Frontend verwendet keinerlei Cookies
- Keine Tracking-Cookies
- Keine Analytics-Cookies
- Keine Marketing-Cookies

**Konsequenz**: Kein Cookie-Banner erforderlich für das Frontend

### Session-Technologie

**Status**: ✅ Nur im Admin-Bereich

- Sessions werden ausschließlich für den Admin-Bereich verwendet
- Sessions sind technisch notwendig für die Authentifizierung
- Session-Cookie-Konfiguration:
  - `httponly`: true (JavaScript-Zugriff verhindert)
  - `secure`: true (nur über HTTPS bei aktiviertem HTTPS)
  - `samesite`: Strict (CSRF-Schutz)

**Rechtliche Einordnung**: Technisch notwendig, keine Einwilligung erforderlich

### Externe Ressourcen

**Status**: ✅ Keine externen Ressourcen

- Kein Laden von CDN-Inhalten (Bootstrap, jQuery, etc.)
- Keine Google Fonts oder externe Schriftarten
- Keine Einbindung von Social-Media-Plugins
- Keine Analytics-Dienste (Google Analytics, Matomo, etc.)

**Konsequenz**: Keine Datenübermittlung an Dritte

## Datenverarbeitung

### IP-Adressen

**Status**: ✅ Werden NICHT gespeichert

- Keine Speicherung von IP-Adressen in Logs
- Keine IP-basierte Zugriffskontrolle
- Login-Versuche werden ohne IP-Adresse protokolliert

### Logs

**Status**: ✅ Nur technische Fehler

- Log-Dateien enthalten ausschließlich technische Fehlerinformationen
- Keine personenbezogenen Daten in Logs
- Automatische Löschung nach 30 Tagen (konfigurierbar)
- Logs sind nur für Administratoren zugänglich

### Content-Speicherung

**Status**: ✅ Nur lokal, keine Cloud

- Alle Inhalte werden als JSON-Dateien lokal gespeichert
- Keine Synchronisation mit externen Services
- Kein automatisches Backup in Cloud-Dienste
- Volle Kontrolle über Daten

## Admin-Bereich

### Authentifizierung

- Sichere Session-basierte Authentifizierung
- Passwort-Hashing mit bcrypt (password_hash)
- CSRF-Token-Schutz für alle Formulare
- Login-Throttling gegen Brute-Force-Angriffe

### Session-Cookie-Hinweis

Optional kann ein Hinweis im Login-Bereich angezeigt werden:

```
"Diese Anwendung verwendet technisch notwendige Session-Cookies für die Authentifizierung."
```

Konfigurierbar in `config/config.php`:
```php
'privacy' => [
    'admin_session_notice' => true,
],
```

## Datenschutzerklärung

### Pflichtangaben für Ihre Website

Erstellen Sie eine Datenschutzerklärung mit folgenden Punkten:

#### 1. Verantwortlicher

```
Name und Kontaktdaten des Verantwortlichen gemäß Art. 13 DSGVO
```

#### 2. Server-Logs

```
Beim Besuch unserer Website werden durch den Webserver automatisch
technische Informationen protokolliert (Server-Logs). Dies liegt in
der Verantwortung Ihres Hosting-Anbieters, nicht von fCMS.

Typische Server-Log-Einträge:
- Besuchte URL
- Datum und Uhrzeit
- Übertragene Datenmenge
- Browser-Typ und -Version
- Betriebssystem

Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse
an der technischen Funktionsfähigkeit der Website)
```

#### 3. Admin-Bereich

```
Unser Admin-Bereich verwendet technisch notwendige Session-Cookies
zur Authentifizierung. Diese Cookies enthalten keine personenbezogenen
Daten und werden nach Beendigung der Sitzung gelöscht.

Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)
```

#### 4. Keine Tracking-Technologien

```
Unsere Website verwendet keine Tracking-Technologien, keine Cookies
im öffentlichen Bereich und keine Analytics-Dienste. Es werden keine
Nutzerdaten erhoben oder analysiert.
```

#### 5. Kontaktformular (falls implementiert)

```
Bei Nutzung des Kontaktformulars werden Ihre Angaben (Name, E-Mail,
Nachricht) lokal auf unserem Server gespeichert. Eine Weitergabe an
Dritte erfolgt nicht.

Rechtsgrundlage: Art. 6 Abs. 1 lit. a DSGVO (Einwilligung) oder
Art. 6 Abs. 1 lit. b DSGVO (Vertragsanbahnung)

Speicherdauer: [Ihre Speicherdauer angeben]
```

## Empfohlene Maßnahmen

### Technisch

1. ✅ **SSL/TLS verwenden**
   - HTTPS aktivieren (z.B. Let's Encrypt)
   - `session.cookie_secure` auf `true` setzen

2. ✅ **Regelmäßige Updates**
   - fCMS aktuell halten
   - PHP-Version aktuell halten
   - Server-Software aktuell halten

3. ✅ **Backup-Strategie**
   - Regelmäßige Backups des `content/`-Verzeichnisses
   - Backups verschlüsselt aufbewahren

4. ✅ **Zugriffskontrolle**
   - Starke Admin-Passwörter verwenden
   - Passwörter regelmäßig ändern

### Organisatorisch

1. ✅ **Datenschutzerklärung**
   - Auf jeder Seite verlinken (Footer)
   - Regelmäßig aktualisieren

2. ✅ **Impressum**
   - Pflichtangaben nach § 5 TMG
   - Auf jeder Seite verlinken (Footer)

3. ✅ **Auftragsverarbeitung**
   - AV-Vertrag mit Hosting-Provider abschließen
   - Server in der EU bevorzugen

4. ✅ **Betroffenenrechte**
   - Prozesse für Auskunfts-, Lösch-, Berichtigungsanfragen
   - Kontakt-E-Mail in Datenschutzerklärung

## Hosting-Empfehlungen

Wählen Sie einen DSGVO-konformen Hosting-Anbieter:

- Server-Standort in der EU
- AV-Vertrag (Auftragsverarbeitungsvertrag) verfügbar
- Keine Weitergabe von Daten an Dritte
- SSL/TLS-Zertifikate verfügbar (Let's Encrypt)

## Checkliste DSGVO-Konformität

### Technische Maßnahmen

- [ ] HTTPS aktiviert
- [ ] Sichere Session-Konfiguration
- [ ] Keine externen Ressourcen (CDN, Fonts, etc.)
- [ ] Keine Tracking-Tools
- [ ] Logs ohne personenbezogene Daten
- [ ] Automatische Log-Löschung konfiguriert

### Rechtliche Maßnahmen

- [ ] Datenschutzerklärung erstellt und verlinkt
- [ ] Impressum erstellt und verlinkt
- [ ] AV-Vertrag mit Hosting-Provider
- [ ] Prozesse für Betroffenenrechte definiert
- [ ] Verzeichnis von Verarbeitungstätigkeiten (bei Bedarf)

### Organisatorische Maßnahmen

- [ ] Zuständigkeit für Datenschutz festgelegt
- [ ] Regelmäßige Prüfung der Datenschutzmaßnahmen
- [ ] Schulung der Admin-Nutzer
- [ ] Backup-Strategie implementiert

## Rechtlicher Hinweis

**Disclaimer**: Dieses Dokument bietet allgemeine Informationen zur DSGVO-Konformität von fCMS. Es stellt keine Rechtsberatung dar. Für spezifische rechtliche Fragen konsultieren Sie bitte einen auf Datenschutzrecht spezialisierten Anwalt.

Die DSGVO-Konformität Ihrer Website hängt nicht nur von fCMS ab, sondern auch von:
- Ihrem Hosting-Provider
- Ihren individuellen Inhalten
- Eventuell von Ihnen hinzugefügten Plugins oder Skripten
- Ihrer organisatorischen Umsetzung

## Unterstützung

Bei Fragen zur DSGVO-Konformität von fCMS:
- GitHub Issues: [Issue erstellen](https://github.com/IhrRepo/fCMS/issues)
- Dokumentation: Siehe README.md

---

Letzte Aktualisierung: November 2025
fCMS Version: 1.0.0
