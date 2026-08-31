<?php

return [
    'brand' => [
        'name' => 'Merlin',
        'tagline' => 'Betriebsplattform',
        'aria_label' => 'Merlin Betriebsplattform',
    ],
    'auth' => [
        'login' => [
            'title' => 'Anmelden',
            'heading' => 'Willkommen zurück',
            'subheading' => 'Melden Sie sich sicher in Ihrer Merlin-Betriebsplattform an.',
        ],
    ],
    'theme' => [
        'palettes' => [
            'merlin-petrol' => 'Merlin Petrol',
            'ocean-blue' => 'Ozeanblau',
            'forest-green' => 'Waldgrün',
            'violet' => 'Violett',
            'coral' => 'Koralle',
            'graphite' => 'Graphit',
        ],
    ],
    'tenant_selection' => [
        'page_title' => 'Betrieb auswählen – Merlin',
        'brand_tagline' => 'Sichere Betriebsauswahl',
        'eyebrow' => 'Arbeitsbereich',
        'title' => 'In welchem Betrieb möchten Sie arbeiten?',
        'introduction' => 'Wählen Sie bewusst einen Betrieb aus. Rollen, Daten und spätere Stationsrechte werden anschließend ausschließlich aus diesem Mandanten geladen.',
        'field' => 'Betrieb',
        'partner_number' => 'Partnernummer :number',
        'continue' => 'Betrieb öffnen',
        'security_note' => 'Ein Wechsel erweitert keine Rechte. Merlin prüft Ihre aktuelle Zugehörigkeit bei jeder Anfrage erneut.',
        'error_title' => 'Der Betrieb konnte nicht geöffnet werden.',
        'invalid' => 'Diese Betriebsauswahl ist nicht verfügbar. Bitte wählen Sie einen angezeigten Betrieb.',
        'context_required' => 'Bitte wählen Sie Ihren Betrieb erneut aus.',
    ],
    'tenant_switcher' => [
        'aria_label' => 'Aktiver Betrieb',
        'active' => 'Aktiver Betrieb',
        'change' => 'Wechseln',
    ],
    'platform_dashboard' => [
        'navigation_label' => 'Plattformübersicht',
        'title' => 'Plattformübersicht',
        'eyebrow' => 'Merlin Plattformverwaltung',
        'welcome' => 'Guten Tag, :name',
        'introduction' => 'Hier verwalten Sie ausschließlich globale Partner-Metadaten und zentrale Systemkataloge.',
        'status' => 'Plattformbereich',
        'boundary' => [
            'eyebrow' => 'Sicherheitsgrenze',
            'heading' => 'Plattform und Partnerdaten bleiben getrennt',
            'description' => 'Die Super-Admin-Rolle gewährt keinen routinemäßigen Zugriff auf Gesellschaften, Stationen, Bankverbindungen oder Mitarbeiterdaten eines Partners.',
        ],
        'cards' => [
            'aria_label' => 'Bereiche der Plattformverwaltung',
            'items' => [
                [
                    'symbol' => 'P',
                    'title' => 'Partner-Metadaten',
                    'description' => 'Registrierungen, Status und Trial-Grunddaten verwalten.',
                    'state' => 'Verfügbar',
                ],
                [
                    'symbol' => 'K',
                    'title' => 'Systemkataloge',
                    'description' => 'Globale Brands und Bankverzeichnisquellen kontrollieren.',
                    'state' => 'Verfügbar',
                ],
                [
                    'symbol' => 'S',
                    'title' => 'Supportzugriffe',
                    'description' => 'Zeitlich begrenzte, freigegebene Zugriffe werden separat ergänzt.',
                    'state' => 'Geplant',
                ],
            ],
        ],
    ],
    'dashboard' => [
        'navigation_label' => 'Übersicht',
        'title' => 'Übersicht',
        'eyebrow' => 'Merlin Betriebsplattform',
        'welcome' => 'Guten Tag, :name',
        'fallback_name' => 'Administrator',
        'introduction' => 'Wir bauen Ihre sichere Arbeitsumgebung Schritt für Schritt auf. Hier sehen Sie künftig die wichtigsten Aufgaben, Freigaben und Stationshinweise.',
        'partner_introduction' => ':tenant ist eingerichtet. Gesellschaft und erste Tankstelle sind gespeichert; als Nächstes folgt die Verwaltung dieser Grunddaten und anschließend das Team.',
        'status' => 'System bereit',
        'progress' => [
            'eyebrow' => 'Einrichtung',
            'heading' => 'Ihr Weg zur einsatzbereiten Plattform',
            'counter' => 'Schritt :current von :total',
            'steps' => [
                [
                    'title' => 'Partner anlegen',
                    'description' => 'Unternehmen und rechtliche Einheit erfassen.',
                ],
                [
                    'title' => 'Tankstellen verbinden',
                    'description' => 'Standorte, Marke und Öffnungszeiten ergänzen.',
                ],
                [
                    'title' => 'Team einladen',
                    'description' => 'Mitarbeiter sicher zuordnen und freigeben.',
                ],
                [
                    'title' => 'Zeiterfassung starten',
                    'description' => 'Regeln prüfen und erste Buchungen erfassen.',
                ],
            ],
        ],
        'cards' => [
            'aria_label' => 'Geplante Merlin-Bereiche',
            'items' => [
                [
                    'symbol' => 'P',
                    'title' => 'Partnerverwaltung',
                    'description' => 'Mandanten, Gesellschaften und Verantwortliche verwalten.',
                    'state' => 'Als Nächstes',
                ],
                [
                    'symbol' => 'T',
                    'title' => 'Tankstellenverwaltung',
                    'description' => 'Standorte mit Stammdaten und zeitlichen Zuordnungen führen.',
                    'state' => 'Geplant',
                ],
                [
                    'symbol' => 'M',
                    'title' => 'Mitarbeiterverwaltung',
                    'description' => 'Einladungen, Rollen und Stationszuweisungen steuern.',
                    'state' => 'Geplant',
                ],
            ],
            'states' => [
                'ready' => 'Grunddaten erfasst',
                'next' => 'Als Nächstes',
            ],
        ],
    ],
];
