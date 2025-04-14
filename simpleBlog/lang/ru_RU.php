<?php

$i18n = [
    // Plugin Info
    'PLUGIN_NAME' => 'Простой блог',
    'PLUGIN_DESCRIPTION' => 'Простой плагин для блога в GetSimple CMS',
    'TAB' => 'Блог',

    // Admin Menu
    'ADD_POST' => 'Добавить пост',
    'ADD_PHOTO' => 'Добавить фото',
    'DESCRIPTION' => 'Описание',
    'POSTS' => 'Посты',
    'CATEGORIES' => 'Категории',
    'ADD_CATEGORY' => 'Добавить категорию',
    'COMMENTS' => 'Комментарии',
    'SETTINGS' => 'Настройки',
    'MANAGEMENT' => 'Управление блогом',
    'SEARCH_POSTS' => 'Поиск постов...',
    'SNIPPET' => 'Сниппеты для темы',

    //function description
    'CATEGORY_LIST' => 'Список категорий',
    'POSTS_LIST' => 'Список постов',
    'POSTS_LIST_DESC' => 'Список постов с описанием',
    'POSTS_LIST_FULL' => 'Список постов с описанием и изображением',
    'POSTS_LIST_RANDOM' => 'Случайный список постов',

    // Notifications
    'POST_ADDED' => 'Пост успешно добавлен!',
    'POST_UPDATED' => 'Пост успешно обновлен!',
    'POST_DELETED' => 'Пост успешно удален!',
    'CATEGORY_ADDED' => 'Категория успешно добавлена!',
    'CATEGORY_DELETED' => 'Категория успешно удалена!',
    'COMMENT_APPROVED' => 'Комментарий успешно одобрен!',
    'SETTINGS_SAVED' => 'Настройки успешно сохранены!',

    // Settings Fields
    'HCAPTCHA_SITE_KEY' => 'Ключ сайта hCaptcha',
    'HCAPTCHA_SECRET_KEY' => 'Секретный ключ hCaptcha',
    'RECAPTCHA_SITE_KEY' => 'Ключ сайта reCAPTCHA',
    'RECAPTCHA_SECRET_KEY' => 'Секретный ключ reCAPTCHA',
    'USE_RECAPTCHA' => 'Использовать reCAPTCHA вместо hCaptcha',
    'DISABLE_CAPTCHA' => 'Отключить CAPTCHA',
    'USE_SLUG_ROUTING' => 'Использовать маршрутизацию по slug (например, /blog/slug)',
    'POSTS_PER_PAGE' => 'Постов на странице',
    'SAVE_SETTINGS' => 'Сохранить настройки',
    'SHOW_COMMENTS' => 'Показывать комментарии',
    'PARENT_PAGE' => 'Родительская страница:',

    // How to Use
    'HOW_TO_USE' => 'Как использовать',
    'HOW_TO_USE_TEXT' => 'Добавьте [blog] на страницу с slug "blog" для отображения блога.',
    'SLUG_ROUTING' => 'Маршрутизация по slug',
    'SLUG_ROUTING_TEXT' => 'Включите маршрутизацию по slug для более чистых URL.',
    'HTACCESS_RULES' => 'RewriteRule ^blog/([A-Za-z0-9-]+)/?$ index.php?id=blog&post=$1 [L]',
    'SLUG_ROUTING_STEPS' => '<br>1. Включите маршрутизацию по slug в настройках.<br> 2. Добавьте указанное правило в файл .htaccess.',

    // Form Labels and Actions
    'NAME' => 'Имя',
    'DELETE' => 'Удалить',
    'CONFIRM_DELETE' => 'Вы уверены, что хотите удалить эту категорию?',
    'CONFIRM_DELETE_POST' => 'Вы уверены, что хотите удалить этот пост?',
    'TITLE' => 'Заголовок',
    'CATEGORY' => 'Категория',
    'TAGS' => 'Теги',
    'COVER_PHOTO' => 'Обложка',
    'CONTENT' => 'Содержание',
    'STATUS' => 'Статус',
    'PUBLISHED' => 'Опубликовано',
    'DRAFT' => 'Черновик',
    'SCHEDULED_DATE' => 'Дата публикации',
    'SCHEDULED' => 'Запланировано',
    'UPDATE_POST' => 'Обновить пост',
    'CURRENT' => 'Текущий',

    // Error Messages
    'POST_NOT_FOUND' => 'Пост не найден.',
    'SELECT_POST' => 'Пожалуйста, выберите пост для редактирования.',

    // Comments
    'COMMENTS_TO_APPROVE' => 'Комментарии для одобрения',
    'APPROVED_COMMENTS' => 'Одобренные комментарии',
    'APPROVE' => 'Одобрить',
    'PREVIEW' => 'Предпросмотр',
    'EDIT' => 'Редактировать',
    'EMAIL' => 'Электронная почта',
    'COMMENT' => 'Комментарий',
    'ADD_COMMENT' => 'Добавить комментарий',
    'COMMENT_ADDED' => 'Комментарий успешно добавлен! Он появится после одобрения.',
    'CAPTCHA_FAILED' => 'Проверка CAPTCHA не удалась. Пожалуйста, попробуйте снова.',

    // Pagination
    'PREVIOUS' => 'Предыдущий',
    'NEXT' => 'Следующий'
];

?>