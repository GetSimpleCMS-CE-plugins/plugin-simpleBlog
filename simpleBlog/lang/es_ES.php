<?php

$i18n = [
    // Plugin Info
    'PLUGIN_NAME' => 'Blog Sencillo',
    'PLUGIN_DESCRIPTION' => 'Un plugin de blog sencillo para GetSimple CMS',
    'TAB' => 'Blog',

    // Admin Menu
    'ADD_POST' => 'Añadir publicación',
    'ADD_PHOTO' => 'Añadir foto',
    'DESCRIPTION' => 'Descripción',
    'POSTS' => 'Publicaciones',
    'CATEGORIES' => 'Categorías',
    'ADD_CATEGORY' => 'Añadir categoría',
    'COMMENTS' => 'Comentarios',
    'SETTINGS' => 'Configuraciones',
    'MANAGEMENT' => 'Gestión del blog',
    'SEARCH_POSTS' => 'Buscar publicaciones...',
    'SNIPPET' => 'Fragmentos para el tema',

    //function description
    'CATEGORY_LIST' => 'Lista de categorías',
    'POSTS_LIST' => 'Lista de publicaciones',
    'POSTS_LIST_DESC' => 'Lista de publicaciones con descripción',
    'POSTS_LIST_FULL' => 'Lista de publicaciones con descripción e imagen',
    'POSTS_LIST_RANDOM' => 'Lista de publicaciones aleatorias',

    // Notifications
    'POST_ADDED' => '¡Publicación añadida con éxito!',
    'POST_UPDATED' => '¡Publicación actualizada con éxito!',
    'POST_DELETED' => '¡Publicación eliminada con éxito!',
    'CATEGORY_ADDED' => '¡Categoría añadida con éxito!',
    'CATEGORY_DELETED' => '¡Categoría eliminada con éxito!',
    'COMMENT_APPROVED' => '¡Comentario aprobado con éxito!',
    'SETTINGS_SAVED' => '¡Configuraciones guardadas con éxito!',

    // Settings Fields
    'HCAPTCHA_SITE_KEY' => 'Clave del sitio hCaptcha',
    'HCAPTCHA_SECRET_KEY' => 'Clave secreta hCaptcha',
    'RECAPTCHA_SITE_KEY' => 'Clave del sitio reCAPTCHA',
    'RECAPTCHA_SECRET_KEY' => 'Clave secreta reCAPTCHA',
    'USE_RECAPTCHA' => 'Usar reCAPTCHA en lugar de hCaptcha',
    'DISABLE_CAPTCHA' => 'Desactivar CAPTCHA',
    'USE_SLUG_ROUTING' => 'Usar enrutamiento por slug (por ejemplo, /blog/slug)',
    'POSTS_PER_PAGE' => 'Publicaciones por página',
    'SAVE_SETTINGS' => 'Guardar configuraciones',
    'SHOW_COMMENTS' => 'Mostrar comentarios',
    'PARENT_PAGE' => 'Página principal:',

    // How to Use
    'HOW_TO_USE' => 'Cómo usar',
    'HOW_TO_USE_TEXT' => 'Añade [blog] a la página con slug "blog" para mostrar el blog.',
    'SLUG_ROUTING' => 'Enrutamiento por slug',
    'SLUG_ROUTING_TEXT' => 'Habilita el enrutamiento por slug para URLs más limpias.',
    'HTACCESS_RULES' => 'RewriteRule ^blog/([A-Za-z0-9-]+)/?$ index.php?id=blog&post=$1 [L]',
    'SLUG_ROUTING_STEPS' => '<br>1. Habilita el enrutamiento por slug en las configuraciones.<br> 2. Añade la regla anterior a tu archivo .htaccess.',

    // Form Labels and Actions
    'NAME' => 'Nombre',
    'DELETE' => 'Eliminar',
    'CONFIRM_DELETE' => '¿Estás seguro de que quieres eliminar esta categoría?',
    'CONFIRM_DELETE_POST' => '¿Estás seguro de que quieres eliminar esta publicación?',
    'TITLE' => 'Título',
    'CATEGORY' => 'Categoría',
    'TAGS' => 'Etiquetas',
    'COVER_PHOTO' => 'Foto de portada',
    'CONTENT' => 'Contenido',
    'STATUS' => 'Estado',
    'PUBLISHED' => 'Publicado',
    'BORRADOR' => 'Borrador/ Planificado',
    'SCHEDULED_DATE' => 'Fecha programada',
    'SCHEDULED' => 'Programado',
    'UPDATE_POST' => 'Actualizar publicación',
    'CURRENT' => 'Actual',

    // Error Messages
    'POST_NOT_FOUND' => 'Publicación no encontrada.',
    'SELECT_POST' => 'Por favor, selecciona una publicación para editar.',

    // Comments
    'COMMENTS_TO_APPROVE' => 'Comentarios por aprobar',
    'APPROVED_COMMENTS' => 'Comentarios aprobados',
    'APPROVE' => 'Aprobar',
    'PREVIEW' => 'Vista previa',
    'EDIT' => 'Editar',
    'EMAIL' => 'Correo electrónico',
    'COMMENT' => 'Comentario',
    'ADD_COMMENT' => 'Añadir comentario',
    'COMMENT_ADDED' => '¡Comentario añadido con éxito! Aparecerá tras la aprobación.',
    'CAPTCHA_FAILED' => 'La verificación CAPTCHA falló. Por favor, intenta de nuevo.',

    // Pagination
    'PREVIOUS' => 'Anterior',
    'NEXT' => 'Siguiente'
];

?>