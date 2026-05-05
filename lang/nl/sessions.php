<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Session Status Labels
    |--------------------------------------------------------------------------
    */

    'status_draft' => 'Concept',
    'status_published' => 'Gepubliceerd',
    'status_confirmed' => 'Bevestigd',
    'status_completed' => 'Voltooid',
    'status_cancelled' => 'Geannuleerd',

    /*
    |--------------------------------------------------------------------------
    | Session Level Labels
    |--------------------------------------------------------------------------
    */

    'level_beginner' => 'Beginner',
    'level_intermediate' => 'Gemiddeld',
    'level_advanced' => 'Gevorderd',

    /*
    |--------------------------------------------------------------------------
    | Activity Type Labels
    |--------------------------------------------------------------------------
    */

    'activity_yoga' => 'Yoga',
    'activity_strength' => 'Krachttraining',
    'activity_running' => 'Hardlopen',
    'activity_cardio' => 'Cardio',
    'activity_pilates' => 'Pilates',
    'activity_outdoor' => 'Outdoor',
    'activity_boxing' => 'Boksen',
    'activity_dance' => 'Dans',
    'activity_padel' => 'Padel',
    'activity_tennis' => 'Tennis',

    /*
    |--------------------------------------------------------------------------
    | Session Detail Page
    |--------------------------------------------------------------------------
    */

    'by_coach' => 'Door',
    'date' => 'Datum',
    'time' => 'Tijd',
    'location' => 'Locatie',
    'price' => 'Prijs',
    'per_person' => 'per persoon',
    'spots' => 'Plaatsen',
    'spots_remaining' => '{0} Geen plaatsen meer beschikbaar|{1} :count plaats beschikbaar|[2,*] :count plaatsen beschikbaar',
    'description' => 'Beschrijving',
    'share_whatsapp' => 'Delen via WhatsApp',
    'copy_link' => 'Link kopi\u00ebren',
    'link_copied' => 'Link gekopieerd naar klembord!',

    /*
    |--------------------------------------------------------------------------
    | Session Creation / Editing Form
    |--------------------------------------------------------------------------
    */

    'create_title' => 'Sessie aanmaken',
    'create_heading' => 'Nieuwe sessie aanmaken',
    'edit_title' => 'Sessie bewerken',
    'edit_heading' => 'Sessie bewerken',
    'activity_type_label' => 'Type activiteit',
    'select_activity_type' => 'Selecteer een type activiteit…',
    'level_label' => 'Niveau',
    'select_level' => 'Selecteer een niveau…',
    'title_label' => 'Titel',
    'title_placeholder' => 'bijv. Ochtendyoga in het park',
    'description_label' => 'Beschrijving',
    'description_placeholder' => 'Beschrijf uw sessie…',
    'location_label' => 'Locatie',
    'location_placeholder' => 'bijv. Jubelpark',
    'postal_code_label' => 'Postcode',
    'date_label' => 'Datum',
    'start_time_label' => 'Starttijd',
    'end_time_label' => 'Eindtijd',
    'price_label' => 'Prijs (EUR)',
    'price_hint' => 'Prijs per persoon, exclusief platformcommissie.',
    'min_participants_label' => 'Min. deelnemers',
    'min_participants_hint' => 'De sessie wordt bevestigd zodra deze drempel bereikt is.',
    'max_participants_label' => 'Max. deelnemers',
    'max_participants_hint' => 'Totaal aantal beschikbare plaatsen.',
    'cover_image_label' => 'Omslagafbeelding',
    'create_button' => 'Sessie aanmaken',
    'update_button' => 'Bijwerken',
    'created' => 'Sessie succesvol aangemaakt.',
    'updated' => 'Sessie succesvol bijgewerkt.',
    'deleted' => 'Sessie verwijderd.',
    'cancelled' => 'Sessie geannuleerd.',
    'published' => 'Sessie gepubliceerd.',
    'repeat_weekly' => 'Wekelijks herhalen',
    'number_of_weeks' => 'Aantal weken',
    'recurring_hint' => 'Maakt individuele sessies aan voor elke week (max 12 weken).',
    'recurring_created' => ':count terugkerende sessies succesvol aangemaakt.',    'recurring_edit_prompt' => 'Deze sessie maakt deel uit van een terugkerende groep. Wat wilt u bewerken?',
    'edit_this_only' => 'Alleen deze sessie',
    'edit_all_future' => 'Alle toekomstige sessies in deze groep',
    'group_updated' => ':count sessies bijgewerkt.',

    /*
    |--------------------------------------------------------------------------
    | Session Discovery Page
    |--------------------------------------------------------------------------
    */

    'discovery_title' => 'Sessies zoeken',
    'discovery_heading' => 'Een sessie vinden',
    'discovery_subtitle' => 'Blader door komende sessies bij u in de buurt.',
    'all_activities' => 'Alle activiteiten',
    'all_levels' => 'Alle niveaus',
    'date_from_label' => 'Van',
    'date_to_label' => 'Tot',
    'time_from_label' => 'Van',
    'time_to_label' => 'Tot',
    'reset_filters' => 'Filters resetten',
    'no_results' => 'Geen sessies gevonden voor deze filters.',
    'result_count' => '{0} Geen sessies gevonden|{1} :count sessie gevonden|[2,*] :count sessies gevonden',
    'view_session' => 'Sessie bekijken',
    'geo_use' => 'Mijn locatie gebruiken',
    'geo_active' => 'Locatie actief',
    'geo_clear' => 'Wissen',
    'geo_not_supported' => 'Geolocatie wordt niet ondersteund door uw browser.',
    'geo_denied' => 'Toegang tot locatie geweigerd. Postcodesearch wordt gebruikt.',
    'location_query_label' => 'Stad of postcode',
    'location_query_placeholder' => 'bijv. Elsene, Brussel, 1050',
    'radius_label' => 'Straal',
    'radius_km' => ':km km',
    'active_location_radius' => ':location · :km km',
    'geo_denied_state' => 'Toegang tot locatie geweigerd. Zoek via stad of postcode hierboven.',
    'no_results_radius' => 'Geen sessies gevonden binnen :km km. Probeer de straal te vergroten of uw filters aan te passen.',
    'invalid_location' => 'Locatie niet gevonden. Gebruik een postcode (bijv. 1000, 1050) of gemeentenaam (bijv. Brussel, Elsene).',
    'map_title' => 'Sessies op de kaart',
    'map_no_markers' => 'Geen gekoppelde sessies overeenkomen met uw huidige filters.',
    'directions' => 'Routebeschrijving',

    /*
    |--------------------------------------------------------------------------
    | Beschikbaarheid betaling
    |--------------------------------------------------------------------------
    */

    'stripe_not_ready_heading' => 'Betalingsinstelling onvolledig',
    'stripe_not_ready_body' => 'U kunt deze sessie opslaan, maar sporters kunnen deze pas boeken als u uw Stripe-betalingsinstelling heeft voltooid.',
    'stripe_not_ready_action' => 'Stripe-betalingen instellen →',

    /*
    |--------------------------------------------------------------------------
    | Publicatiebeveiliging
    |--------------------------------------------------------------------------
    */

    'publish_requires_stripe_onboarding' => 'U moet de Stripe-onboarding voltooien voordat u een sessie kunt publiceren.',

];
