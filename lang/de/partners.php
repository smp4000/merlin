<?php

return [
    'navigation' => ['group' => 'Plattform', 'label' => 'Partner'],
    'labels' => ['singular' => 'Partner', 'plural' => 'Partner'],
    'actions' => ['invite' => 'Partner anlegen', 'send_invitation' => 'Einladung senden'],
    'tabs' => ['partner' => 'Partnerdaten', 'owner' => 'Inhaber & Zugang'],
    'fields' => [
        'partner_number' => 'Partnernummer',
        'display_name' => 'Anzeigename',
        'type' => 'Partnerart',
        'status' => 'Status',
        'country' => 'Land',
        'locale' => 'Sprache',
        'owner_email' => 'E-Mail-Adresse des Inhabers',
        'trial_ends_at' => 'Testphase bis',
        'created_at' => 'Angelegt am',
    ],
    'types' => ['single_operator' => 'Einzelunternehmen', 'company_group' => 'Unternehmensgruppe'],
    'statuses' => [
        'onboarding' => 'Onboarding',
        'active' => 'Aktiv',
        'read_only' => 'Nur-Lesen',
        'closure_requested' => 'Schließung beantragt',
        'closed' => 'Geschlossen',
        'suspended' => 'Sicherheitsgesperrt',
    ],
    'invitation' => [
        'title' => 'Neuen Partner sicher einladen',
        'description' => 'Der Mandant und die 14-tägige Testphase entstehen erst, nachdem der Inhaber die E-Mail bestätigt, die Rechtshinweise akzeptiert und sein Passwort gesetzt hat.',
        'sent_title' => 'Einladung wurde vorbereitet',
        'sent_body' => 'Der Partner erscheint nach erfolgreicher Bestätigung in dieser Liste.',
    ],
    'validation' => [
        'platform_admin_required' => 'Diese Aktion ist ausschließlich für Plattform-Super-Admins erlaubt.',
        'invitation_not_available' => 'Für diese E-Mail-Adresse kann derzeit keine neue Partner-Einladung angelegt werden.',
    ],
];
