<?php

$i18n = [
    // Plugin Info
    'PLUGIN_NAME' => 'Простий блог',
    'PLUGIN_DESCRIPTION' => 'Простий плагін для блогу в GetSimple CMS',
    'TAB' => 'Блог',

    // Admin Menu
    'ADD_POST' => 'Додати пост',
    'ADD_PHOTO' => 'Додати фото',
    'DESCRIPTION' => 'Опис',
    'POSTS' => 'Пости',
    'CATEGORIES' => 'Категорії',
    'ADD_CATEGORY' => 'Додати категорію',
    'COMMENTS' => 'Коментарі',
    'SETTINGS' => 'Налаштування',
    'MANAGEMENT' => 'Керування блогом',
    'SEARCH_POSTS' => 'Пошук постів...',
    'SNIPPET' => 'Сніпети для теми',

    //function description
    'CATEGORY_LIST' => 'Список категорій',
    'POSTS_LIST' => 'Список постів',
    'POSTS_LIST_DESC' => 'Список постів з описом',
    'POSTS_LIST_FULL' => 'Список постів з описом та зображенням',
    'POSTS_LIST_RANDOM' => 'Випадковий список постів',

    // Notifications
    'POST_ADDED' => 'Пост успішно додано!',
    'POST_UPDATED' => 'Пост успішно оновлено!',
    'POST_DELETED' => 'Пост успішно видалено!',
    'CATEGORY_ADDED' => 'Категорію успішно додано!',
    'CATEGORY_DELETED' => 'Категорію успішно видалено!',
    'COMMENT_APPROVED' => 'Коментар успішно схвалено!',
    'SETTINGS_SAVED' => 'Налаштування успішно збережено!',

    // Settings Fields
    'HCAPTCHA_SITE_KEY' => 'Ключ сайту hCaptcha',
    'HCAPTCHA_SECRET_KEY' => 'Секретний ключ hCaptcha',
    'RECAPTCHA_SITE_KEY' => 'Ключ сайту reCAPTCHA',
    'RECAPTCHA_SECRET_KEY' => 'Секретний ключ reCAPTCHA',
    'USE_RECAPTCHA' => 'Використовувати reCAPTCHA замість hCaptcha',
    'DISABLE_CAPTCHA' => 'Вимкнути CAPTCHA',
    'USE_SLUG_ROUTING' => 'Використовувати маршрутизацію за slug (наприклад, /blog/slug)',
    'POSTS_PER_PAGE' => 'Постів на сторінці',
    'SAVE_SETTINGS' => 'Зберегти налаштування',
    'SHOW_COMMENTS' => 'Показувати коментарі',
    'PARENT_PAGE' => 'Батьківська сторінка:',

    // How to Use
    'HOW_TO_USE' => 'Як використовувати',
    'HOW_TO_USE_TEXT' => 'Додайте [blog] на сторінку зі slug "blog" для відображення блогу.',
    'SLUG_ROUTING' => 'Маршрутизація за slug',
    'SLUG_ROUTING_TEXT' => 'Увімкніть маршрутизацію за slug для чистіших URL.',
    'HTACCESS_RULES' => 'RewriteRule ^blog/([A-Za-z0-9-]+)/?$ index.php?id=blog&post=$1 [L]',
    'SLUG_ROUTING_STEPS' => '<br>1. Увімкніть маршрутизацію за slug у налаштуваннях.<br> 2. Додайте вказане правило до файлу .htaccess.',

    // Form Labels and Actions
    'NAME' => 'Ім’я',
    'DELETE' => 'Видалити',
    'CONFIRM_DELETE' => 'Ви впевнені, що хочете видалити цю категорію?',
    'CONFIRM_DELETE_POST' => 'Ви впевнені, що хочете видалити цей пост?',
    'TITLE' => 'Заголовок',
    'CATEGORY' => 'Категорія',
    'TAGS' => 'Теги',
    'COVER_PHOTO' => 'Обкладинка',
    'CONTENT' => 'Вміст',
    'STATUS' => 'Статус',
    'PUBLISHED' => 'Опубліковано',
    'DRAFT' => 'Чернетка/ заплановано',
    'SCHEDULED_DATE' => 'Дата публікації',
    'SCHEDULED' => 'Заплановано',
    'UPDATE_POST' => 'Оновити пост',
    'CURRENT' => 'Поточний',

    // Error Messages
    'POST_NOT_FOUND' => 'Пост не знайдено.',
    'SELECT_POST' => 'Будь ласка, виберіть пост для редагування.',

    // Comments
    'COMMENTS_TO_APPROVE' => 'Коментарі для схвалення',
    'APPROVED_COMMENTS' => 'Схвалені коментарі',
    'APPROVE' => 'Схвалити',
    'PREVIEW' => 'Попередній перегляд',
    'EDIT' => 'Редагувати',
    'EMAIL' => 'Електронна пошта',
    'COMMENT' => 'Коментар',
    'ADD_COMMENT' => 'Додати коментар',
    'COMMENT_ADDED' => 'Коментар успішно додано! Він з’явиться після схвалення.',
    'CAPTCHA_FAILED' => 'Перевірка CAPTCHA не вдалася. Спробуйте ще раз.',

    // Pagination
    'PREVIOUS' => 'Попередній',
    'NEXT' => 'Наступний'
];

?>