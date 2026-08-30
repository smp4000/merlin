<?php

return [
    'navigation' => ['group' => 'Platform', 'label' => 'Partners'],
    'labels' => ['singular' => 'Partner', 'plural' => 'Partners'],
    'actions' => ['invite' => 'Create partner', 'send_invitation' => 'Send invitation'],
    'tabs' => ['partner' => 'Partner details', 'owner' => 'Owner & access'],
    'fields' => [
        'partner_number' => 'Partner number',
        'display_name' => 'Display name',
        'type' => 'Partner type',
        'status' => 'Status',
        'country' => 'Country',
        'locale' => 'Language',
        'owner_email' => 'Owner email address',
        'trial_ends_at' => 'Trial ends',
        'created_at' => 'Created at',
    ],
    'types' => ['single_operator' => 'Single operator', 'company_group' => 'Company group'],
    'statuses' => [
        'onboarding' => 'Onboarding',
        'active' => 'Active',
        'read_only' => 'Read only',
        'closure_requested' => 'Closure requested',
        'closed' => 'Closed',
        'suspended' => 'Security suspended',
    ],
    'invitation' => [
        'title' => 'Securely invite a new partner',
        'description' => 'The tenant and 14-day trial are only created after the owner verifies the email, accepts the legal notices, and sets a password.',
        'sent_title' => 'Invitation prepared',
        'sent_body' => 'The partner appears in this list after successful confirmation.',
    ],
    'validation' => [
        'platform_admin_required' => 'This action is restricted to platform super administrators.',
        'invitation_not_available' => 'A new partner invitation cannot currently be created for this email address.',
    ],
];
