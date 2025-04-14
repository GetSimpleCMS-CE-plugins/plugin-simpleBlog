<?php
$thisfile = basename(__FILE__, ".php");



i18n_merge('simpleBlog') || i18n_merge('simpleBlog', 'en_US');

register_plugin(
    $thisfile,
    'SimpleBlog',
    '1.1',
    'Multicolor',
    'http://ko-fi.com/multicolorplugins',
    'SimpleBlog for Simple CMS! Based on Sqlite3',
    'blog',
    'blog_admin'
);

$svgIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M4 21q-.825 0-1.412-.587T2 19V3l1.675 1.675L5.325 3L7 4.675L8.675 3l1.65 1.675L12 3l1.675 1.675L15.325 3L17 4.675L18.675 3l1.65 1.675L22 3v16q0 .825-.587 1.413T20 21zm0-2h7v-6H4zm9 0h7v-2h-7zm0-4h7v-2h-7zm-9-4h16V8H4z"/></svg>';

add_action('nav-tab', 'createNavTab', array('blog', $thisfile, $svgIcon . i18n_r('simpleBlog/TAB'), 'blog_admin'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/ADD_POST'), '?id=simpleBlog&blog_admin&tab=add_post'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/POSTS'), '?id=simpleBlog&blog_admin&tab=posts'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/CATEGORIES'), '?id=simpleBlog&blog_admin&tab=categories'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/COMMENTS'), '?id=simpleBlog&blog_admin&tab=comments'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/SETTINGS'), '?id=simpleBlog&blog_admin&tab=settings'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/SNIPPET'), '?id=simpleBlog&blog_admin&tab=snippet'));

add_action('index-pretemplate', 'blog_theme_content');


register_style('simpleBlog', $SITEURL . 'plugins/simpleBlog/css/style.css?v=54', GSVERSION, 'screen');
queue_style('simpleBlog', GSFRONT);


function blog_init_db()
{
    $db_file = GSDATAOTHERPATH . 'blog.db';
    if (!file_exists($db_file)) {
        $db = new SQLite3($db_file);

        $db->exec("CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL,
            content TEXT NOT NULL,
            category_id INTEGER,
            cover_photo TEXT,
            date INTEGER NOT NULL,
            published INTEGER DEFAULT 1,
            scheduled_date INTEGER,
            description TEXT,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )");

        $db->exec("CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL
        )");

        $db->exec("CREATE TABLE tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT NOT NULL
        )");

        $db->exec("CREATE TABLE post_tags (
            post_id INTEGER,
            tag_id INTEGER,
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (tag_id) REFERENCES tags(id),
            PRIMARY KEY (post_id, tag_id)
        )");

        $db->exec("CREATE TABLE comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER,
            author TEXT NOT NULL,
            email TEXT NOT NULL,
            content TEXT NOT NULL,
            date INTEGER NOT NULL,
            approved INTEGER DEFAULT 0,
            FOREIGN KEY (post_id) REFERENCES posts(id)
        )");

        $db->exec("CREATE TABLE settings (
            name TEXT PRIMARY KEY,
            value TEXT
        )");

        $db->close();
    }

    $cover_folder = GSDATAUPLOADPATH . 'blog_covers/';
    if (!file_exists($cover_folder)) {
        mkdir($cover_folder, 0755, true);
    }
}

function generate_slug($title, $db, $table = 'posts', $exclude_id = null)
{
    // Map of special characters to their base equivalents
    $char_map = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
        'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z'
    ];
    
    // Replace special characters with their base equivalents
    $slug = strtr($title, $char_map);
    
    // Convert to lowercase and remove all special characters except spaces
    $slug = strtolower(preg_replace('/[^A-Za-z0-9\s]/', '', $slug));
    
    // Replace spaces with hyphens and remove multiple consecutive hyphens
    $slug = preg_replace('/\s+/', '-', trim($slug));
    
    $base_slug = $slug;
    $counter = 1;
    
    while (true) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE slug = ? AND id != ?");
        $stmt->bindValue(1, $slug, SQLITE3_TEXT);
        $stmt->bindValue(2, $exclude_id ?: 0, SQLITE3_INTEGER);
        $result = $stmt->execute()->fetchArray();
        if ($result[0] == 0) {
            break;
        }
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

