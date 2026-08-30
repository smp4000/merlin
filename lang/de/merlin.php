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
    'dashboard' => [
        'navigation_label' => 'Übersicht',
        'title' => 'Übersicht',
        'eyebrow' => 'Merlin Betriebsplattform',
        'welcome' => 'Guten Tag, :name',
        'fallback_name' => 'Administrator',
        'introduction' => 'Wir bauen Ihre sichere Arbeitsumgebung Schritt für Schritt auf. Hier sehen Sie künftig die wichtigsten Aufgaben, Freigaben und Stationshinweise.',
        'status' => 'System bereit',
        'progress' => [
            'eyebrow' => 'Einrichtung',
            'heading' => 'Ihr Weg zur einsatzbereiten Plattform',
            'counter' => 'Schritt 1 von 4',
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
        ],
    ],
];
