<?php

$i18n = [
    // Plugin Info
    'PLUGIN_NAME' => 'Einfacher Blog',
    'PLUGIN_DESCRIPTION' => 'Ein einfaches Blog-Plugin für GetSimple CMS',
    'TAB' => 'Blog',

    // Admin Menu
    'ADD_POST' => 'Beitrag hinzufügen',
    'ADD_PHOTO' => 'Foto hinzufügen',
    'DESCRIPTION' => 'Beschreibung',
    'POSTS' => 'Beiträge',
    'CATEGORIES' => 'Kategorien',
    'ADD_CATEGORY' => 'Kategorie hinzufügen',
    'COMMENTS' => 'Kommentare',
    'SETTINGS' => 'Einstellungen',
    'MANAGEMENT' => 'Blog-Verwaltung',
    'SEARCH_POSTS' => 'Beiträge suchen...',
    'SNIPPET' => 'Snippets für das Theme',

    //function description
    'CATEGORY_LIST' => 'Kategorienliste',
    'POSTS_LIST' => 'Beitragsliste',
    'POSTS_LIST_DESC' => 'Beitragsliste mit Beschreibung',
    'POSTS_LIST_FULL' => 'Beitragsliste mit Beschreibung und Bild',
    'POSTS_LIST_RANDOM' => 'Zufällige Beitragsliste',

    // Notifications
    'POST_ADDED' => 'Beitrag erfolgreich hinzugefügt!',
    'POST_UPDATED' => 'Beitrag erfolgreich aktualisiert!',
    'POST_DELETED' => 'Beitrag erfolgreich gelöscht!',
    'CATEGORY_ADDED' => 'Kategorie erfolgreich hinzugefügt!',
    'CATEGORY_DELETED' => 'Kategorie erfolgreich gelöscht!',
    'COMMENT_APPROVED' => 'Kommentar erfolgreich genehmigt!',
    'SETTINGS_SAVED' => 'Einstellungen erfolgreich gespeichert!',

    // Settings Fields
    'HCAPTCHA_SITE_KEY' => 'hCaptcha Site-Schlüssel',
    'HCAPTCHA_SECRET_KEY' => 'hCaptcha Geheim-Schlüssel',
    'RECAPTCHA_SITE_KEY' => 'reCAPTCHA Site-Schlüssel',
    'RECAPTCHA_SECRET_KEY' => 'reCAPTCHA Geheim-Schlüssel',
    'USE_RECAPTCHA' => 'reCAPTCHA statt hCaptcha verwenden',
    'DISABLE_CAPTCHA' => 'CAPTCHA deaktivieren',
    'USE_SLUG_ROUTING' => 'Slug-Routing verwenden (z. B. /blog/slug)',
    'POSTS_PER_PAGE' => 'Beiträge pro Seite',
    'SAVE_SETTINGS' => 'Einstellungen speichern',
    'SHOW_COMMENTS' => 'Kommentare anzeigen',
    'PARENT_PAGE' => 'Übergeordnete Seite:',

    // How to Use
    'HOW_TO_USE' => 'Wie benutzt man es',
    'HOW_TO_USE_TEXT' => 'Fügen Sie [blog] auf der Seite mit dem Slug "blog" hinzu, um den Blog anzuzeigen.',
    'SLUG_ROUTING' => 'Slug-Routing',
    'SLUG_ROUTING_TEXT' => 'Aktivieren Sie Slug-Routing für sauberere URLs.',
    'HTACCESS_RULES' => 'RewriteRule ^blog/([A-Za-z0-9-]+)/?$ index.php?id=blog&post=$1 [L]',
    'SLUG_ROUTING_STEPS' => '<br>1. Aktivieren Sie Slug-Routing in den Einstellungen.<br> 2. Fügen Sie die obige Regel in Ihre .htaccess-Datei ein.',

    // Form Labels and Actions
    'NAME' => 'Name',
    'DELETE' => 'Löschen',
    'CONFIRM_DELETE' => 'Sind Sie sicher, dass Sie diese Kategorie löschen möchten?',
    'CONFIRM_DELETE_POST' => 'Sind Sie sicher, dass Sie diesen Beitrag löschen möchten?',
    'TITLE' => 'Titel',
    'CATEGORY' => 'Kategorie',
    'TAGS' => 'Tags',
    'COVER_PHOTO' => 'Titelbild',
    'CONTENT' => 'Inhalt',
    'STATUS' => 'Status',
    'PUBLISHED' => 'Veröffentlicht',
    'DRAFT' => 'Entwurf/ Geplant',
    'SCHEDULED_DATE' => 'Geplantes Datum',
    'SCHEDULED' => 'Geplant',
    'UPDATE_POST' => 'Beitrag aktualisieren',
    'CURRENT' => 'Aktuell',

    // Error Messages
    'POST_NOT_FOUND' => 'Beitrag nicht gefunden.',
    'SELECT_POST' => 'Bitte wählen Sie einen Beitrag zum Bearbeiten aus.',

    // Comments
    'COMMENTS_TO_APPROVE' => 'Zu genehmigende Kommentare',
    'APPROVED_COMMENTS' => 'Genehmigte Kommentare',
    'APPROVE' => 'Genehmigen',
    'PREVIEW' => 'Vorschau',
    'EDIT' => 'Bearbeiten',
    'EMAIL' => 'E-Mail',
    'COMMENT' => 'Kommentar',
    'ADD_COMMENT' => 'Kommentar hinzufügen',
    'COMMENT_ADDED' => 'Kommentar erfolgreich hinzugefügt! Er erscheint nach der Genehmigung.',
    'CAPTCHA_FAILED' => 'CAPTCHA-Verifizierung fehlgeschlagen. Bitte versuchen Sie es erneut.',

    // Pagination
    'PREVIOUS' => 'Vorherige',
    'NEXT' => 'Nächste'
];

?>