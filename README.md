# N24 Consent Manager

WordPress-Plugin für das Consent-Tool der bestehenden Website.

## Screenshots

### Backend mit Live Vorschau

![N24 Consent Manager Backend](assets/screenshots/n24-consent-manager-backend.png)

### Cookie-Banner im Frontend

![N24 Consent Manager Cookie-Banner](assets/screenshots/n24-consent-manager.png)

## Installation

1. Den Ordner `n24-consent-manager` nach `wp-content/plugins/` kopieren oder die ZIP-Datei in WordPress hochladen.
2. Im WordPress-Adminbereich unter `Plugins` aktivieren.
3. Unter `Einstellungen > N24 Consent Manager` URLs, Farben, Icon und Texte prüfen.

## Backend

Die Einstellungsseite ist in Tabs gegliedert. Farbfelder verwenden den WordPress-Colorpicker, rechts wird eine Live-Vorschau der Cookie-Box angezeigt.

## Sprache

Die Standardsprache des Plugins ist Deutsch. Eine englische Sprachdatei liegt unter `languages/n24-consent-manager-en_US.po` und `languages/n24-consent-manager-en_US.mo`.

## Schriftart

Das Plugin setzt keine eigene Schriftfamilie. Banner, Buttons und Tabs erben die Schriftart des aktiven Themes.

## Cookie-Einstellungen-Link

Der Floating-Button wird automatisch ausgegeben. Für einen zusätzlichen Link im Footer oder in Seiten kann dieser Shortcode genutzt werden:

```text
[n24_consent_settings]
```

Die alten Shortcodes `[conset_cookie_settings]` und `[conny_cookie_settings]` bleiben als Aliase erhalten.

## Dienste erweitern

Weitere Statistik- oder Marketing-Dienste können per Filter ergänzt werden:

```php
add_filter('n24_consent_manager_services', function (array $services): array {
    $services['statistics'][] = [
        'id' => 'example_analytics',
        'name' => 'Example Analytics',
        'provider' => 'Example GmbH',
        'address' => 'Musterstraße 1, 12345 Musterstadt',
        'privacyUrl' => 'https://example.com/datenschutz',
        'purpose' => 'Statistische Auswertung der Website-Nutzung.',
        'cookies' => [
            [
                'name' => '_example',
                'expiry' => '13 Monate',
                'type' => 'HTTP Cookie',
                'purpose' => 'Wiedererkennung von Besuchern.',
            ],
        ],
    ];

    return $services;
});
```
