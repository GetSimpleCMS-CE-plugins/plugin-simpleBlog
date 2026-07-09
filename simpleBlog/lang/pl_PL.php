<?php

$i18n = [
	// Plugin Info
	'PLUGIN_NAME' => 'Prosty Blog',
	'PLUGIN_DESCRIPTION' => 'Prosta wtyczka blogowa dla GetSimple CMS',
	'TAB' => 'Blog',

	// Admin Menu
	'ADD_POST' => 'Dodaj post',
	'ADD_PHOTO' => 'Dodaj zdjęcie',
	'DESCRIPTION' => 'Opis',
	'POSTS' => 'Posty',
	'CATEGORIES' => 'Kategorie',
	'ADD_CATEGORY' => 'Dodaj kategorię',
	'COMMENTS' => 'Komentarze',
	'SETTINGS' => 'Ustawienia',
	'MANAGEMENT' => 'Zarządzanie blogiem',
	'SEARCH_POSTS' => 'Szukaj postów...',
	'SNIPPET' => 'Fragmenty kodu dla motywu',
	'TAGS' => 'Tagi',

	//function description
	'CATEGORY_LIST' => 'Lista kategorii',
	'POSTS_LIST' => 'Lista postów',
	'POSTS_LIST_DESC' => 'Lista postów z opisem',
	'POSTS_LIST_FULL' => 'Lista postów z opisem i zdjęciem',
	'POSTS_LIST_RANDOM' => 'Losowa lista postów',
	'TAGS_LIST' => 'Lista tagów',
	'DISPLAY_POST_TAGS' => 'Wyświetl tagi posta',

	// Notifications
	'POST_ADDED' => 'Post dodany pomyślnie!',
	'POST_UPDATED' => 'Post zaktualizowany pomyślnie!',
	'POST_DELETED' => 'Post usunięty pomyślnie!',
	'CATEGORY_ADDED' => 'Kategoria dodana pomyślnie!',
	'CATEGORY_DELETED' => 'Kategoria usunięta pomyślnie!',
	'TAG_ADDED' => 'Tag dodany pomyślnie!',
	'TAG_DELETED' => 'Tag usunięty pomyślnie!',
	'COMMENT_APPROVED' => 'Komentarz zatwierdzony pomyślnie!',
	'COMMENT_DELETED' => 'Komentarz usunięty pomyślnie!',
	'SETTINGS_SAVED' => 'Ustawienia zapisane pomyślnie!',

	// Settings Fields
	'HCAPTCHA_SITE_KEY' => 'Klucz strony hCaptcha',
	'HCAPTCHA_SECRET_KEY' => 'Sekretny klucz hCaptcha',
	'RECAPTCHA_SITE_KEY' => 'Klucz strony reCAPTCHA',
	'RECAPTCHA_SECRET_KEY' => 'Sekretny klucz reCAPTCHA',
	'USE_RECAPTCHA' => 'Użyj reCAPTCHA zamiast hCaptcha',
	'DISABLE_CAPTCHA' => 'Wyłącz CAPTCHA',
	'USE_SLUG_ROUTING' => 'Użyj routingu slug (np. /blog/slug)',
	'POSTS_PER_PAGE' => 'Postów na stronę',
	'SAVE_SETTINGS' => 'Zapisz ustawienia',
	'SHOW_COMMENTS' => 'Pokaż komentarze',
	'PARENT_PAGE' => 'Strona nadrzędna:',

	// How to Use
	'HOW_TO_USE' => 'Jak używać',
	'HOW_TO_USE_TEXT' => 'Dodaj [blog] na stronę ze slugiem "blog", aby wyświetlić blog.',
	'SLUG_ROUTING' => 'Routing slug',
	'SLUG_ROUTING_TEXT' => 'Włącz routing slug dla bardziej przejrzystych URL-i.',
	'HTACCESS_RULES' => 'RewriteRule ^blog/([A-Za-z0-9-]+)/?$ index.php?id=blog&post=$1 [L]',
	'SLUG_ROUTING_STEPS' => '<br>1. Włącz routing slug w ustawieniach.<br> 2. Dodaj powyższą regułę do pliku .htaccess.',

	// Form Labels and Actions
	'NAME' => 'Nazwa',
	'DELETE' => 'Usuń',
	'CONFIRM_DELETE' => 'Czy na pewno chcesz usunąć tę kategorię?',
	'CONFIRM_DELETE_POST' => 'Czy na pewno chcesz usunąć ten post?',
	'CONFIRM_DELETE_COMMENT' => 'Czy na pewno chcesz usunąć ten komentarz?',
	'TITLE' => 'Tytuł',
	'CATEGORY' => 'Kategoria',
	'COVER_PHOTO' => 'Zdjęcie okładkowe',
	'GALLERY' => 'Galeria',
	'ADD_GALLERY_IMAGE' => 'Dodaj zdjęcie do galerii',
	'GALLERY_HELP' => 'Kliknij, aby dodać zdjęcia. Kliknij × na miniaturze, aby ją usunąć.',
	'PICKER_TITLE' => 'Wybierz obraz',
	'PICKER_BROWSE' => 'Przeglądaj przesłane pliki',
	'PICKER_UPLOAD' => 'Prześlij nowy',
	'PICKER_FILTER' => 'Filtruj obrazy...',
	'PICKER_LOADING' => 'Ładowanie...',
	'PICKER_NO_IMAGES' => 'Nie znaleziono obrazów.',
	'PICKER_CHOOSE_FILES' => 'Kliknij, aby wybrać pliki, lub przeciągnij je tutaj',
	'PICKER_UPLOADING' => 'Przesyłanie',
	'CONTENT' => 'Treść',
	'STATUS' => 'Status',
	'PUBLISHED' => 'Opublikowany',
	'DRAFT' => 'Szkic/Zaplanowane',
	'SCHEDULED_DATE' => 'Data publikacji',
	'SCHEDULED' => 'Zaplanowany',
	'UPDATE_POST' => 'Zaktualizuj post',
	'CURRENT' => 'Aktualny',

	// Tags
	'TAGS_HELP' => 'oddzielone przecinkami',
	'TAGS_MANAGEMENT' => 'Zarządzanie tagami',
	'ADD_TAG' => 'Dodaj tag',

	// Error Messages
	'POST_NOT_FOUND' => 'Post nie znaleziony.',
	'SELECT_POST' => 'Proszę wybrać post do edycji.',

	// Comments
	'COMMENTS_TO_APPROVE' => 'Komentarze do zatwierdzenia',
	'APPROVED_COMMENTS' => 'Zatwierdzone komentarze',
	'APPROVE' => 'Zatwierdź',
	'PREVIEW' => 'Podgląd',
	'EDIT' => 'Edytuj',
	'EMAIL' => 'E-mail',
	'COMMENT' => 'Komentarz',
	'ADD_COMMENT' => 'Dodaj komentarz',
	'COMMENT_ADDED' => 'Komentarz dodany pomyślnie! Pojawi się po zatwierdzeniu.',
	'CAPTCHA_FAILED' => 'Weryfikacja CAPTCHA nie powiodła się. Spróbuj ponownie.',

	// Pagination
	'PREVIOUS' => 'Poprzedni',
	'NEXT' => 'Następny'
];

?>