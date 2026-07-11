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
	'TAGS' => 'Теги',

	//function description
	'CATEGORY_LIST' => 'Список категорий',
	'POSTS_LIST' => 'Список постов',
	'POSTS_LIST_DESC' => 'Список постов с описанием',
	'POSTS_LIST_FULL' => 'Список постов с описанием и изображением',
	'POSTS_LIST_RANDOM' => 'Случайный список постов',
	'TAGS_LIST' => 'Список тегов',
	'DISPLAY_POST_TAGS' => 'Показать теги поста',

	// Notifications
	'POST_ADDED' => 'Пост успешно добавлен!',
	'POST_UPDATED' => 'Пост успешно обновлен!',
	'POST_DELETED' => 'Пост успешно удален!',
	'CATEGORY_ADDED' => 'Категория успешно добавлена!',
	'CATEGORY_DELETED' => 'Категория успешно удалена!',
	'TAG_ADDED' => 'Тег успешно добавлен!',
	'TAG_DELETED' => 'Тег успешно удален!',
	'COMMENT_APPROVED' => 'Комментарий успешно одобрен!',
	'COMMENT_DELETED' => 'Комментарий успешно удален!',
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
	'ENABLE_RSS' => 'Включить RSS-ленту',
	'RSS_POST_COUNT' => 'Постов в RSS-ленте',
	'RSS_FEED_URL' => 'RSS-лента',
	'RSS_FEED_HELP' => 'Укажите этот URL в вашей RSS-читалке, чтобы подписаться на новые посты. Обновляется автоматически при публикации, редактировании или удалении поста.',
	'RSS_DISABLED_HELP' => 'RSS сейчас отключён. Включите выше и сохраните, чтобы создать rss.xml.',
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
	'CONFIRM_DELETE_COMMENT' => 'Вы уверены, что хотите удалить этот комментарий?',
	'TITLE' => 'Заголовок',
	'SLUG' => 'Слаг',
	'SLUG_PLACEHOLDER' => 'Оставьте пустым для автогенерации из заголовка',
	'CHARS_LABEL' => 'символов',
	'CHARS_OVER' => 'сверх рекомендуемой длины',
	'CATEGORY' => 'Категория',
	'COVER_PHOTO' => 'Обложка',
	'GALLERY' => 'Галерея',
	'ADD_GALLERY_IMAGE' => 'Добавить изображение в галерею',
	'GALLERY_HELP' => 'Нажмите, чтобы добавить изображения. Нажмите × на миниатюре, чтобы удалить её.',
	'PICKER_TITLE' => 'Выберите изображение',
	'PICKER_BROWSE' => 'Просмотр загруженных файлов',
	'PICKER_UPLOAD' => 'Загрузить новое',
	'PICKER_FILTER' => 'Фильтр изображений...',
	'PICKER_LOADING' => 'Загрузка...',
	'PICKER_NO_IMAGES' => 'Изображения не найдены.',
	'PICKER_CHOOSE_FILES' => 'Нажмите, чтобы выбрать файлы, или перетащите их сюда',
	'PICKER_UPLOADING' => 'Загрузка',
	'PICKER_REMOTE' => 'Внешняя ссылка',
	'PICKER_REMOTE_LABEL' => 'URL изображения',
	'PICKER_REMOTE_USE' => 'Использовать эту ссылку',
	'PICKER_REMOTE_INVALID' => 'Введите корректный http(s) URL.',
	'CONTENT' => 'Содержание',
	'STATUS' => 'Статус',
	'PUBLISHED' => 'Опубликовано',
	'DRAFT' => 'Черновик/Запланировано',
	'SCHEDULED_DATE' => 'Дата публикации',
	'SCHEDULED' => 'Запланировано',
	'PUBLISHED_ON' => 'Опубликовано',
	'LAST_EDITED_ON' => 'Последнее изменение',
	'UPDATE_POST' => 'Обновить пост',
	'CURRENT' => 'Текущий',

	// Tags
	'TAGS_HELP' => 'разделенные запятыми',
	'TAGS_MANAGEMENT' => 'Управление тегами',
	'ADD_TAG' => 'Добавить тег',

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