function get_setting($db, $name)
{
    $stmt = $db->prepare("SELECT value FROM settings WHERE name = ?");
    $stmt->bindValue(1, $name, SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray();
    return $result ? $result['value'] : '';
}


// sitemap



add_action('sitemap-aftersave', 'get_sitemap');
function get_sitemap()
{
    // Inicjalizacja zmiennych i połączenie z bazą danych
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    global $SITEURL;

    // Ścieżka do pliku sitemap.xml w głównym folderze
    $sitemap_file = GSROOTPATH . 'sitemap.xml';

    // Tworzenie zawartości sitemapy w zmiennej
    $sitemap_content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $sitemap_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    // 1. Sekcja postów z bazy danych
    $stmt = $db->prepare("SELECT * FROM posts");
    $result = $stmt->execute();

    while ($row = $result->fetchArray()) {
        $sitemap_content .= '    <url>' . PHP_EOL;

        if (get_setting($db, 'use_slug_routing')) {
            $url = $SITEURL . get_setting($db, 'root_page') . '/' . htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8');
        } else {
            // Budujemy URL bez kodowania separatora &
            $url = $SITEURL . 'index.php?id=' . htmlspecialchars(get_setting($db, 'root_page'), ENT_QUOTES, 'UTF-8') .
                '&slug=' . htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8');
        }

        $sitemap_content .= '        <loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;

        // Opcjonalnie: dodanie daty modyfikacji jeśli istnieje w tabeli
        if (isset($row['date'])) {
            $sitemap_content .= '        <lastmod>' . date('Y-m-d', strtotime($row['date'])) . '</lastmod>' . PHP_EOL;
        }

        $sitemap_content .= '        <changefreq>monthly</changefreq>' . PHP_EOL;
        $sitemap_content .= '        <priority>0.8</priority>' . PHP_EOL;
        $sitemap_content .= '    </url>' . PHP_EOL;
    }

    // 2. Sekcja natywnych stron GetSimple CMS
    $pages_dir = GSDATAPAGESPATH;
    $pages = glob($pages_dir . '*.xml');

    // Sprawdzamy, czy Friendly URLs są włączone (na podstawie konfiguracji GetSimple CMS)
    $friendly_urls = (defined('GSUSECUSTOMURL') && GSUSECUSTOMURL === true) ||
        (isset($GLOBALS['USECUSTOMURL']) && $GLOBALS['USECUSTOMURL'] === true);

    foreach ($pages as $page) {
        $page_data = getXML($page);
        if ($page_data && (string) $page_data->private != 'Y') { // Pomijamy prywatne strony
            $sitemap_content .= '    <url>' . PHP_EOL;

            $slug = pathinfo($page, PATHINFO_FILENAME);

            if ($friendly_urls) {
                // Friendly URLs włączone - używamy slugów z ukośnikiem
                $url = ($slug == 'index') ? $SITEURL : $SITEURL . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '/';
            } else {
                // Friendly URLs wyłączone - używamy formatu index.php?id=slug
                $url = $SITEURL . 'index.php?id=' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
            }

            $sitemap_content .= '        <loc>' . $url . '</loc>' . PHP_EOL;

            // Data modyfikacji strony
            if (isset($page_data->pubDate)) {
                $sitemap_content .= '        <lastmod>' . date('Y-m-d', strtotime($page_data->pubDate)) . '</lastmod>' . PHP_EOL;
            }

            // Priorytet zależny od tego, czy to strona główna
            $priority = ($slug == 'index') ? '1.0' : '0.6';
            $sitemap_content .= '        <changefreq>weekly</changefreq>' . PHP_EOL;
            $sitemap_content .= '        <priority>' . $priority . '</priority>' . PHP_EOL;
            $sitemap_content .= '    </url>' . PHP_EOL;
        }


    }

    // Zakończenie sitemapy
    $sitemap_content .= '</urlset>' . PHP_EOL;

    // Zapis do pliku
    if (file_put_contents($sitemap_file, $sitemap_content) === false) {
        // Opcjonalna obsługa błędów
        error_log("Nie udało się zapisać sitemapy do pliku: " . $sitemap_file);
    }

    // Zamknięcie połączenia z bazą
    $db->close();
}

//function for frontend


function get_categoryList()
{
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $stmt = $db->prepare("SELECT * FROM categories");
    $result = $stmt->execute();
    global $SITEURL;

    echo '<ul class="simpleBlogCategory">';
    while ($row = $result->fetchArray()) {

        if (get_setting($db, 'use_slug_routing')) {
            echo '<li><a href="' . $SITEURL . get_setting($db, 'root_page') . '/category/' . $row['slug'] . '">' . $row['name'] . '</a></li>';
        } else {
            echo '<li><a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&category=' . $row['slug'] . '">' . $row['name'] . '</a></li>';
        }


    }
    echo '</ul>';


    $db->close();

}



function get_newPostList($number)
{
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $stmt = $db->prepare("SELECT * FROM posts LIMIT :number");
    $stmt->bindValue(':number', $number, SQLITE3_INTEGER);
    $result = $stmt->execute();
    global $SITEURL;
    echo '<ul class="simpleBlogNewPost">';
    while ($row = $result->fetchArray()) {


        if (get_setting($db, 'use_slug_routing')) {
            echo '<li><a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '">' . $row['title'] . '</a></li>';
        } else {
            echo '<li><a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '">' . $row['title'] . '</a></li>';
        }
    }
    echo '</ul>';


    $db->close();

}



function get_newPostListWithDesc($number)
{
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $stmt = $db->prepare("SELECT * FROM posts LIMIT :number");
    $stmt->bindValue(':number', $number, SQLITE3_INTEGER);
    $result = $stmt->execute();
    global $SITEURL;
    echo '<ul class="simpleBlogWithDesc">';
    while ($row = $result->fetchArray()) {


        if (get_setting($db, 'use_slug_routing')) {
            echo '<li><a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '"><h5>' . $row['title'] . '</h5></a>
            <p>' . $row['description'] . '</p>
            </li>';
        } else {
            echo '<li><a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '">' . $row['title'] . '</a>
              <p>' . $row['description'] . '</p>
            </li>';
        }
    }
    echo '</ul>';

    $db->close();

}



function get_newPostListFull($number)
{
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $stmt = $db->prepare("SELECT * FROM posts LIMIT :number");
    $stmt->bindValue(':number', $number, SQLITE3_INTEGER);
    $result = $stmt->execute();
    global $SITEURL;
    echo '<ul class="simpleBlogWithDesc">';
    while ($row = $result->fetchArray()) {


        if (get_setting($db, 'use_slug_routing')) {
            echo '<li>
              <a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '"> <img class="coverphoto" src="' . $SITEURL . $row['cover_photo'] . '"></a>
            <a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '"><h5>' . $row['title'] . '</h5></a>
            <p>' . $row['description'] . '</p>
            </li>';
        } else {
            echo '<li>
              <a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '"> <img class="coverphoto" src="' . $SITEURL . $row['cover_photo'] . '"></a>

            <a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '">' . $row['title'] . '</a>
              <p>' . $row['description'] . '</p>
            </li>';
        }
    }
    echo '</ul>';

    $db->close();

}



function get_randomPostList($number)
{
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $stmt = $db->prepare("SELECT * FROM posts ORDER BY RANDOM() LIMIT :number");
    $stmt->bindValue(':number', $number, SQLITE3_INTEGER);
    $result = $stmt->execute();
    global $SITEURL;
    echo '<ul class="simpleBlogRandom">';
    while ($row = $result->fetchArray()) {


        if (get_setting($db, 'use_slug_routing')) {
            echo '<li><a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '">' . $row['title'] . '</a></li>';
        } else {
            echo '<li><a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '">' . $row['title'] . '</a></li>';
        }
    }
    echo '</ul>';


    $db->close();

}




//end function for frontend;

function blog_admin()
{

    $pp = '<div id="donate-button-container" style="display:flex; align-items:center; justify-content:space-between;background:#fafafa;border:solid 1px #ddd;padding:10px;margin:10px 0;">
<div id="donate-button"></div>
<script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js" charset="UTF-8"></script>
<script>
PayPal.Donation.Button({
env:"production",
hosted_button_id:"4WXFFQMU7CUGG",
image: {
src:"https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif",
alt:"Donate with PayPal button",
title:"PayPal - The safer, easier way to pay online!",
}
}).render("#donate-button");
</script>

<b>Buy me small coffe!</b>
</div>
';

    global $thisfile;
    blog_init_db();
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');

    echo '<style>
        .blog-admin-container { width:90%; margin: 20px auto; font-family: Arial, sans-serif; }
        .blog-admin-container h3 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        .blog-admin-container h4 { color: #000; margin: 20px 0; font-size:1.3rem; display:block; }
        .blog-form { background: #fafafa; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border:solid 1px #ddd }
        .blog-form label { display: block; margin-bottom: 5px; font-weight: bold; }
        .blog-form input[type="text"], .blog-form input[type="email"], .blog-form textarea, .blog-form select {
            width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        .blog-form input[type="file"] { margin-bottom: 15px; }
        .blog-form input[type="submit"] { background: #0073aa; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .blog-form input[type="submit"]:hover { background: #005177; }
        .post-list, .category-list { margin-top: 20px; }
        .post-item, .category-item { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .post-item a, .category-item a { color: #0073aa; text-decoration: none; margin-left: 10px; }
        .post-item a:hover, .category-item a:hover { text-decoration: underline; }
        .category-item form, .post-item form { display: inline; }
        .category-item input[type="submit"], .post-item input[type="submit"] { background: #d63638; color: #fff; padding: 5px 10px; }
        .category-item input[type="submit"]:hover, .post-item input[type="submit"]:hover { background: #b32d2e; }
        .comment-item { background: #f9f9f9; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .comment-item form { display: inline; }
        .notification { background: #e0f7fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; color: #0073aa; }
        .help-section { margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 5px; border: 1px solid #ddd; }
        .help-section h5 { margin-top: 0; color: #333; }
        .help-section pre { background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }

        .post-item,.category-item{
            display: grid;
    grid-template-columns: 1fr 50px;
    align-items: center;
    justify-content: center;
    padding:20px;
}

.comment-item{
display:grid;
 grid-template-columns: 1fr 80px 80px;
}

.comment-item p{
margin:0;
}

.post-item p{
margin:0;
}


.category-item p{
    margin:0;
    }

.post-item a{
text-decoration:none !important;
color:rgba(0,0,0,0.5    ) !important;
}
    </style>';

    echo '<div class="blog-admin-container">';
    echo '<h3>' . i18n_r('simpleBlog/MANAGEMENT') . '</h3>';

    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'posts';

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {




            case 'add_post':
                $title = $_POST['title'];
                $slug = generate_slug($title, $db);
                $content = $_POST['content'];
                $category_id = $_POST['category_id'];
                $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
                $date = time();
                $published = $_POST['published'];
                $scheduled_date = !empty($_POST['scheduled_date']) ? strtotime($_POST['scheduled_date']) : null;
                $description = $_POST['description'];
                $metadane = $_POST['metadane'];

                $cover_photo = $_POST['cover_photo'];


                $stmt = $db->prepare("INSERT INTO posts (title, slug, content, category_id, cover_photo, date, published, scheduled_date, description,metadane) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?)");
                $stmt->bindValue(1, $title, SQLITE3_TEXT);
                $stmt->bindValue(2, $slug, SQLITE3_TEXT);
                $stmt->bindValue(3, $content, SQLITE3_TEXT);
                $stmt->bindValue(4, $category_id, SQLITE3_INTEGER);
                $stmt->bindValue(5, $cover_photo, SQLITE3_TEXT);
                $stmt->bindValue(6, $date, SQLITE3_INTEGER);
                $stmt->bindValue(7, $published, SQLITE3_INTEGER);
                $stmt->bindValue(8, $scheduled_date, $scheduled_date ? SQLITE3_INTEGER : SQLITE3_NULL);
                $stmt->bindValue(9, $description, SQLITE3_TEXT);
                $stmt->bindValue(10, $metadane, SQLITE3_TEXT);
                $stmt->execute();
                $post_id = $db->lastInsertRowID();

                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $tag_slug = generate_slug($tag, $db, 'tags');
                        $stmt = $db->prepare("INSERT OR IGNORE INTO tags (name, slug) VALUES (?, ?)");
                        $stmt->bindValue(1, $tag, SQLITE3_TEXT);
                        $stmt->bindValue(2, $tag_slug, SQLITE3_TEXT);
                        $stmt->execute();

                        $stmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
                        $stmt->bindValue(1, $tag_slug, SQLITE3_TEXT);
                        $tag_id = $stmt->execute()->fetchArray()['id'];

                        $stmt = $db->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                        $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                        $stmt->bindValue(2, $tag_id, SQLITE3_INTEGER);
                        $stmt->execute();
                    }
                }

                get_sitemap();

                echo "<meta http-equiv='refresh' content='0;url=load.php?id=simpleBlog&blog_admin&tab=edit_post&post_id=" . $post_id . "&successpostcreated'>";


                break;

            case 'edit_post':
                $post_id = $_POST['post_id'];
                $title = $_POST['title'];
                $slug = generate_slug($title, $db, 'posts', $post_id);
                $content = $_POST['content'];
                $category_id = $_POST['category_id'];
                $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
                $published = $_POST['published'];
                $scheduled_date = !empty($_POST['scheduled_date']) ? strtotime($_POST['scheduled_date']) : null;

                $stmt = $db->prepare("SELECT cover_photo FROM posts WHERE id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $existing_post = $stmt->execute()->fetchArray();
                $cover_photo = $_POST['cover_photo'];
                $description = $_POST['description'];
                $metadane = $_POST['metadane'];



                $stmt = $db->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, category_id = ?, cover_photo = ?, published = ?, scheduled_date = ?, description = ?,metadane = ? WHERE id = ?");
                $stmt->bindValue(1, $title, SQLITE3_TEXT);
                $stmt->bindValue(2, $slug, SQLITE3_TEXT);
                $stmt->bindValue(3, $content, SQLITE3_TEXT);
                $stmt->bindValue(4, $category_id, SQLITE3_INTEGER);
                $stmt->bindValue(5, $cover_photo, SQLITE3_TEXT);
                $stmt->bindValue(6, $published, SQLITE3_INTEGER);
                $stmt->bindValue(7, $scheduled_date, $scheduled_date ? SQLITE3_INTEGER : SQLITE3_NULL);
                $stmt->bindValue(8, $description, SQLITE3_TEXT); // Poprawiona kolejność
                $stmt->bindValue(9, $post_id, SQLITE3_INTEGER);  // Poprawiona kolejność
                $stmt->bindValue(10, $metadane, SQLITE3_INTEGER);  // Poprawiona kolejność
                $stmt->execute();

                $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $stmt->execute();

                foreach ($tags as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $tag_slug = generate_slug($tag, $db, 'tags');
                        $stmt = $db->prepare("INSERT OR IGNORE INTO tags (name, slug) VALUES (?, ?)");
                        $stmt->bindValue(1, $tag, SQLITE3_TEXT);
                        $stmt->bindValue(2, $tag_slug, SQLITE3_TEXT);
                        $stmt->execute();

                        $stmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
                        $stmt->bindValue(1, $tag_slug, SQLITE3_TEXT);
                        $tag_id = $stmt->execute()->fetchArray()['id'];

                        $stmt = $db->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                        $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                        $stmt->bindValue(2, $tag_id, SQLITE3_INTEGER);
                        $stmt->execute();
                    }
                }
                get_sitemap();
                echo '<div class="notification">' . i18n_r('simpleBlog/POST_UPDATED') . '</div>';
                break;

            case 'delete_post':
                $post_id = $_POST['post_id'];
                $stmt = $db->prepare("SELECT cover_photo FROM posts WHERE id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $post = $stmt->execute()->fetchArray();

                $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $stmt->execute();
                $stmt = $db->prepare("DELETE FROM comments WHERE post_id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $stmt->execute();
                $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $stmt->execute();
                echo '<div class="notification">' . i18n_r('simpleBlog/POST_DELETED') . '</div>';

                get_sitemap();
                break;

            case 'add_category':
                $name = $_POST['name'];
                $slug = generate_slug($name, $db, 'categories');
                $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmt->bindValue(1, $name, SQLITE3_TEXT);
                $stmt->bindValue(2, $slug, SQLITE3_TEXT);
                $stmt->execute();
                echo '<div class="notification">' . i18n_r('simpleBlog/CATEGORY_ADDED') . '</div>';
                break;

            case 'delete_category':
                $category_id = $_POST['category_id'];
                $stmt = $db->prepare("UPDATE posts SET category_id = NULL WHERE category_id = ?");
                $stmt->bindValue(1, $category_id, SQLITE3_INTEGER);
                $stmt->execute();
                $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bindValue(1, $category_id, SQLITE3_INTEGER);
                $stmt->execute();
                echo '<div class="notification">' . i18n_r('simpleBlog/CATEGORY_DELETED') . '</div>';
                break;



            case 'approve_comment':
                $stmt = $db->prepare("UPDATE comments SET approved = 1 WHERE id = ?");
                $stmt->bindValue(1, $_POST['comment_id'], SQLITE3_INTEGER);
                $stmt->execute();
                echo '<div class="notification">' . i18n_r($thisfile . '/COMMENT_APPROVED') . '</div>';
                break;

            case 'delete_comment': // Nowa akcja: usuwanie komentarza
                $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->bindValue(1, $_POST['comment_id'], SQLITE3_INTEGER);
                $stmt->execute();
                echo '<div class="notification">' . i18n_r($thisfile . '/COMMENT_DELETED') . '</div>';
                break;


            case 'save_settings':
                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('hcaptcha_site_key', ?)");
                $stmt->bindValue(1, $_POST['hcaptcha_site_key'], SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('hcaptcha_secret_key', ?)");
                $stmt->bindValue(1, $_POST['hcaptcha_secret_key'], SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('use_slug_routing', ?)");
                $stmt->bindValue(1, isset($_POST['use_slug_routing']) ? '1' : '0', SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('recaptcha_site_key', ?)");
                $stmt->bindValue(1, $_POST['recaptcha_site_key'], SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('recaptcha_secret_key', ?)");
                $stmt->bindValue(1, $_POST['recaptcha_secret_key'], SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('use_recaptcha', ?)");
                $stmt->bindValue(1, isset($_POST['use_recaptcha']) ? '1' : '0', SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('disable_captcha', ?)");
                $stmt->bindValue(1, isset($_POST['disable_captcha']) ? '1' : '0', SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('posts_per_page', ?)");
                $stmt->bindValue(1, $_POST['posts_per_page'], SQLITE3_INTEGER);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('root_page', ?)");
                $stmt->bindValue(1, $_POST['root_page'], SQLITE3_TEXT);
                $stmt->execute();

                $stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('show_comments', ?)");
                $stmt->bindValue(1, $_POST['show_comments'] ?? 0, SQLITE3_TEXT);
                $stmt->execute();

                get_sitemap();
                echo '<div class="notification">' . i18n_r('simpleBlog/SETTINGS_SAVED') . '</div>';
                break;
        }
    }

    switch ($tab) {


        case 'snippet':

            echo '<style>
            
            pre{background: #fff;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow-x: auto;
    margin:10px 0;
        }

        p{
        margin:0 !important;
        padding:0 !important;
        }
            
            </style>';

            echo '<h4>' . i18n_r('simpleBlog/SNIPPET') . '</h4>';

            echo '<p>' . i18n_r('simpleBlog/CATEGORY_LIST') . '</p>';
            echo ' <pre>get_categoryList()</pre>';
            echo '<p>' . i18n_r('simpleBlog/POSTS_LIST') . '</p>';
            echo ' <pre>get_newPostList(3)</pre>';
            echo '<p>' . i18n_r('simpleBlog/POSTS_LIST_DESC') . '</p>';
            echo ' <pre>get_newPostListWithDesc(3)</pre>';
            echo '<p>' . i18n_r('simpleBlog/POSTS_LIST_FULL') . '</p>';
            echo ' <pre>get_newPostListFull(3)</pre>';
            echo '<p>' . i18n_r('simpleBlog/POSTS_LIST_RANDOM') . '</p>';

            echo ' <pre>get_randomPostList(3)</pre>';


            break;

        case 'settings':


            echo '<h4>' . i18n_r('simpleBlog/SETTINGS') . '</h4>';
            echo '<div class="blog-form">';
            echo '<form method="post">';
            echo '<input type="hidden" name="action" value="save_settings">';
            echo '<label>' . i18n_r('simpleBlog/HCAPTCHA_SITE_KEY') . ':</label><input type="text" name="hcaptcha_site_key" value="' . htmlspecialchars(get_setting($db, 'hcaptcha_site_key')) . '"><br>';
            echo '<label>' . i18n_r('simpleBlog/HCAPTCHA_SECRET_KEY') . ':</label><input type="text" name="hcaptcha_secret_key" value="' . htmlspecialchars(get_setting($db, 'hcaptcha_secret_key')) . '"><br>';
            echo '<label>' . i18n_r('simpleBlog/RECAPTCHA_SITE_KEY') . ':</label><input type="text" name="recaptcha_site_key" value="' . htmlspecialchars(get_setting($db, 'recaptcha_site_key')) . '"><br>';
            echo '<label>' . i18n_r('simpleBlog/RECAPTCHA_SECRET_KEY') . ':</label><input type="text" name="recaptcha_secret_key" value="' . htmlspecialchars(get_setting($db, 'recaptcha_secret_key')) . '"><br>';
            echo '<label><input type="checkbox" name="use_recaptcha" value="1" ' . (get_setting($db, 'use_recaptcha') === '1' ? 'checked' : '') . '> ' . i18n_r('simpleBlog/USE_RECAPTCHA') . '</label><br>';
            echo '<label><input type="checkbox" name="disable_captcha" value="1" ' . (get_setting($db, 'disable_captcha') === '1' ? 'checked' : '') . '> ' . i18n_r('simpleBlog/DISABLE_CAPTCHA') . '</label><br>';
            echo '<label><input type="checkbox" name="use_slug_routing" value="1" ' . (get_setting($db, 'use_slug_routing') === '1' ? 'checked' : '') . '> ' . i18n_r('simpleBlog/USE_SLUG_ROUTING') . '</label><br>';
            echo '<label><input type="checkbox" name="show_comments" value="1" ' . (get_setting($db, 'show_comments') === '1' ? 'checked' : '') . '> ' . i18n_r('simpleBlog/SHOW_COMMENTS') . '</label><br>';

            echo '<label>' . i18n_r('simpleBlog/POSTS_PER_PAGE') . ':</label><input type="number" style="width:100%;padding:10px;border:solid 1px #ddd;border-radius:5px;margin-bottom:10px;" name="posts_per_page" value="' . htmlspecialchars(get_setting($db, 'posts_per_page') ?: 10) . '" min="1" required><br>';

            echo '<label>' . i18n_r('simpleBlog/PARENT_PAGE') . '<select class="root_page" name="root_page">';

            foreach (glob(GSDATAPAGESPATH . '*.xml') as $file) {
                $file = pathinfo($file)['filename'];
                echo '<option value="' . $file . '">' . $file . '</option></label>';

            }
            ;

            echo '</select>';

            echo '<script>document.querySelector(".root_page").value = "' . get_setting($db, 'root_page') . '"</script>';

            echo '<input type="submit" style="margin-top:30px" value="' . i18n_r('simpleBlog/SAVE_SETTINGS') . '">';

            echo '</form>';
            echo '</div>';

            echo '<div class="help-section">';
            echo '<h5>' . i18n_r('simpleBlog/SLUG_ROUTING') . '</h5>';
            echo '<button  style="background: #0073aa;color:#fff;padding:10px;margin:5px 0"  onclick="copyToClipboard()">Copy to Clipboard</button>';
            if (get_setting($db, 'root_page') && get_setting($db, 'use_slug_routing')) {

                echo '<div style="background:#fafafa;border:solid 1px #ddd;padding:10px;line-height:1.3; ">';
                echo '#For Post<br>
                 RewriteRule ^' . get_setting($db, 'root_page') . '/([A-Za-z0-9-]+)/?$ index.php?id=' . get_setting($db, 'root_page') . '&post=$1 [L]';
                echo '<br>#For Category<br>';
                echo 'RewriteRule ^' . get_setting($db, 'root_page') . '/category/([A-Za-z0-9-]+)/?$ index.php?id=' . get_setting($db, 'root_page') . '&category=$1 [L]</div>';


                echo '<p>' . i18n_r('simpleBlog/SLUG_ROUTING_STEPS') . '</p>';
                echo '</div>';

                echo '<script>
function copyToClipboard() {
    const textToCopy = `#For Post
RewriteRule ^index/([A-Za-z0-9-]+)/?$ index.php?id=index&post=$1 [L]
#For Category
RewriteRule ^index/category/([A-Za-z0-9-]+)/?$ index.php?id=index&category=$1 [L]`;
    
    // Tworzymy tymczasowy element textarea
    const textarea = document.createElement("textarea");
    textarea.value = textToCopy;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand("copy");
    document.body.removeChild(textarea);
    alert("Kod został skopiowany do schowka!");
}
</script>';

            } else {
                echo '<h4>MAKE PARENT PAGE and SLUG ROUTING</h4>
            </div>';
            }
            break;



        case 'categories':

            echo '<h4>' . i18n_r('simpleBlog/ADD_CATEGORY') . '</h4>';
            echo '<div class="blog-form">';
            echo '<form method="post">';
            echo '<input type="hidden" name="action" value="add_category">';
            echo '<label>' . i18n_r('simpleBlog/NAME') . ':</label><input type="text" name="name" required><br>';
            echo '<input type="submit" value="' . i18n_r('simpleBlog/ADD_CATEGORY') . '">';
            echo '</form>';
            echo '</div>';
            echo '<hr>';
            echo '<h4>' . i18n_r('simpleBlog/CATEGORIES') . '</h4>';
            echo '<div class="category-list">';
            $results = $db->query("SELECT * FROM categories ORDER BY name ASC");
            while ($row = $results->fetchArray()) {
                echo '<div class="category-item">';
                echo "<p>{$row['name']} ({$row['slug']})";
                echo "<form method='post' style='display:inline;'>";
                echo "<input type='hidden' name='action' value='delete_category'>";
                echo "<input type='hidden' name='category_id' value='{$row['id']}'>";
                echo "<input type='submit' value='" . i18n_r('simpleBlog/DELETE') . "' onclick='return confirm(\"" . i18n_r('simpleBlog/CONFIRM_DELETE') . "\");'>";
                echo "</form></p>";
                echo '</div>';
            }
            echo '</div>';
            break;

        case 'add_post':


            echo '<style>
            
                   .addPhoto{
                   background:#000;
                   color:#fff;
                   border-radius:5px !important;
                   border:none;
                   padding:10px;
                   margin-bottom:10px;
                   }

                   .cur_photo{
                   display:block;
                   margin:10px 0;
        }

           
            </style>';

            global $SITEURL;
            echo '<h4>' . i18n_r('simpleBlog/ADD_POST') . '</h4>';
            echo '<div class="blog-form">';
            echo '<form method="post" enctype="multipart/form-data">';
            echo '<input type="hidden" name="action" value="add_post">';
            echo '<label>' . i18n_r('simpleBlog/TITLE') . ':</label><input type="text" name="title" required><br>';
            echo '<label>' . i18n_r('simpleBlog/CATEGORY') . ':</label>';

            $categories = $db->query("SELECT * FROM categories");
            if (!$categories) {
                echo "<p style='color:red'>First, create a category!</p>";
            } else {
                echo '<select name="category_id" required>';
                while ($cat = $categories->fetchArray()) {
                    echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                }
                echo '</select>';
            }
            ;



            echo '<br>';
            echo '<label style="display:none">' . i18n_r('simpleBlog/TAGS') . ':</label><input type="hidden" name="tags"><br>';
            echo '<label>' . i18n_r('simpleBlog/COVER_PHOTO') . ':</label>
            
       <input type="text" name="cover_photo" class="cover_photo">
            <button class="addPhoto">' . i18n_r('simpleBlog/ADD_PHOTO') . '</button>
            <script>
            	document.querySelector(".addPhoto").addEventListener("click", (e) => {
		e.preventDefault();
		window.open("' . $SITEURL . 'plugins/simpleBlog/filebrowser/imagebrowser.php?type=images&CKEditor=post-content", "", "left=10,top=10,width=960,height=500");
        });
        </script>';


            echo '<br>';

            echo '<label>' . i18n_r('simpleBlog/DESCRIPTION') . ':</label><textarea name="description"  style="height:150px"></textarea><br>';
            echo '<label>Metadata (SEO):</label><textarea name="metadane"  style="height:150px"></textarea><br>';
            echo '<label>' . i18n_r('simpleBlog/CONTENT') . ':</label><textarea id="post-content" name="content" required rows="10"></textarea><br>';
            echo '<label>' . i18n_r('simpleBlog/STATUS') . ':</label><select name="published" required>';
            echo '<option value="1">' . i18n_r('simpleBlog/PUBLISHED') . '</option>';
            echo '<option value="0">' . i18n_r('simpleBlog/DRAFT') . '</option>';
            echo '</select><br>';
            echo '<label>' . i18n_r('simpleBlog/SCHEDULED_DATE') . ':</label><input type="datetime-local" name="scheduled_date"><br>';
            echo '<input type="submit" style="margin-top:20px;" value="' . i18n_r('simpleBlog/ADD_POST') . '">';
            echo '</form>';
            echo '</div>';


            global $EDTOOL;
            global $EDLANG;
            global $EDHEIGHT;
            global $toolbar;
            global $options;
            global $EDOPTIONS;


            if (isset($EDTOOL))
                $EDTOOL = returnJsArray($EDTOOL);
            if (isset($toolbar))
                $toolbar = returnJsArray($toolbar); // handle plugins that corrupt this
            else if (strpos(trim($EDTOOL), '[[') !== 0 && strpos(trim($EDTOOL), '[') === 0) {
                $EDTOOL = "[$EDTOOL]";
            }

            if (isset($toolbar) && strpos(trim($toolbar), '[[') !== 0 && strpos($toolbar, '[') === 0) {
                $toolbar = "[$toolbar]";
            }
            $toolbar = isset($EDTOOL) ? ",toolbar: " . trim($EDTOOL, ",") : '';
            $options = isset($EDOPTIONS) ? ',' . trim($EDOPTIONS, ",") : '';


            echo '<script type="text/javascript" src="template/js/ckeditor/ckeditor.js"?t=" . getDef("GSCKETSTAMP") : ""; ?>"></script>

				<script type="text/javascript">
					var editor = CKEDITOR.replace("post-content", {
						skin: "getsimple",
						forcePasteAsPlainText: true,
						language: "' . $EDLANG . '",
						defaultLanguage: "en",
				 
						entities: false,
 						height: "' . $EDHEIGHT . '",
						baseHref: "' . $SITEURL . '",
						tabSpaces: 10,
						filebrowserBrowseUrl: "filebrowser.php?type=all",
						filebrowserImageBrowseUrl: "filebrowser.php?type=images",
						filebrowserWindowWidth: "730",
						filebrowserWindowHeight: "500"
                        ' . $toolbar . $options . '
					 			
					});

					CKEDITOR.instances["post-content"].on("instanceReady", InstanceReadyEvent);

					function InstanceReadyEvent(ev) {
						_this = this;

						this.document.on("keyup", function () {
							$("#editform #post-content").trigger("change");
							_this.resetDirty();
						});

						this.timer = setInterval(function () { trackChanges(_this) }, 500);
					}

					/**
					 * keep track of changes for editor
					 * until cke 4.2 is released with onchange event
					 */
					function trackChanges(editor) {
						// console.log("check changes");
						if (editor.checkDirty()) {
							$("#post-content").trigger("change");
							editor.resetDirty();
						}
					};
				</script>
';


            break;

        case 'edit_post':

            echo '<style>
            
                   .addPhoto{
                   background:#000;
                   color:#fff;
                   border-radius:5px !important;
                   border:none;
                   padding:10px;
                   }

                   .cur_photo{
                   display:block;
                   margin:10px 0;
        }

           
            </style>';

            if (isset($_GET['successpostcreated'])) {
                echo '<div class="notification">' . i18n_r('simpleBlog/POST_ADDED') . '</div>';
            }
            ;

            global $SITEURL;
            echo '<h4>' . i18n_r('simpleBlog/EDIT') . '</h4>';
            if (isset($_GET['post_id'])) {
                $post_id = $_GET['post_id'];
                $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
                $stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                $post = $stmt->execute()->fetchArray();

                if ($post) {
                    $tags_stmt = $db->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
                    $tags_stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
                    $tags_result = $tags_stmt->execute();
                    $tags = [];
                    while ($tag = $tags_result->fetchArray()) {
                        $tags[] = $tag['name'];
                    }
                    $tags_str = implode(', ', $tags);

                    echo '<div class="blog-form">';
                    echo '<form method="post" enctype="multipart/form-data">';
                    echo '<input type="hidden" name="action" value="edit_post">';
                    echo '<input type="hidden" name="post_id" value="' . $post['id'] . '">';
                    echo '<label>' . i18n_r('simpleBlog/TITLE') . ':</label><input type="text" name="title" value="' . htmlspecialchars($post['title']) . '" required><br>';
                    echo '<label>' . i18n_r('simpleBlog/CATEGORY') . ':</label><select name="category_id" required>';

                    $categories = $db->query("SELECT * FROM categories");
                    while ($cat = $categories->fetchArray()) {
                        $selected = $cat['id'] == $post['category_id'] ? ' selected' : '';
                        echo "<option value='{$cat['id']}'{$selected}>{$cat['name']}</option>";
                    }
                    echo '</select><br>';

                    echo '<label  style="display:none">' . i18n_r('simpleBlog/TAGS') . ':</label><input type="hidden" name="tags" value="' . htmlspecialchars($tags_str) . '"><br>';
                    echo '<label>' . i18n_r('simpleBlog/COVER_PHOTO') . ':</label> <input type="text" name="cover_photo" value="' . htmlspecialchars($post['cover_photo']) . '" class="cover_photo">
            <button class="addPhoto">' . i18n_r('simpleBlog/ADD_PHOTO') . '</button>
            <script>
            	document.querySelector(".addPhoto").addEventListener("click", (e) => {
		e.preventDefault();
		window.open("' . $SITEURL . 'plugins/simpleBlog/filebrowser/imagebrowser.php?type=images&CKEditor=post-content", "", "left=10,top=10,width=960,height=500");
        });
        </script>';
                    if ($post['cover_photo']) {
                        echo '<br> <img src="' . $GLOBALS['SITEURL'] . $post['cover_photo'] . '" style="max-width:200px" class="cur_photo">';
                    }
                    echo '<br>';
                    echo '<br>';

                    echo '<label>' . i18n_r('simpleBlog/DESCRIPTION') . ':</label><textarea name="description" style="height:150px">' . htmlspecialchars($post['description']) . '</textarea><br>';
                    echo '<label>Metadane (dla SEO):</label><textarea name="metadane"  style="height:150px"></textarea><br>';

                    echo '<label>' . i18n_r('simpleBlog/CONTENT') . ':</label><textarea name="content" id="post-content" required rows="10">' . htmlspecialchars($post['content']) . '</textarea><br>';
                    echo '<label>' . i18n_r('simpleBlog/STATUS') . ':</label><select name="published" required>';
                    echo '<option value="1"' . ($post['published'] == 1 ? ' selected' : '') . '>' . i18n_r('simpleBlog/PUBLISHED') . '</option>';
                    echo '<option value="0"' . ($post['published'] == 0 ? ' selected' : '') . '>' . i18n_r('simpleBlog/DRAFT') . '</option>';
                    echo '</select><br>';
                    $scheduled_date = $post['scheduled_date'] ? date('Y-m-d\TH:i', $post['scheduled_date']) : '';
                    echo '<label>' . i18n_r('simpleBlog/SCHEDULED_DATE') . ':</label><input type="datetime-local" name="scheduled_date" value="' . htmlspecialchars($scheduled_date) . '"><br>';
                    echo '<input type="submit" style="margin-top:20px;" style="margin-top:20px" value="' . i18n_r('simpleBlog/UPDATE_POST') . '">';
                    echo '</form>';
                    echo '</div>';

                    global $EDTOOL;
                    global $EDLANG;
                    global $EDHEIGHT;
                    global $toolbar;
                    global $options;
                    global $EDOPTIONS;


                    if (isset($EDTOOL))
                        $EDTOOL = returnJsArray($EDTOOL);
                    if (isset($toolbar))
                        $toolbar = returnJsArray($toolbar); // handle plugins that corrupt this
                    else if (strpos(trim($EDTOOL), '[[') !== 0 && strpos(trim($EDTOOL), '[') === 0) {
                        $EDTOOL = "[$EDTOOL]";
                    }

                    if (isset($toolbar) && strpos(trim($toolbar), '[[') !== 0 && strpos($toolbar, '[') === 0) {
                        $toolbar = "[$toolbar]";
                    }
                    $toolbar = isset($EDTOOL) ? ",toolbar: " . trim($EDTOOL, ",") : '';
                    $options = isset($EDOPTIONS) ? ',' . trim($EDOPTIONS, ",") : '';



                    echo '<script type="text/javascript" src="template/js/ckeditor/ckeditor.js"?t=" . getDef("GSCKETSTAMP") : ""; ?>"></script>

				<script type="text/javascript">
					var editor = CKEDITOR.replace("post-content", {
						skin: "getsimple",
						forcePasteAsPlainText: true,
						language: "' . $EDLANG . '",
						defaultLanguage: "en",
				 
						entities: false,
 						height: "' . $EDHEIGHT . '",
						baseHref: "' . $SITEURL . '",
						tabSpaces: 10,
						filebrowserBrowseUrl: "filebrowser.php?type=all",
						filebrowserImageBrowseUrl: "filebrowser.php?type=images",
						filebrowserWindowWidth: "730",
						filebrowserWindowHeight: "500"
                        ' . $toolbar . $options . '
					 			
					});

					CKEDITOR.instances["post-content"].on("instanceReady", InstanceReadyEvent);

					function InstanceReadyEvent(ev) {
						_this = this;

						this.document.on("keyup", function () {
							$("#editform #post-content").trigger("change");
							_this.resetDirty();
						});

						this.timer = setInterval(function () { trackChanges(_this) }, 500);
					}

					/**
					 * keep track of changes for editor
					 * until cke 4.2 is released with onchange event
					 */
					function trackChanges(editor) {
						// console.log("check changes");
						if (editor.checkDirty()) {
							$("#post-content").trigger("change");
							editor.resetDirty();
						}
					};
				</script>
';


                } else {
                    echo '<p>' . i18n_r('simpleBlog/POST_NOT_FOUND') . '</p>';
                }
            } else {
                echo '<p>' . i18n_r('simpleBlog/SELECT_POST') . '</p>';
            }
            break;

        case 'comments':

            echo '
            <style>

            .comment-item{
            display:flex;
            align-items:center;
            justify-content:space-between;
        }

         .comment-item .aprove{
         background:green;
         border:none;
         border-radius:5px !important;
         padding:5px;
         }

         .comment-item .delete{
background:red;
border:none;
border-radius:5px !important;
padding:5px;
        }
            </style>
            ';
            // Komentarze do zatwierdzenia
            echo '<h4>' . i18n_r('simpleBlog/COMMENTS_TO_APPROVE') . '</h4>';
            $results = $db->query("SELECT c.*, p.title FROM comments c LEFT JOIN posts p ON c.post_id = p.id WHERE c.approved = 0");
            while ($row = $results->fetchArray()) {
                echo '<div class="comment-item">';
                echo "<p><strong>{$row['title']}</strong> - {$row['author']}: {$row['content']}</p>";
                echo "<div class='btn-row'><form method='post' style='display:inline;'>";
                echo "<input type='hidden' name='action' value='approve_comment'>";
                echo "<input type='hidden' name='comment_id' value='{$row['id']}'>";
                echo '<button type="submit" class="aprove"><svg width="15px" height="15px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">

<defs>

<style>.cls-1{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style>

</defs>

<title/>

<g id="checkmark">

<line class="cls-1" x1="3" x2="12" y1="16" y2="25"/>

<line class="cls-1" x1="12" x2="29" y1="25" y2="7"/>

</g>

</svg></button>';
                echo "</form>";
                echo "<form method='post' style='display:inline; margin-left:10px;'>";
                echo "<input type='hidden' name='action' value='delete_comment'>";
                echo "<input type='hidden' name='comment_id' value='{$row['id']}'>";
                echo "<button type='submit' class='delete' onclick='return confirm(\"" . i18n_r($thisfile . '/SIMPLEBLOG_CONFIRM_DELETE_COMMENT') . "\");'>";

                echo '<svg width="15px" height="15px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">

<defs>

<style>.cls-1{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style>

</defs>

<title/>

<g id="cross">

<line class="cls-1" x1="7" x2="25" y1="7" y2="25"/>

<line class="cls-1" x1="7" x2="25" y1="25" y2="7"/>

</g>

</svg></button>';

                echo "</form></div>";
                echo '</div>';
            }

            // Lista zaakceptowanych komentarzy
            echo '<h4>' . i18n_r('simpleBlog/APPROVED_COMMENTS') . '</h4>';
            $approved_results = $db->query("SELECT c.*, p.title FROM comments c LEFT JOIN posts p ON c.post_id = p.id WHERE c.approved = 1 ORDER BY c.date DESC");
            while ($row = $approved_results->fetchArray()) {
                echo '<div class="comment-item">';
                echo "<p><strong>{$row['title']}</strong> - {$row['author']}: {$row['content']} (" . date('Y-m-d H:i', $row['date']) . ")</p>";
                echo "<div class='btn-row'><form method='post' style='display:inline; margin-left:10px;'>";
                echo "<input type='hidden' name='action' value='delete_comment'>";
                echo "<input type='hidden' name='comment_id' value='{$row['id']}'>";
                echo "<button type='submit' class='delete' onclick='return confirm(\"" . i18n_r($thisfile . '/SIMPLEBLOG_CONFIRM_DELETE_COMMENT') . "\");'>";
                echo '<svg width="15px" height="15px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">

                <defs>
                
                <style>.cls-1{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style>
                
                </defs>
                
                <title/>
                
                <g id="cross">
                
                <line class="cls-1" x1="7" x2="25" y1="7" y2="25"/>
                
                <line class="cls-1" x1="7" x2="25" y1="25" y2="7"/>
                
                </g>
                
                </svg></button>';


                echo "</form></div> ";
                echo '</div>';
            }
            break;

        case 'posts':
        default:
            echo '<style>
        .post-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
        
        .post-item button {
            background: red;
            color: #fff;
            border: none;
            border-radius: 5px !important;
        }
    
        .btn-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
    
        .pagination {
            margin: 20px 0;
            display: flex;
            gap: 5px;
            justify-content: center;
        }
    
        .pagination a {
            padding: 5px 10px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration:none !important;
        }
    
        .pagination a.active {
            background: #007bff;
            color: white;
        }
    
        .search-container {
            margin-bottom: 20px;
        }
    
        #searchInput {
          width:100%;padding:10px !important;box-sizing:border-box;
        }
    </style>';

            echo '<div class="search-container">';
            echo '<input type="text" id="searchInput" style="" placeholder="' . i18n_r('simpleBlog/SEARCH_POSTS') . '">';
            echo '</div>';

            echo '<h4>' . i18n_r('simpleBlog/POSTS') . '</h4>';
            echo '<div class="post-list" id="postList">';

            $posts_per_page = 10;
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $offset = ($page - 1) * $posts_per_page;

            $all_posts = [];
            $all_results = $db->query("SELECT p.*, c.name as category_name 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.date DESC");

            while ($row = $all_results->fetchArray()) {
                $tags_stmt = $db->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
                $tags_stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
                $tags_result = $tags_stmt->execute();
                $tags = [];
                while ($tag = $tags_result->fetchArray()) {
                    $tags[] = $tag['name'];
                }
                $row['tags'] = $tags;
                $all_posts[] = $row;
            }

            $total_results = count($all_posts);
            $total_pages = ceil($total_results / $posts_per_page);

            $displayed_posts = array_slice($all_posts, $offset, $posts_per_page);

            foreach ($displayed_posts as $row) {
                $tags_str = !empty($row['tags']) ? ' | ' . i18n_r('simpleBlog/TAGS') . ': ' . implode(', ', $row['tags']) : '';

                echo '<div class="post-item" data-title="' . htmlspecialchars($row['title']) . '" data-category="' . htmlspecialchars($row['category_name'] ?? '') . '">';
                echo "<p><b>{$row['title']}</b> - {$row['category_name']} - " .
                    date('Y-m-d', $row['date']) .
                    " [" . ($row['published'] ? i18n_r('simpleBlog/PUBLISHED') : i18n_r('simpleBlog/DRAFT')) . "]" .
                    ($row['scheduled_date'] ? " | " . i18n_r('simpleBlog/SCHEDULED') . ": " . date('Y-m-d H:i', $row['scheduled_date']) : "") .
                    "</p>
            <div class='btn-row'> 
                <a target='_blank' href='/" . get_setting($db, 'root_page') . "?post={$row['slug']}'>
                    <svg width='15px' height='15px' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'>
                        <path d='M10 17C13.866 17 17 13.866 17 10C17 6.13401 13.866 3 10 3C6.13401 3 3 6.13401 3 10C3 13.866 6.13401 17 10 17Z' stroke='#000000' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/>
                        <path d='M20.9992 21L14.9492 14.95' stroke='#000000' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/>
                    </svg>
                </a>" .
                    " <a href='?id=simpleBlog&blog_admin&tab=edit_post&post_id={$row['id']}'>" .
                    '<svg width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>' . "</a>" .
                    " <form method='post' style='display:inline;'><input type='hidden' name='action' value='delete_post'><input type='hidden' name='post_id' value='{$row['id']}'><button type='submit' onclick='return confirm(\"" . i18n_r('simpleBlog/CONFIRM_DELETE_POST') . "\");'>" .
                    '<svg width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L17.1991 18.0129C17.129 19.065 17.0939 19.5911 16.8667 19.99C16.6666 20.3412 16.3648 20.6235 16.0011 20.7998C15.588 21 15.0607 21 14.0062 21H9.99377C8.93927 21 8.41202 21 7.99889 20.7998C7.63517 20.6235 7.33339 20.3412 7.13332 19.99C6.90607 19.5911 6.871 19.065 6.80086 18.0129L6 6M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>' . "</button></form></div>";
                echo '</div>';
            }
            echo '</div>';

            echo '<div class="pagination">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active = $i == $page ? 'active' : '';
                echo "<a href='?id=simpleBlog&blog_admin&page=$i' class='$active'>$i</a>";
            }
            echo '</div>';

            echo '<script>
    const allPosts = ' . json_encode($all_posts) . ';
    const postsPerPage = ' . $posts_per_page . ';
    const rootPage = "' . get_setting($db, 'root_page') . '";
    
    function renderPosts(posts) {
        const postList = document.getElementById("postList");
        postList.innerHTML = "";
        
        posts.forEach(post => {
            const tagsStr = post.tags.length > 0 ? " | ' . i18n_r('simpleBlog/TAGS') . ': " + post.tags.join(", ") : "";
            const scheduledStr = post.scheduled_date ? " | ' . i18n_r('simpleBlog/SCHEDULED') . ': " + new Date(post.scheduled_date * 1000).toLocaleString() : "";
            const publishedStr = post.published ? "' . i18n_r('simpleBlog/PUBLISHED') . '" : "' . i18n_r('simpleBlog/DRAFT') . '";
            
            const postHtml = `
                <div class="post-item" data-title="${post.title.toLowerCase()}" data-category="${post.category_name ? post.category_name.toLowerCase() : \'\'}">
                    <p><b>${post.title}</b> - ${post.category_name || \'Brak kategorii\'} - ${new Date(post.date * 1000).toLocaleDateString()} [${publishedStr}]${scheduledStr}${tagsStr}</p>
                    <div class="btn-row">
                        <a target="_blank" href="/${rootPage}?post=${post.slug}">
                            <svg width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10 17C13.866 17 17 13.866 17 10C17 6.13401 13.866 3 10 3C6.13401 3 3 6.13401 3 10C3 13.866 6.13401 17 10 17Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M20.9992 21L14.9492 14.95" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a href="?id=simpleBlog&blog_admin&tab=edit_post&post_id=${post.id}">
                            <svg width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M21.2799 6.40005L11.7399 15.94C10.7899 16.89 7.96987 17.33 7.33987 16.7C6.70987 16.07 7.13987 13.25 8.08987 12.3L17.6399 2.75002C17.8754 2.49308 18.1605 2.28654 18.4781 2.14284C18.7956 1.99914 19.139 1.92124 19.4875 1.9139C19.8359 1.90657 20.1823 1.96991 20.5056 2.10012C20.8289 2.23033 21.1225 2.42473 21.3686 2.67153C21.6147 2.91833 21.8083 3.21243 21.9376 3.53609C22.0669 3.85976 22.1294 4.20626 22.1211 4.55471C22.1128 4.90316 22.0339 5.24635 21.8894 5.5635C21.7448 5.88065 21.5375 6.16524 21.2799 6.40005V6.40005Z" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M11 4H6C4.93913 4 3.92178 4.42142 3.17163 5.17157C2.42149 5.92172 2 6.93913 2 8V18C2 19.0609 2.42149 20.0783 3.17163 20.8284C3.92178 21.5786 4.93913 22 6 22H17C19.21 22 20 20.2 20 18V13" stroke="#000000" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="post_id" value="${post.id}">
                            <button type="submit" onclick="return confirm(\'' . i18n_r('simpleBlog/CONFIRM_DELETE_POST') . '\');">
                                <svg width="15px" height="15px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 6L17.1991 18.0129C17.129 19.065 17.0939 19.5911 16.8667 19.99C16.6666 20.3412 16.3648 20.6235 16.0011 20.7998C15.588 21 15.0607 21 14.0062 21H9.99377C8.93927 21 8.41202 21 7.99889 20.7998C7.63517 20.6235 7.33339 20.3412 7.13332 19.99C6.90607 19.5911 6.871 19.065 6.80086 18.0129L6 6M4 6H20M16 6L15.7294 5.18807C15.4671 4.40125 15.3359 4.00784 15.0927 3.71698C14.8779 3.46013 14.6021 3.26132 14.2905 3.13878C13.9376 3 13.523 3 12.6936 3H11.3064C10.477 3 10.0624 3 9.70951 3.13878C9.39792 3.26132 9.12208 3.46013 8.90729 3.71698C8.66405 4.00784 8.53292 4.40125 8.27064 5.18807L8 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            `;
            postList.innerHTML += postHtml;
        });
    }
    
    document.getElementById("searchInput").addEventListener("input", function(e) {
        const searchTerm = e.target.value.toLowerCase();
        
        const filteredPosts = allPosts.filter(post => {
            const title = post.title.toLowerCase();
            const category = post.category_name ? post.category_name.toLowerCase() : "";
            const tags = post.tags.join(" ").toLowerCase();
            const date = new Date(post.date * 1000).toLocaleDateString();
            
            return title.includes(searchTerm) || 
                   category.includes(searchTerm) || 
                   tags.includes(searchTerm) || 
                   date.includes(searchTerm);
        });
        
        renderPosts(filteredPosts.slice(0, postsPerPage));
    });
    </script>';
            break;
    }

    echo    $pp ;
    echo '</div>';
    $db->close();
}


function checkCategory($catNumber)
{
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $checkCategory = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $checkCategory->bindValue(1, $catNumber, SQLITE3_INTEGER); // Powiązanie wartości z placeholderem
    $result = $checkCategory->execute(); // Wykonanie zapytania
    $total_checkCategory = $result->fetchArray(); // Pobranie wyniku
    $catName = $total_checkCategory[2];
    return $catName;
}


function get_blog_content($slug = null)
{
    global $SITEURL;
    //icon 
    $timeIcon = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 9C7.44772 9 7 9.44771 7 10C7 10.5523 7.44772 11 8 11H16C16.5523 11 17 10.5523 17 10C17 9.44771 16.5523 9 16 9H8Z" fill="currentColor"/>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M6 3C4.34315 3 3 4.34315 3 6V18C3 19.6569 4.34315 21 6 21H18C19.6569 21 21 19.6569 21 18V6C21 4.34315 19.6569 3 18 3H6ZM5 18V7H19V18C19 18.5523 18.5523 19 18 19H6C5.44772 19 5 18.5523 5 18Z" fill="currentColor"/>
    </svg>';

    global $thisfile;
    blog_init_db();
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
    $site_key_hcaptcha = get_setting($db, 'hcaptcha_site_key');
    $site_key_recaptcha = get_setting($db, 'recaptcha_site_key');
    $use_recaptcha = get_setting($db, 'use_recaptcha') === '1';
    $disable_captcha = get_setting($db, 'disable_captcha') === '1';
    $posts_per_page = (int) get_setting($db, 'posts_per_page') ?: 10;
    $output = '';



    if ($slug || (isset($_GET['post']) && !empty($_GET['post']))) {
        // Existing single post view logic remains unchanged
        $post_slug = $slug ?: $_GET['post'];
        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND (p.published = 1 OR (p.published = 0 AND p.scheduled_date IS NOT NULL AND p.scheduled_date <= ?))");
        $stmt->bindValue(1, $post_slug, SQLITE3_TEXT);
        $stmt->bindValue(2, time(), SQLITE3_INTEGER);
        $result = $stmt->execute()->fetchArray();



        if ($result) {
            global $metad, $SITEURL, $SITENAME, $title;
            $metad = $result['metadane'];
            $title = "{$result['title']}";

            $tags_stmt = $db->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
            $tags_stmt->bindValue(1, $result['id'], SQLITE3_INTEGER);
            $tags_result = $tags_stmt->execute();
            $tags = [];
            while ($tag = $tags_result->fetchArray()) {
                $tags[] = $tag['name'];
            }
            $tags_str = !empty($tags) ? '<p class="tags" style="display:none">' . i18n_r('simpleBlog/TAGS') . ': ' . implode(', ', $tags) . '</p>' : '';

            $output .= "<h1>{$result['title']}</h1>";
            if (get_setting($db, 'use_slug_routing')) {
                $output .= "<p><small>$timeIcon " . date('Y-m-d', $result['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/category/" . checkCategory($result['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$result['category_name']} </a></small></p>";

            } else {
                $output .= "<p><small>$timeIcon " . date('Y-m-d', $result['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&category=" . checkCategory($result['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$result['category_name']} </a></small></p>";
            }



            if ($result['cover_photo']) {
                $output .= "<img class='blogcover' src='" . $GLOBALS['SITEURL'] . "{$result['cover_photo']}' alt='{$result['title']}' style='max-width:100%'>";
            }
            $output .= "<div class='blogcontent'>{$result['content']}</div>";
            $output .= "{$tags_str}";

            if (get_setting($db, 'show_comments')) {
                $output .= '<h3>' . i18n_r('simpleBlog/ADD_COMMENT') . '</h3>';
                $output .= '<form method="post" class="comform">';
                $output .= '<input type="hidden" name="action" value="add_comment">';
                $output .= '<input type="hidden" name="post_id" value="' . $result['id'] . '">';
                $output .= i18n_r('simpleBlog/NAME') . ': <input type="text" name="author" required><br>';
                $output .= i18n_r('simpleBlog/EMAIL') . ': <input type="email" name="email" required><br>';
                $output .= i18n_r('simpleBlog/COMMENT') . ': <textarea name="content" required></textarea><br>';
                if (!$disable_captcha) {
                    if ($use_recaptcha && $site_key_recaptcha) {
                        $output .= '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($site_key_recaptcha) . '"></div>';
                        $output .= '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
                    } elseif ($site_key_hcaptcha) {
                        $output .= '<div class="h-captcha" data-sitekey="' . htmlspecialchars($site_key_hcaptcha) . '"></div>';
                        $output .= '<script src="https://js.hcaptcha.com/1/api.js" async defer></script>';
                    }
                }
                $output .= '<input type="submit" value="' . i18n_r('simpleBlog/ADD_COMMENT') . '">';
                $output .= '</form>';

                $output .= '<h3>' . i18n_r('simpleBlog/COMMENTS') . '</h3>';
                $stmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND approved = 1 ORDER BY date DESC");
                $stmt->bindValue(1, $result['id'], SQLITE3_INTEGER);
                $comments = $stmt->execute();
                while ($comment = $comments->fetchArray()) {
                    $output .= "<div class='comitem'>";
                    $output .= "<p><strong>{$comment['author']}</strong> (" . date('Y-m-d', $comment['date']) . ")</p>";
                    $output .= "<p>{$comment['content']}</p>";
                    $output .= "</div>";
                }
            }
        }
    } elseif (isset($_GET['category']) && !empty($_GET['category'])) {


        // New category view logic
        $category_slug = $_GET['category'];
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $posts_per_page;

        // Get category name for display
        $cat_stmt = $db->prepare("SELECT name FROM categories WHERE slug = ?");
        $cat_stmt->bindValue(1, $category_slug, SQLITE3_TEXT);
        $category_name = $cat_stmt->execute()->fetchArray()['name'] ?? 'Unknown Category';

        $total_stmt = $db->prepare("SELECT COUNT(*) FROM posts p JOIN categories c ON p.category_id = c.id WHERE c.slug = ? AND (p.published = 1 OR (p.published = 0 AND p.scheduled_date IS NOT NULL AND p.scheduled_date <= ?))");
        $total_stmt->bindValue(1, $category_slug, SQLITE3_TEXT);
        $total_stmt->bindValue(2, time(), SQLITE3_INTEGER);
        $total_posts = $total_stmt->execute()->fetchArray(SQLITE3_NUM)[0];
        $total_pages = ceil($total_posts / $posts_per_page);

        // Fetch posts for this category
        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p JOIN categories c ON p.category_id = c.id WHERE c.slug = ? AND (p.published = 1 OR (p.published = 0 AND p.scheduled_date IS NOT NULL AND p.scheduled_date <= ?)) ORDER BY p.date DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $category_slug, SQLITE3_TEXT);
        $stmt->bindValue(2, time(), SQLITE3_INTEGER);
        $stmt->bindValue(3, $posts_per_page, SQLITE3_INTEGER);
        $stmt->bindValue(4, $offset, SQLITE3_INTEGER);
        $results = $stmt->execute();

        $output .= "<h1> {$category_name}</h1>";

        while ($row = $results->fetchArray()) {
            $tags_stmt = $db->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
            $tags_stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
            $tags_result = $tags_stmt->execute();
            $tags = [];
            while ($tag = $tags_result->fetchArray()) {
                $tags[] = $tag['name'];
            }
            $tags_str = !empty($tags) ? ' | ' . i18n_r('simpleBlog/TAGS') . ': ' . implode(', ', $tags) : '';

            $output .= "<div class='blogitem" . ($row['cover_photo'] ? ' withcover' : '') . "'>";
            if ($row['cover_photo']) {
                if (get_setting($db, 'use_slug_routing')) {
                    $output .= "<a href='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/{$row['slug']}'> <img class='blogcover' src='" . $GLOBALS['SITEURL'] . "{$row['cover_photo']}' alt='{$row['title']}' ></a>";
                } else {
                    $output .= "<a href='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&post={$row['slug']}'> <img src='" . $GLOBALS['SITEURL'] . "{$row['cover_photo']}' alt='{$row['title']}' class='blogcover'></a>";
                }
            }



            if (get_setting($db, 'use_slug_routing')) {
                $output .= "<div class='blogitem-content'><h2><a href='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/{$row['slug']}'>{$row['title']}</a></h2>";
                $output .= "<p><small>$timeIcon " . date('Y-m-d', $row['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/category/" . checkCategory($row['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$row['category_name']} </a></small></p>";

            } else {
                $output .= "<div class='blogitem-content'><h2><a href='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&post={$row['slug']}'>{$row['title']}</a></h2>";
                $output .= "<p><small>$timeIcon " . date('Y-m-d', $row['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&category=" . checkCategory($row['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$row['category_name']} </a></small></p>";
            }


            $output .= "<div>" . substr(strip_tags($row['description']), 0, 600) . "...</div></div>";
            $output .= "</div>";
        }

        // Pagination for category view
        if ($total_pages > 1) {
            $output .= '<div style="margin-top: 20px;">';
            if ($page > 1) {
                $output .= "<a href='" . $SITEURL . get_setting($db, 'root_page') . "?category={$category_slug}&page=" . ($page - 1) . "' style='margin-right: 10px;'>" . i18n_r('simpleBlog/PREVIOUS') . "</a>";
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                $output .= "<a href='" . $SITEURL . get_setting($db, 'root_page') . "?category={$category_slug}&page=$i' style='margin: 0 5px;" . ($i == $page ? "font-weight:bold;" : "") . "'>$i</a>";
            }
            if ($page < $total_pages) {
                $output .= "<a href='" . $SITEURL . get_setting($db, 'root_page') . "?category={$category_slug}&page=" . ($page + 1) . "' style='margin-left: 10px;'>" . i18n_r('simpleBlog/NEXT') . "</a>";
            }
            $output .= '</div>';
        }
    } else {
        // Existing blog listing logic remains unchanged
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $posts_per_page;

        $total_stmt = $db->querySingle("SELECT COUNT(*) FROM posts WHERE published = 1 OR (published = 0 AND scheduled_date IS NOT NULL AND scheduled_date <= " . time() . ")");
        $total_pages = ceil($total_stmt / $posts_per_page);

        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.published = 1 OR (p.published = 0 AND p.scheduled_date IS NOT NULL AND p.scheduled_date <= ?)) ORDER BY p.date DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, time(), SQLITE3_INTEGER);
        $stmt->bindValue(2, $posts_per_page, SQLITE3_INTEGER);
        $stmt->bindValue(3, $offset, SQLITE3_INTEGER);
        $results = $stmt->execute();

        while ($row = $results->fetchArray()) {
            $tags_stmt = $db->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
            $tags_stmt->bindValue(1, $row['id'], SQLITE3_INTEGER);
            $tags_result = $tags_stmt->execute();
            $tags = [];
            while ($tag = $tags_result->fetchArray()) {
                $tags[] = $tag['name'];
            }
            $tags_str = !empty($tags) ? ' | ' . i18n_r('simpleBlog/TAGS') . ': ' . implode(', ', $tags) : '';

            $output .= "<div class='blogitem" . ($row['cover_photo'] ? ' withcover' : '') . "'>";
            if ($row['cover_photo']) {
                if (get_setting($db, 'use_slug_routing')) {
                    $output .= "<a href='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/{$row['slug']}'> <img src='" . $GLOBALS['SITEURL'] . "{$row['cover_photo']}' alt='{$row['title']}'  class='blogcover'></a>";
                } else {
                    $output .= "<a href='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&post={$row['slug']}'> <img src='" . $GLOBALS['SITEURL'] . "{$row['cover_photo']}' alt='{$row['title']}' class='blogcover'></a>";
                }
            }


            if (get_setting($db, 'use_slug_routing')) {
                $output .= "<div class='blogitem-content'><h2><a href='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/{$row['slug']}'>{$row['title']}</a></h2>";
                $output .= "<p><small>$timeIcon " . date('Y-m-d', $row['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/category/" . checkCategory($row['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$row['category_name']} </a></small></p>";

            } else {
                $output .= "<div class='blogitem-content'><h2><a href='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&post={$row['slug']}'>{$row['title']}</a></h2>";
                $output .= "<p><small>$timeIcon " . date('Y-m-d', $row['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&category=" . checkCategory($row['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$row['category_name']} </a></small></p>";
            }



            $output .= "<div>" . substr(strip_tags($row['description']), 0, 600) . "...</div></div>";
            $output .= "</div>";
        }

        if ($total_pages > 1) {
            $output .= '<div style="margin-top: 20px;">';
            if ($page > 1) {
                $output .= "<a href='" . $SITEURL . get_setting($db, 'root_page') . "?page=" . ($page - 1) . "' style='margin-right: 10px;'>" . i18n_r('simpleBlog/PREVIOUS') . "</a>";
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                $output .= "<a href='" . $SITEURL . get_setting($db, 'root_page') . "?page=$i' style='margin: 0 5px;" . ($i == $page ? "font-weight:bold;" : "") . "'>$i</a>";
            }
            if ($page < $total_pages) {
                $output .= "<a href='" . $SITEURL . get_setting($db, 'root_page') . "?page=" . ($page + 1) . "' style='margin-left: 10px;'>" . i18n_r('simpleBlog/NEXT') . "</a>";
            }
            $output .= '</div>';
        }
    }

    if (isset($_POST['action']) && $_POST['action'] == 'add_comment') {
        // Existing comment submission logic remains unchanged
        $secret_key_hcaptcha = get_setting($db, 'hcaptcha_secret_key');
        $secret_key_recaptcha = get_setting($db, 'recaptcha_secret_key');
        $success = true;

        if (!$disable_captcha) {
            if ($use_recaptcha && $secret_key_recaptcha) {
                $response = $_POST['g-recaptcha-response'];
                $verify_url = "https://www.google.com/recaptcha/api/siteverify";
                $data = array('secret' => $secret_key_recaptcha, 'response' => $response);
                $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
                $context = stream_context_create($options);
                $verify = file_get_contents($verify_url, false, $context);
                $captcha_result = json_decode($verify);
                $success = $captcha_result->success;
            } elseif ($site_key_hcaptcha && $secret_key_hcaptcha) {
                $response = $_POST['h-captcha-response'];
                $verify_url = "https://hcaptcha.com/siteverify";
                $data = array('secret' => $secret_key_hcaptcha, 'response' => $response);
                $options = array('http' => array('header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)));
                $context = stream_context_create($options);
                $verify = file_get_contents($verify_url, false, $context);
                $captcha_result = json_decode($verify);
                $success = $captcha_result->success;
            }
        }

        if ($success) {
            $stmt = $db->prepare("INSERT INTO comments (post_id, author, email, content, date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $_POST['post_id'], SQLITE3_INTEGER);
            $stmt->bindValue(2, $_POST['author'], SQLITE3_TEXT);
            $stmt->bindValue(3, $_POST['email'], SQLITE3_TEXT);
            $stmt->bindValue(4, $_POST['content'], SQLITE3_TEXT);
            $stmt->bindValue(5, time(), SQLITE3_INTEGER);
            $stmt->execute();
            $output .= '<p>' . i18n_r('simpleBlog/COMMENT_ADDED') . '</p>';
        } else {
            $output .= '<p>' . i18n_r('simpleBlog/CAPTCHA_FAILED') . '</p>';
        }
    }

    $db->close();
    return $output;
}

function blog_theme_content()
{

    global $content;
    global $desc;
    global $metad;


    blog_init_db();
    $db = new SQLite3(GSDATAOTHERPATH . 'blog.db');


    $use_slug_routing = get_setting($db, 'use_slug_routing') === '1';
    $current_page = strtolower(return_page_slug());

    $newcontent = get_blog_content();
    if ($current_page == get_setting($db, 'root_page')) {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        $parts = explode('/', $uri);

        if (count($parts) >= 2 && $parts[0] === get_setting($db, 'root_page') && !empty($parts[1])) {
            $slug = $parts[1];
            $blog_output = get_blog_content($slug);
            if ($blog_output) {
                $newcontent = $blog_output;
            }
        }

        $content = $newcontent;
    }

    $db->close();
}
