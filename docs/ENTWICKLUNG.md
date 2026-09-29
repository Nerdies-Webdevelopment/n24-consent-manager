# Entwicklung und Tests

Das Plugin besteht aus `n24-consent-manager.php`, dem Frontend-Skript in `assets/js/` und dem Stylesheet in `assets/css/`. Die WordPress-Option `n24_consent_manager_options` speichert die Website-Konfiguration.

## Dienste per Filter ergänzen

Der Filter `n24_consent_manager_services` erhält ein Array mit den Schlüsseln `necessary`, `statistics`, `marketing` und `external_media`. Jeder Dienst benötigt eine eindeutige `id` und passende Angaben zu Name, Anbieter, Zweck und Cookies. Zusätzliche Dienste sollten in einem eigenen Integrationsplugin registriert werden, damit Plugin-Updates diese Erweiterungen erhalten.

Ein über den Filter aufgeführter Dienst blockiert nicht automatisch ein bereits anderweitig eingebundenes Skript. Die Einbindung muss ebenfalls über die Consent-Steuerung erfolgen.

## Browsertests

Voraussetzungen: Node.js und Playwright mit einem Chromium-Browser.

```sh
node tests/browser.cjs
```

Optional bestimmen `N24_PLAYWRIGHT_MODULE` den Pfad zum Playwright-Modul und `N24_BROWSER_PATH` den Pfad zur Browser-Anwendung. Die Tests verwenden isolierte Beispieldienste und fangen Protokollanfragen ab. Sie decken unter anderem Speicherung, Ablehnen, Akzeptieren, einzelne Dienste, Widerruf, Fokus und den Nur-notwendig-Modus ab.

## PHP-Prüfungen

`N24_WP_LOAD` muss auf die `wp-load.php` einer lokalen Testinstallation mit aktiviertem Plugin zeigen.

```sh
php -l n24-consent-manager.php
php tests/smoke.php
```

Die Prüfungen kontrollieren Farb- und SVG-Bereinigung, Transparenz im Speicherfilter sowie fehlerhafte REST-Anfragen. Sie legen keine Testeinwilligungen an. Beim Start der WordPress-Testinstallation werden jedoch die normalen Plugin-Hooks ausgeführt.
