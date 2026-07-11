<?php
$thisfile = basename(__FILE__, ".php");

i18n_merge('simpleBlog') || i18n_merge('simpleBlog', 'en_US');

register_plugin(
	$thisfile,
	'SimpleBlog',
	'3.5',
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
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/TAGS'), '?id=simpleBlog&blog_admin&tab=tags'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/SETTINGS'), '?id=simpleBlog&blog_admin&tab=settings'));
add_action('blog-sidebar', 'createSideMenu', array($thisfile, i18n_r('simpleBlog/SNIPPET'), '?id=simpleBlog&blog_admin&tab=snippet'));

add_action('index-pretemplate', 'blog_theme_content');
add_action('theme-header', 'styleTheme');

// Early AJAX intercept for the image picker (browse uploads / upload new).
if (!empty($_REQUEST['sb_picker_action']) && !is_frontend()) {
	sb_picker_handle_ajax($_REQUEST['sb_picker_action']);
	exit; // handle_ajax() always exit()s - safety net
}

function styleTheme(){
	$db_file = GSDATAOTHERPATH . 'blog.db';

	if (file_exists($db_file)) {
		$db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
		$checkStyle = $db->prepare("SELECT * FROM settings WHERE name = 'style_theme'");
		$result = $checkStyle->execute();
		$style = $result->fetchArray(SQLITE3_ASSOC);

		global $SITEURL, $SITENAME;
		echo '
	<link rel="stylesheet" href="' . $SITEURL . 'plugins/simpleBlog/css/' . $style['value'] . '.css?v=' . rand() . '">';

		if (get_setting($db, 'enable_rss') !== '0') {
			$feed_title = htmlspecialchars(($SITENAME ?: 'Blog') . ' RSS Feed', ENT_QUOTES, 'UTF-8');
			echo '
	<link rel="alternate" type="application/rss+xml" title="' . $feed_title . '" href="' . $SITEURL . 'rss.xml">
';
		}
	};
}

function blog_init_db(){
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
			gallery TEXT,
			updated_at INTEGER,
			FOREIGN KEY (category_id) REFERENCES categories(id)
		)");

		//new metadata
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

		// Add default category in user language
		$uncategorized_names = [
			'de' => 'Ohne Kategorie',
			'en' => 'Uncategorized',
			'es' => 'Sin categoría',
			'pl' => 'Bez kategorii',
			'ru' => 'Без категории',
			'uk' => 'Без категорії'
		];
		$current_lang = function_exists('return_i18n_lang') ? return_i18n_lang() : 'en';
		$uncategorized_name = isset($uncategorized_names[$current_lang]) ? $uncategorized_names[$current_lang] : 'Uncategorized';

		$stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, 'uncategorized')");
		$stmt->bindValue(1, $uncategorized_name, SQLITE3_TEXT);
		$stmt->execute();

		$db->close();
	} else {
		$db = new SQLite3($db_file);
		@$db->exec("ALTER TABLE posts ADD COLUMN description TEXT");
		@$db->exec("ALTER TABLE posts ADD COLUMN metadata TEXT");
		@$db->exec("ALTER TABLE posts ADD COLUMN gallery TEXT");
		@$db->exec("ALTER TABLE posts ADD COLUMN updated_at INTEGER");
		@$db->exec("UPDATE posts SET updated_at = date WHERE updated_at IS NULL");
		$db->close();
	}

	$cover_folder = GSDATAUPLOADPATH . 'blog_covers/';
	if (!file_exists($cover_folder)) {

		mkdir($cover_folder, 0755, true);
	}

	// If rss.xml doesn't exist yet (fresh install/upgrade), generate it now
	if (!file_exists(GSROOTPATH . 'rss.xml')) {
		global $SITEURL;
		$rss_db = new SQLite3($db_file);
		if (get_setting($rss_db, 'enable_rss') !== '0') {
			sb_write_rss_file($rss_db, $SITEURL);
		}
		$rss_db->close();
	}
}

function generate_slug($title, $db, $table = 'posts', $exclude_id = null){
	// Map of special characters to their base equivalents
	$char_map = [
		// Polish characters
		'ą' => 'a',
		'ć' => 'c',
		'ę' => 'e',
		'ł' => 'l',
		'ń' => 'n',
		'ó' => 'o',
		'ś' => 's',
		'ź' => 'z',
		'ż' => 'z',
		'Ą' => 'A',
		'Ć' => 'C',
		'Ę' => 'E',
		'Ł' => 'L',
		'Ń' => 'N',
		'Ó' => 'O',
		'Ś' => 'S',
		'Ź' => 'Z',
		'Ż' => 'Z',
		// German characters
		'ä' => 'ae',
		'ö' => 'oe',
		'ü' => 'ue',
		'ß' => 'ss',
		'Ä' => 'Ae',
		'Ö' => 'Oe',
		'Ü' => 'Ue',
		// Spanish characters
		'á' => 'a',
		'é' => 'e',
		'í' => 'i',
		'ó' => 'o',
		'ú' => 'u',
		'ñ' => 'n',
		'ü' => 'u',
		'Á' => 'A',
		'É' => 'E',
		'Í' => 'I',
		'Ó' => 'O',
		'Ú' => 'U',
		'Ñ' => 'N',
		'Ü' => 'U',
		// Russian characters
		'а' => 'a',
		'б' => 'b',
		'в' => 'v',
		'г' => 'g',
		'д' => 'd',
		'е' => 'e',
		'ё' => 'yo',
		'ж' => 'zh',
		'з' => 'z',
		'и' => 'i',
		'й' => 'y',
		'к' => 'k',
		'л' => 'l',
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => 's',
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'kh',
		'ц' => 'ts',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'sch',
		'ъ' => '',
		'ы' => 'y',
		'ь' => '',
		'э' => 'e',
		'ю' => 'yu',
		'я' => 'ya',
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Ё' => 'Yo',
		'Ж' => 'Zh',
		'З' => 'Z',
		'И' => 'I',
		'Й' => 'Y',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'Kh',
		'Ц' => 'Ts',
		'Ч' => 'Ch',
		'Ш' => 'Sh',
		'Щ' => 'Sch',
		'Ъ' => '',
		'Ы' => 'Y',
		'Ь' => '',
		'Э' => 'E',
		'Ю' => 'Yu',
		'Я' => 'Ya',
		// Ukrainian characters
		'ґ' => 'g',
		'і' => 'i',
		'ї' => 'yi',
		'є' => 'ye',
		'Ґ' => 'G',
		'І' => 'I',
		'Ї' => 'Yi',
		'Є' => 'Ye'
	];

	// Replace special characters with their base equivalents
	$slug = strtr($title, $char_map);

	// Convert to lowercase and remove all special characters except spaces and hyphens
	$slug = strtolower(preg_replace('/[^A-Za-z0-9\s-]/', '', $slug));

	// Replace spaces with hyphens, remove multiple consecutive hyphens, trim stray edge hyphens
	$slug = preg_replace('/\s+/', '-', trim($slug));
	$slug = preg_replace('/-+/', '-', $slug);
	$slug = trim($slug, '-');

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

function get_setting($db, $name){
	$stmt = $db->prepare("SELECT value FROM settings WHERE name = ?");
	$stmt->bindValue(1, $name, SQLITE3_TEXT);
	$result = $stmt->execute()->fetchArray();
	return $result ? $result['value'] : '';
}

/**
 * Sanitize the gallery JSON submitted from the admin form.
 * @param string $raw Raw JSON string from $_POST['gallery']
 * @return string Clean JSON array (always valid JSON, defaults to '[]')
 */
function sb_sanitize_gallery_input($raw){
	$decoded = json_decode((string)$raw, true);
	if (!is_array($decoded)) return '[]';

	$clean = [];
	foreach ($decoded as $item) {
		if (!is_string($item)) continue;
		$item = ltrim(trim($item), '/');
		if ($item === '' || strpos($item, '..') !== false) continue;
		if (strpos($item, 'data/uploads/') !== 0) continue;
		$clean[] = $item;
	}
	return json_encode(array_values(array_unique($clean)));
}

/**
 * Renders a live "X / recommended" character counter beneath a textarea.
 * @param string $textarea_id  id of the textarea to watch (must already exist in the DOM)
 * @param int    $recommended  recommended max character count
 * @return string HTML (a small counter element + inline script)
 */
function sb_render_char_counter($textarea_id, $recommended = 160){
	$id = htmlspecialchars($textarea_id, ENT_QUOTES, 'UTF-8');
	$counter_id = $id . '-counter';

	$html  = '<div class="sb-char-counter" id="' . $counter_id . '"></div>';
	$html .= '<style>.sb-char-counter{font-size:.78rem;color:#6b7280;margin:.25rem 0 .5rem}.sb-char-counter.sb-char-warn{color:#b45309}.sb-char-counter.sb-char-over{color:#c0392b}</style>';
	$html .= '<script>
	(function(){
		var ta = document.getElementById(' . json_encode($id) . ');
		var counter = document.getElementById(' . json_encode($counter_id) . ');
		var recommended = ' . (int)$recommended . ';
		if (!ta || !counter) return;

		function update(){
			var len = ta.value.length;
			var remaining = recommended - len;
			var text = len + " / " + recommended + " " + ' . json_encode(i18n_r('simpleBlog/CHARS_LABEL')) . ';
			if (remaining < 0) {
				text += " (" + Math.abs(remaining) + " " + ' . json_encode(i18n_r('simpleBlog/CHARS_OVER')) . ' + ")";
				counter.className = "sb-char-counter sb-char-over";
			} else if (remaining <= 20) {
				counter.className = "sb-char-counter sb-char-warn";
			} else {
				counter.className = "sb-char-counter";
			}
			counter.textContent = text;
		}

		ta.addEventListener("input", update);
		update();
	})();
	</script>';

	return $html;
}

// ─────────────────────────────────────────────────────────────────────────────
// IMAGE PICKER  -  uploads / thumbnails / browsing
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Resolves a stored image value to a browser-usable URL.
 * @param string $path Stored value - either a relative path or a full URL
 * @param string $SITEURL Site base URL
 * @return string
 */
function sb_resolve_image_url($path, $SITEURL) {
	$path = (string)$path;
	if ($path === '') return '';
	if (preg_match('#^(https?:)?//#i', $path)) return $path;
	return $SITEURL . $path;
}

function sb_thumb_url($filename, $SITEURL) {
	if (strpos($filename, 'data/uploads/') === 0) {
		$filename = substr($filename, strlen('data/uploads/'));
	}
	$parts      = explode('/', $filename);
	$basename   = array_pop($parts);
	$subdir     = count($parts) ? implode('/', $parts) . '/' : '';
	$thumb_file = GSTHUMBNAILPATH . $subdir . 'thumbnail.' . $basename;
	if (file_exists($thumb_file)) {
		return $SITEURL . 'data/thumbs/' . implode('/', array_map('rawurlencode', explode('/', $subdir . 'thumbnail.' . $basename)));
	}
	return $SITEURL . 'data/uploads/' . implode('/', array_map('rawurlencode', explode('/', $filename)));
}

// Recursively list every image in the GS uploads folder, including subfolders.
function sb_get_upload_images($SITEURL) {
	$images  = [];
	$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
	$base    = GSDATAUPLOADPATH;

	$scan = function ($dir, $rel) use (&$scan, &$images, $allowed, $SITEURL) {
		if (!is_dir($dir)) return;
		foreach (scandir($dir) as $file) {
			if ($file[0] === '.') continue;
			$full = $dir . $file;
			$path = $rel ? $rel . '/' . $file : $file;
			if (is_dir($full)) {
				$scan($full . '/', $path);
			} else {
				$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				if (!in_array($ext, $allowed)) continue;
				$images[] = [
					'filename' => $path,
					'url'      => $SITEURL . 'data/uploads/' . implode('/', array_map('rawurlencode', explode('/', $path))),
					'thumb'    => sb_thumb_url($path, $SITEURL),
					'folder'   => $rel ?: '',
					'basename' => $file,
				];
			}
		}
	};

	$scan($base, '');
	usort($images, fn($a, $b) => strcmp($a['filename'], $b['filename']));
	return $images;
}

// ─────────────────────────────────────────────────────────────────────────────
// PICKER AJAX ENDPOINTS  (only reached via the early sb_picker_action intercept)
// ─────────────────────────────────────────────────────────────────────────────

function sb_picker_handle_ajax($action) {
	header('Content-Type: application/json');
	global $SITEURL;

	switch ($action) {

		case 'get_uploads': {
			echo json_encode(['ok' => true, 'images' => sb_get_upload_images($SITEURL)]);
			exit;
		}

		case 'upload_image': {
			if (empty($_FILES['image']['name'])) { echo json_encode(['ok' => false, 'msg' => 'No file']); exit; }

			$file         = $_FILES['image'];
			$allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
			$mime         = mime_content_type($file['tmp_name']);
			if (!in_array($mime, $allowed_mime)) { echo json_encode(['ok' => false, 'msg' => 'Unsupported file type']); exit; }
			if ($file['size'] > 8 * 1024 * 1024) { echo json_encode(['ok' => false, 'msg' => 'File exceeds 8 MB']); exit; }

			$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$safe_ext = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
			// Sanitise original filename: lowercase, replace spaces/special chars with hyphens
			$base = strtolower(pathinfo($file['name'], PATHINFO_FILENAME));
			$base = preg_replace('/[^a-z0-9._-]/', '-', $base);
			$base = preg_replace('/-+/', '-', trim($base, '-'));
			$base = $base ?: 'image';
			$filename = $base . '.' . $safe_ext;
			// Handle filename collisions - append -1, -2, etc. if the file already exists
			if (file_exists(GSDATAUPLOADPATH . $filename)) {
				$i = 1;
				while (file_exists(GSDATAUPLOADPATH . $base . '-' . $i . '.' . $safe_ext)) { $i++; }
				$filename = $base . '-' . $i . '.' . $safe_ext;
			}

			$dest = GSDATAUPLOADPATH . $filename;
			if (!move_uploaded_file($file['tmp_name'], $dest)) {
				echo json_encode(['ok' => false, 'msg' => 'Upload failed']); exit;
			}
			
			$imgmanip = GSADMININCPATH . 'imagemanipulation.php';
			if (file_exists($imgmanip)) {
				require_once($imgmanip);
				if (function_exists('genStdThumb')) genStdThumb('', $filename);
			}

			echo json_encode(['ok' => true, 'image' => [
				'filename' => $filename,
				'url'      => $SITEURL . 'data/uploads/' . rawurlencode($filename),
				'thumb'    => sb_thumb_url($filename, $SITEURL),
				'folder'   => '',
				'basename' => $filename,
			]]);
			exit;
		}

		default:
			echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
			exit;
	}
}

/**
 * Renders the shared image picker modal (Browse Uploads / Upload New tabs)
 * @param string $SITEURL Site base URL
 * @return string HTML
 */
function sb_render_picker_modal($SITEURL){
	$html = '<div class="sb-picker-modal" id="sb-picker-modal" style="display:none;">
		<div class="sb-picker-inner">
			<div class="sb-picker-head">
				<h4 id="sb-picker-title">' . i18n_r('simpleBlog/PICKER_TITLE') . '</h4>
				<button type="button" class="sb-picker-close" id="sb-picker-close">&times;</button>
			</div>

			<div class="sb-picker-tabs">
				<button type="button" class="sb-picker-tab active" data-tab="browse">' . i18n_r('simpleBlog/PICKER_BROWSE') . '</button>
				<button type="button" class="sb-picker-tab" data-tab="upload">' . i18n_r('simpleBlog/PICKER_UPLOAD') . '</button>
				<button type="button" class="sb-picker-tab" data-tab="remote" id="sb-picker-tab-remote" style="display:none;">' . i18n_r('simpleBlog/PICKER_REMOTE') . '</button>
			</div>

			<div class="sb-picker-pane" id="sb-picker-browse">
				<div class="sb-picker-crumb" id="sb-picker-crumb"></div>
				<div class="sb-picker-search-row">
					<input type="text" id="sb-picker-search" placeholder="' . htmlspecialchars(strip_tags(i18n_r('simpleBlog/PICKER_FILTER')), ENT_QUOTES, 'UTF-8') . '">
				</div>
				<div class="sb-picker-grid" id="sb-picker-grid"><div class="sb-picker-loading">' . i18n_r('simpleBlog/PICKER_LOADING') . '</div></div>
			</div>

			<div class="sb-picker-pane" id="sb-picker-upload" style="display:none;">
				<label class="sb-picker-upload-area" for="sb-picker-upload-input">
					<span>' . i18n_r('simpleBlog/PICKER_CHOOSE_FILES') . '</span>
				</label>
				<input type="file" id="sb-picker-upload-input" accept="image/png,image/jpeg,image/gif,image/webp" multiple style="display:none;">
				<div class="sb-picker-upload-status" id="sb-picker-upload-status"></div>
			</div>

			<div class="sb-picker-pane" id="sb-picker-remote" style="display:none;">
				<label class="sb-picker-remote-label" for="sb-picker-remote-input">' . i18n_r('simpleBlog/PICKER_REMOTE_LABEL') . '</label>
				<input type="text" id="sb-picker-remote-input" class="sb-picker-remote-input" placeholder="https://example.com/image.jpg">
				<button type="button" class="sb-picker-remote-use">' . i18n_r('simpleBlog/PICKER_REMOTE_USE') . '</button>
				<div class="sb-picker-remote-status" id="sb-picker-remote-status"></div>
			</div>
		</div>
	</div>';

	$html .= '<style>
	.sb-picker-modal{position:fixed;inset:0;background:rgba(20,20,30,.55);z-index:99999;display:flex;align-items:flex-start;justify-content:center;padding:3rem 1rem 1rem;}
	.sb-picker-inner{background:#fff;width:min(660px,92vw);max-height:calc(100vh - 4rem);border-radius:8px;overflow-y:auto;display:flex;flex-direction:column}
	.sb-picker-head{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.25rem;border-bottom:1px solid #f0f0f0;position:sticky;top:0;background:#fff;z-index:1}
	.sb-picker-head h4{margin:0;font-size:.9375rem}
	.sb-picker-close{border:none;background:none;font-size:22px;line-height:1;cursor:pointer;color:#6b7280}

	.sb-picker-tabs{display:flex;gap:0;border-bottom:1px solid #e5e7eb;padding:0 1.25rem;margin-top:.25rem}
	.sb-picker-tab{display:inline-flex;align-items:center;gap:.4rem;background:none;border:none;border-bottom:2px solid transparent;padding:.6rem 1rem;font-size:.875rem;font-weight:600;color:#6b7280;cursor:pointer;margin-bottom:-1px}
	.sb-picker-tab.active{color:#2764e7;border-bottom-color:#2764e7}
	.sb-picker-tab:hover:not(.active){color:#374151}
	.sb-picker-pane{padding:1.25rem}

	.sb-picker-crumb{font-size:.8125rem;color:#6b7280;margin-bottom:.6rem;display:flex;align-items:center;flex-wrap:wrap;gap:.2rem;min-height:1.4rem}
	.sb-picker-crumb-item{cursor:pointer;color:#2764e7;font-weight:600;padding:.1rem .2rem;border-radius:3px}
	.sb-picker-crumb-item:hover{background:#f0f4ff}
	.sb-picker-crumb-item.active{color:#374151;cursor:default;font-weight:400}
	.sb-picker-crumb-item.active:hover{background:none}

	.sb-picker-search-row{margin-bottom:.75rem}
	.sb-picker-search-row input{width:100%;box-sizing:border-box;padding:.45rem .75rem;border:1px solid #d0d5dd;border-radius:6px;font-size:.875rem;font-family:inherit}
	.sb-picker-search-row input:focus{outline:none;border-color:#2764e7;box-shadow:0 0 0 3px rgba(39,100,231,.1)}

	.sb-picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:.6rem;max-height:360px;overflow-y:auto;padding:.25rem}
	.sb-picker-img{cursor:pointer;border:2px solid transparent;border-radius:7px;overflow:hidden;background:#f4f6f9;display:flex;flex-direction:column}
	.sb-picker-img:hover{border-color:#2764e7;box-shadow:0 2px 8px rgba(39,100,231,.2)}
	.sb-picker-img img{width:100%;aspect-ratio:1;object-fit:cover;display:block}
	.sb-picker-name{font-size:.67rem;color:#6b7280;padding:.2rem .3rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:center;background:#f9fafb}
	.sb-picker-empty{text-align:center;color:#9ca3af;padding:2rem;font-size:.875rem;grid-column:1/-1}

	.sb-picker-folder{cursor:pointer;border:2px solid #e5e7eb;border-radius:7px;background:#fffbeb;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:.6rem .4rem;gap:.3rem;position:relative}
	.sb-picker-folder:hover{border-color:#f59e0b;background:#fef3c7}
	.sb-picker-folder .sb-picker-name{font-size:.72rem;color:#92400e;font-weight:600;text-align:center;background:none}
	.sb-picker-folder-count{position:absolute;top:4px;right:5px;font-size:.65rem;color:#92400e;font-weight:700;background:#fde68a;border-radius:3px;padding:0 .25rem}

	.sb-picker-upload-area{display:flex;align-items:center;justify-content:center;border:2px dashed #d0d5dd;border-radius:9px;padding:2.5rem 1.5rem;text-align:center;background:#fafafa;cursor:pointer;font-size:.875rem;color:#6b7280;font-weight:600}
	.sb-picker-upload-area:hover{border-color:#2764e7;background:#f8faff;color:#2764e7}
	.sb-picker-upload-status{font-size:.8rem;color:#6b7280;margin-top:.75rem}
	
	.sb-picker-remote-label{display:block;font-size:.8125rem;color:#374151;font-weight:600;margin-bottom:.5rem}
	.sb-picker-remote-input{width:100%;box-sizing:border-box;padding:.55rem .75rem;border:1px solid #d0d5dd;border-radius:6px;font-size:.875rem;font-family:inherit;margin-bottom:.75rem}
	.sb-picker-remote-input:focus{outline:none;border-color:#2764e7;box-shadow:0 0 0 3px rgba(39,100,231,.1)}
	.sb-picker-remote-use{background:#2764e7;color:#fff;border:none;padding:.55rem 1.2rem;border-radius:6px;font-size:.875rem;font-weight:600;cursor:pointer}
	.sb-picker-remote-use:hover{background:#0094f0}
	.sb-picker-remote-status{font-size:.8rem;color:#c0392b;margin-top:.6rem}
	.sb-picker-loading{grid-column:1/-1;text-align:center;padding:2.5rem;color:#9ca3af;font-size:.875rem}
	</style>';

	$html .= '<script>
	(function(){
		if (window.sbOpenPicker) return; // only wire this up once per page

		var ajaxUrl = window.location.pathname + window.location.search;
		var modal   = document.getElementById("sb-picker-modal");
		var grid    = document.getElementById("sb-picker-grid");
		var crumb   = document.getElementById("sb-picker-crumb");
		var search  = document.getElementById("sb-picker-search");
		var uploadIn = document.getElementById("sb-picker-upload-input");
		var uploadSt = document.getElementById("sb-picker-upload-status");
		var closeBtn = document.getElementById("sb-picker-close");
		var remoteTabBtn = document.getElementById("sb-picker-tab-remote");
		var remoteIn = document.getElementById("sb-picker-remote-input");
		var remoteSt = document.getElementById("sb-picker-remote-status");
		var remoteUseBtn = document.querySelector(".sb-picker-remote-use");

		var state = { mode: "cover_photo", images: [], folder: "" };

		function post(action, data) {
			var body = new URLSearchParams(Object.assign({ sb_picker_action: action }, data || {}));
			return fetch(ajaxUrl, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString() })
				.then(function(r){ return r.json(); });
		}

		function esc(s) {
			var d = document.createElement("div");
			d.textContent = s == null ? "" : s;
			return d.innerHTML;
		}

		function load(force) {
			if (state.images.length && !force) { render(); return; }
			grid.innerHTML = "<div class=\\"sb-picker-loading\\">" + ' . json_encode(i18n_r('simpleBlog/PICKER_LOADING')) . ' + "</div>";
			fetch(ajaxUrl + (ajaxUrl.indexOf("?") === -1 ? "?" : "&") + "sb_picker_action=get_uploads")
				.then(function(r){ return r.json(); })
				.then(function(res){
					state.images = res.images || [];
					render();
				});
		}

		function render() {
			var filter = (search ? search.value : "").toLowerCase().trim();
			var images, folders;

			if (filter) {
				images = state.images.filter(function(img){ return img.filename.toLowerCase().indexOf(filter) !== -1; });
				folders = [];
			} else {
				var seen = {};
				folders = [];
				images = [];
				state.images.forEach(function(img){
					if (state.folder && img.filename.indexOf(state.folder + "/") !== 0) return;
					var rel = state.folder ? img.filename.slice(state.folder.length + 1) : img.filename;
					var parts = rel.split("/");
					if (parts.length > 1) {
						var sub = (state.folder ? state.folder + "/" : "") + parts[0];
						if (!seen[sub]) { seen[sub] = true; folders.push(parts[0]); }
					} else {
						images.push(img);
					}
				});
			}

			if (crumb) {
				var html = "<svg style=\"vertical-align:middle;\" width=\"16\" height=\"16\" viewBox=\"0 0 24 24\" fill=\"#f59e0b\"><path d=\"M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z\"></path></svg> <span class=\\"sb-picker-crumb-item" + (state.folder === "" ? " active" : "") + "\\" data-nav=\\"\\">uploads</span>";
				if (state.folder) {
					var parts = state.folder.split("/"), built = "";
					parts.forEach(function(p, i){
						built = built ? built + "/" + p : p;
						html += " / <span class=\\"sb-picker-crumb-item" + (i === parts.length - 1 ? " active" : "") + "\\" data-nav=\\"" + esc(built) + "\\">" + esc(p) + "</span>";
					});
				}
				crumb.innerHTML = html;
			}

			if (!folders.length && !images.length) {
				grid.innerHTML = "<p class=\\"sb-picker-empty\\">" + ' . json_encode(i18n_r('simpleBlog/PICKER_NO_IMAGES')) . ' + "</p>";
				return;
			}

			var out = "";
			folders.sort().forEach(function(f){
				var fullPath = (state.folder ? state.folder + "/" : "") + f;
				var count = state.images.filter(function(img){ return img.filename.indexOf(fullPath + "/") === 0; }).length;
				out += "<div class=\\"sb-picker-folder\\" data-nav=\\"" + esc(fullPath) + "\\">"
					+ "<svg width=\\"28\\" height=\\"28\\" viewBox=\\"0 0 24 24\\" fill=\\"#f59e0b\\"><path d=\\"M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z\\"/></svg>"
					+ "<span class=\\"sb-picker-name\\">" + esc(f) + "</span>"
					+ "<span class=\\"sb-picker-folder-count\\">" + count + "</span>"
					+ "</div>";
			});
			images.forEach(function(img){
				out += "<div class=\\"sb-picker-img\\" data-pick=\\"" + esc(img.filename) + "\\" title=\\"" + esc(img.filename) + "\\">"
					+ "<img src=\\"" + esc(img.thumb) + "\\" alt=\\"" + esc(img.basename) + "\\" loading=\\"lazy\\">"
					+ "<span class=\\"sb-picker-name\\">" + esc(img.basename) + "</span>"
					+ "</div>";
			});
			grid.innerHTML = out;
		}

		function select(filename) {
			// Browse/upload hand this a bare uploads-relative path
			var isAbsolute = /^(https?:)?\/\//i.test(filename);
			var stored = isAbsolute ? filename : "data/uploads/" + filename;
			if (state.mode === "gallery") {
				if (typeof window.sbAddGalleryImage === "function") window.sbAddGalleryImage(stored);
			} else {
				var el = document.querySelector(".cover_photo");
				if (el) el.value = stored;
				var preview = document.querySelector(".cur_photo");
				if (preview) {
					preview.setAttribute("src", isAbsolute ? stored : (' . json_encode($SITEURL) . ' + stored));
					preview.style.display = "";
				}
				close();
			}
		}

		function open(mode) {
			state.mode = mode || "cover_photo";
			state.folder = "";
			if (search) search.value = "";
			if (uploadIn) uploadIn.multiple = (state.mode === "gallery");
			document.querySelectorAll(".sb-picker-tab").forEach(function(b, i){ b.classList.toggle("active", i === 0); });
			document.getElementById("sb-picker-browse").style.display = "";
			document.getElementById("sb-picker-upload").style.display = "none";
			document.getElementById("sb-picker-remote").style.display = "none";
			// Remote URL only makes sense for a single cover photo, not the gallery
			// picker (gallery values must stay within data/uploads/ to be sanitized).
			if (remoteTabBtn) remoteTabBtn.style.display = (state.mode === "cover_photo") ? "" : "none";
			if (remoteIn) remoteIn.value = "";
			if (remoteSt) remoteSt.textContent = "";
			if (modal) modal.style.display = "flex";
			load();
		}

		function close() {
			if (modal) modal.style.display = "none";
		}

		window.sbOpenPicker = open;

		if (closeBtn) closeBtn.addEventListener("click", close);
		if (modal) modal.addEventListener("click", function(e){ if (e.target === modal) close(); });

		document.querySelectorAll(".sb-picker-tab").forEach(function(btn){
			btn.addEventListener("click", function(){
				document.querySelectorAll(".sb-picker-tab").forEach(function(b){ b.classList.remove("active"); });
				btn.classList.add("active");
				var tab = btn.dataset.tab;
				document.getElementById("sb-picker-browse").style.display = tab === "browse" ? "" : "none";
				document.getElementById("sb-picker-upload").style.display = tab === "upload" ? "" : "none";
				document.getElementById("sb-picker-remote").style.display = tab === "remote" ? "" : "none";
			});
		});

		function useRemoteUrl() {
			var url = (remoteIn && remoteIn.value ? remoteIn.value : "").trim();
			if (!/^https?:\/\//i.test(url)) {
				if (remoteSt) remoteSt.textContent = ' . json_encode(i18n_r('simpleBlog/PICKER_REMOTE_INVALID')) . ';
				return;
			}
			if (remoteSt) remoteSt.textContent = "";
			select(url);
		}
		if (remoteUseBtn) remoteUseBtn.addEventListener("click", useRemoteUrl);
		if (remoteIn) remoteIn.addEventListener("keydown", function(e){
			if (e.key === "Enter") { e.preventDefault(); useRemoteUrl(); }
		});

		if (grid) grid.addEventListener("click", function(e){
			var folderEl = e.target.closest("[data-nav]");
			if (folderEl) { state.folder = folderEl.dataset.nav; if (search) search.value = ""; render(); return; }
			var imgEl = e.target.closest("[data-pick]");
			if (imgEl) select(imgEl.dataset.pick);
		});
		if (crumb) crumb.addEventListener("click", function(e){
			var el = e.target.closest("[data-nav]");
			if (!el) return;
			state.folder = el.dataset.nav;
			if (search) search.value = "";
			render();
		});
		if (search) search.addEventListener("input", render);

		if (uploadIn) uploadIn.addEventListener("change", function(){
			var files = Array.prototype.slice.call(uploadIn.files || []);
			if (!files.length) return;
			var done = 0;
			uploadSt.textContent = ' . json_encode(i18n_r('simpleBlog/PICKER_UPLOADING')) . ' + " 0 / " + files.length + "\\u2026";
			files.forEach(function(file){
				var fd = new FormData();
				fd.append("sb_picker_action", "upload_image");
				fd.append("image", file);
				fetch(ajaxUrl, { method: "POST", body: fd })
					.then(function(r){ return r.json(); })
					.then(function(res){
						done++;
						uploadSt.textContent = ' . json_encode(i18n_r('simpleBlog/PICKER_UPLOADING')) . ' + " " + done + " / " + files.length + "\\u2026";
						if (res.ok) {
							state.images = []; // invalidate cache so Browse tab reflects the new upload
							select(res.image.filename);
						}
						if (done === files.length) {
							uploadSt.textContent = "";
							uploadIn.value = "";
						}
					});
			});
		});
	})();
	</script>';

	return $html;
}

/**
 * Renders the gallery picker field (hidden input + thumbnail preview + add button)
 * @param string $initial_gallery_json JSON array of uploads-relative image paths
 * @param string $SITEURL Site base URL
 * @return string HTML
 */
function sb_gallery_field_html($initial_gallery_json, $SITEURL){
	$decoded = json_decode((string)$initial_gallery_json, true);
	if (!is_array($decoded)) $decoded = [];
	$initial_json_attr = htmlspecialchars(json_encode(array_values($decoded)), ENT_QUOTES, 'UTF-8');

	$html  = '<label>' . i18n_r('simpleBlog/GALLERY') . ':</label>';
	$html .= '<div class="sb-gallery-field">';
	$html .= '<input type="hidden" name="gallery" id="sb-gallery-hidden" value="' . $initial_json_attr . '">';
	$html .= '<div id="sb-gallery-preview" class="sb-gallery-preview"></div>';
	$html .= '<button type="button" class="sb-gallery-add-btn addPhoto">' . i18n_r('simpleBlog/ADD_GALLERY_IMAGE') . '</button>';
	$html .= '<p class="sb-gallery-help">' . i18n_r('simpleBlog/GALLERY_HELP') . '</p>';
	$html .= '</div>';

	$html .= '
	<style>
		.sb-gallery-preview{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0;min-height:4px}
		.sb-gallery-thumb{position:relative;width:90px;height:90px;border:1px solid #ddd;border-radius:4px;overflow:hidden;background:#f4f4f4}
		.sb-gallery-thumb img{width:100%;height:100%;object-fit:cover;display:block}
		.sb-gallery-thumb .sb-gallery-remove{position:absolute;top:2px;right:2px;background:rgba(0,0,0,.65); color:#fff;border:none;border-radius:50%;width:20px;height:20px;line-height:18px; font-size:13px;cursor:pointer;padding:0}
		.sb-gallery-thumb .sb-gallery-remove:hover{background:#c0392b}
		.sb-gallery-help{font-size:.8em;color:#777;margin:4px 0 0}
	</style>';

	$html .= '
	<script>
	(function(){
		var sbGalleryImages = [];
		try {
			var sbGalleryHidden = document.getElementById("sb-gallery-hidden");
			var parsed = JSON.parse((sbGalleryHidden && sbGalleryHidden.value) || "[]");
			if (Array.isArray(parsed)) sbGalleryImages = parsed;
		} catch (e) { sbGalleryImages = []; }

		function sbSaveGallery(){
			var hidden = document.getElementById("sb-gallery-hidden");
			if (hidden) hidden.value = JSON.stringify(sbGalleryImages);
		}

		function sbRenderGalleryPreview(){
			var wrap = document.getElementById("sb-gallery-preview");
			if (!wrap) return;
			var html = "";
			sbGalleryImages.forEach(function(url, idx){
				html += "<div class=\"sb-gallery-thumb\">"
					+ "<img src=\"' . $SITEURL . '" + url + "\">"
					+ "<button type=\"button\" class=\"sb-gallery-remove\" data-idx=\"" + idx + "\">&times;</button>"
					+ "</div>";
			});
			wrap.innerHTML = html;
		}

		window.sbAddGalleryImage = function(url){
			if (!url || sbGalleryImages.indexOf(url) !== -1) return;
			sbGalleryImages.push(url);
			sbSaveGallery();
			sbRenderGalleryPreview();
		};

		document.addEventListener("click", function(e){
			var btn = e.target && e.target.closest ? e.target.closest(".sb-gallery-remove") : null;
			if (!btn) return;
			var idx = parseInt(btn.getAttribute("data-idx"), 10);
			if (isNaN(idx)) return;
			sbGalleryImages.splice(idx, 1);
			sbSaveGallery();
			sbRenderGalleryPreview();
		});

		var sbAddBtn = document.querySelector(".sb-gallery-add-btn");
		if (sbAddBtn) {
			sbAddBtn.addEventListener("click", function(e){
				e.preventDefault();
				if (window.sbOpenPicker) window.sbOpenPicker("gallery");
			});
		}

		sbRenderGalleryPreview();
	})();
	</script>';

	return $html;
}

/**
 * Renders the front-end gallery grid for a single post, using GLightbox.
 * @param string $gallery_json JSON array of uploads-relative image paths
 * @param string $SITEURL Site base URL
 * @param int    $post_id Post ID, used to scope the GLightbox instance to this gallery
 * @param string $alt_title Alt text base (post title)
 * @return string HTML, empty string if no gallery images
 */
function sb_render_public_gallery($gallery_json, $SITEURL, $post_id, $alt_title = ''){
	$images = json_decode((string)$gallery_json, true);
	if (!is_array($images) || empty($images)) return '';

	$alt = htmlspecialchars($alt_title, ENT_QUOTES, 'UTF-8');
	$gid = 'sb-gallery-' . (int)$post_id;

	$html = '<div class="sb-post-gallery" id="' . $gid . '">';
	foreach ($images as $img) {
		$full  = htmlspecialchars($SITEURL . $img, ENT_QUOTES, 'UTF-8');
		$thumb = htmlspecialchars(sb_thumb_url($img, $SITEURL), ENT_QUOTES, 'UTF-8');
		$html .= '<a href="' . $full . '" class="glightbox sb-post-gallery-item" data-gallery="' . $gid . '">';
		$html .= '<img src="' . $thumb . '" alt="' . $alt . '" loading="lazy">';
		$html .= '</a>';
	}
	$html .= '</div>';

	$html .= '
	<style>
		.sb-post-gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin:16px 0}
		.sb-post-gallery-item{display:block;border:1px solid #ccc;border-radius:6px;overflow:hidden;line-height:0}
		.sb-post-gallery-item img{width:100%;height:140px;object-fit:cover; display:block;transition:transform .15s; margin:0!important}
		.sb-post-gallery-item:hover img{transform:scale(1.04)}
	</style>';

	$html .= '<link rel="stylesheet" href="' . $SITEURL . 'plugins/simpleBlog/assets/glightbox/glightbox.min.css">';
	$html .= '<script src="' . $SITEURL . 'plugins/simpleBlog/assets/glightbox/glightbox.min.js"></script>';
	$html .= '<script>
	(function(){
		function sbInitLightbox(){
			if (!window.GLightbox) return;
			GLightbox({ selector: "#' . $gid . ' .glightbox" });
		}
		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", sbInitLightbox);
		} else {
			sbInitLightbox();
		}
	})();
	</script>';

	return $html;
}

// sitemap
add_action('sitemap-aftersave', 'get_sb_sitemap');

/**
 * Builds the RSS 2.0 XML for the blog's most recent visible posts.
 *
 * @param SQLite3 $db
 * @param string $SITEURL
 * @return string
 */
function sb_generate_rss_xml($db, $SITEURL) {
	global $SITENAME;

	$use_slug_routing = get_setting($db, 'use_slug_routing') === '1';
	$root_page = get_setting($db, 'root_page');
	$limit = (int)get_setting($db, 'rss_post_count');
	if ($limit < 1) $limit = 20;

	// Same "visible posts" rule used by the main blog listing: published, or
	// a draft whose scheduled date has already passed.
	$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.published = 1 OR (p.published = 0 AND p.scheduled_date IS NOT NULL AND p.scheduled_date <= ?)) ORDER BY p.date DESC LIMIT ?");
	$stmt->bindValue(1, time(), SQLITE3_INTEGER);
	$stmt->bindValue(2, $limit, SQLITE3_INTEGER);
	$result = $stmt->execute();

	$feed_title = htmlspecialchars($SITENAME ?: 'Blog', ENT_QUOTES, 'UTF-8');
	$feed_link  = htmlspecialchars($SITEURL, ENT_QUOTES, 'UTF-8');
	$feed_self  = htmlspecialchars($SITEURL . 'rss.xml', ENT_QUOTES, 'UTF-8');

	$items = '';
	while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
		if ($use_slug_routing) {
			$post_url = $SITEURL . $root_page . '/' . $row['slug'];
		} else {
			$post_url = $SITEURL . 'index.php?id=' . $root_page . '&post=' . $row['slug'];
		}
		$link = htmlspecialchars($post_url, ENT_QUOTES, 'UTF-8');

		// Prefer the post's own excerpt field; fall back to a stripped,
		// truncated snippet of the full content via GS's own getExcerpt().
		$desc_source = !empty($row['description']) ? $row['description'] : getExcerpt($row['content'], 300);
		$desc_source = str_replace(']]>', ']]&gt;', strip_tags($desc_source)); // CDATA can't contain a literal "]]>"

		$items .= "\t<item>\n";
		$items .= "\t\t<title>" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</title>\n";
		$items .= "\t\t<link>{$link}</link>\n";
		$items .= "\t\t<guid isPermaLink=\"true\">{$link}</guid>\n";
		$items .= "\t\t<pubDate>" . date(DATE_RSS, $row['date']) . "</pubDate>\n";
		$items .= "\t\t<description><![CDATA[{$desc_source}]]></description>\n";
		if (!empty($row['category_name'])) {
			$items .= "\t\t<category>" . htmlspecialchars($row['category_name'], ENT_QUOTES, 'UTF-8') . "</category>\n";
		}
		$items .= "\t</item>\n";
	}

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
	$xml .= "<channel>\n";
	$xml .= "\t<title>{$feed_title}</title>\n";
	$xml .= "\t<link>{$feed_link}</link>\n";
	$xml .= "\t<atom:link href=\"{$feed_self}\" rel=\"self\" type=\"application/rss+xml\" />\n";
	$xml .= "\t<description>" . $feed_title . ' RSS Feed' . "</description>\n";
	$xml .= "\t<lastBuildDate>" . date(DATE_RSS) . "</lastBuildDate>\n";
	$xml .= $items;
	$xml .= "</channel>\n";
	$xml .= "</rss>\n";

	return $xml;
}

/**
 * Writes (or removes) rss.xml at the site root, based on the current
 * enable_rss setting. Call this anywhere a post or the setting changes.
 *
 * @param SQLite3 $db
 * @param string $SITEURL
 */
function sb_write_rss_file($db, $SITEURL) {
	$file = GSROOTPATH . 'rss.xml';
	// Enabled unless explicitly turned off, so the feed works immediately on
	// upgrade without requiring a settings visit first.
	if (get_setting($db, 'enable_rss') === '0') {
		if (file_exists($file)) @unlink($file);
		return;
	}
	@file_put_contents($file, sb_generate_rss_xml($db, $SITEURL));
}

function get_sb_sitemap(){
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
		$sitemap_content .= '	<url>' . PHP_EOL;

		if (get_setting($db, 'use_slug_routing')) {
			$url = $SITEURL . get_setting($db, 'root_page') . '/' . htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8');
		} else {
			// Budujemy URL bez kodowania separatora &
			$url = $SITEURL . 'index.php?id=' . htmlspecialchars(get_setting($db, 'root_page'), ENT_QUOTES, 'UTF-8') .
				'&post=' . htmlspecialchars($row['slug'], ENT_QUOTES, 'UTF-8');
		}

		$sitemap_content .= '		<loc>' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</loc>' . PHP_EOL;

		// Opcjonalnie: dodanie daty modyfikacji jeśli istnieje w tabeli
		if (isset($row['date'])) {
			$sitemap_content .= '		<lastmod>' . date('Y-m-d', $row['date']) . '</lastmod>' . PHP_EOL;
		}

		$sitemap_content .= '		<changefreq>monthly</changefreq>' . PHP_EOL;
		$sitemap_content .= '		<priority>0.8</priority>' . PHP_EOL;
		$sitemap_content .= '	</url>' . PHP_EOL;
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
			$sitemap_content .= '	<url>' . PHP_EOL;

			$slug = pathinfo($page, PATHINFO_FILENAME);

			if ($friendly_urls) {
				// Friendly URLs włączone - używamy slugów z ukośnikiem
				$url = ($slug == 'index') ? $SITEURL : $SITEURL . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '/';
			} else {
				// Friendly URLs wyłączone - używamy formatu index.php?id=slug
				$url = $SITEURL . 'index.php?id=' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8');
			}

			$sitemap_content .= '		<loc>' . $url . '</loc>' . PHP_EOL;

			// Data modyfikacji strony
			if (isset($page_data->pubDate)) {
				$sitemap_content .= '		<lastmod>' . date('Y-m-d', strtotime($page_data->pubDate)) . '</lastmod>' . PHP_EOL;
			}

			// Priorytet zależny od tego, czy to strona główna
			$priority = ($slug == 'index') ? '1.0' : '0.6';
			$sitemap_content .= '		<changefreq>weekly</changefreq>' . PHP_EOL;
			$sitemap_content .= '		<priority>' . $priority . '</priority>' . PHP_EOL;
			$sitemap_content .= '	</url>' . PHP_EOL;
		}
	}

	$sitemap_content .= '</urlset>' . PHP_EOL;

	// Zapis do pliku
	if (file_put_contents($sitemap_file, $sitemap_content) === false) {
		// Opcjonalna obsługa błędów
		error_log("Nie udało się zapisać sitemapy do pliku: " . $sitemap_file);
	}

	$db->close();
}

//function for frontend
function get_categoryList(){
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

function get_newPostList($number){
	$db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
	$stmt = $stmt = $db->prepare("SELECT * FROM posts ORDER BY date DESC LIMIT :number");
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

function get_newPostListWithDesc($number){
	$db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
	$stmt = $stmt = $db->prepare("SELECT * FROM posts ORDER BY date DESC LIMIT :number");
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

function get_newPostListFull($number){
	$db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
	$stmt = $stmt = $db->prepare("SELECT * FROM posts ORDER BY date DESC LIMIT :number");
	$stmt->bindValue(':number', $number, SQLITE3_INTEGER);
	$result = $stmt->execute();
	global $SITEURL;
	echo '<ul class="simpleBlogWithDesc">';
	while ($row = $result->fetchArray()) {

		if (get_setting($db, 'use_slug_routing')) {
			echo '<li>
			  <a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '"> <img class="coverphoto" src="' . sb_resolve_image_url($row['cover_photo'], $SITEURL) . '"></a>
			<a href="' . $SITEURL . get_setting($db, 'root_page') . '/' . $row['slug'] . '"><h5>' . $row['title'] . '</h5></a>
			<p>' . $row['description'] . '</p>
			</li>';
		} else {
			echo '<li>
			  <a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '"> <img class="coverphoto" src="' . sb_resolve_image_url($row['cover_photo'], $SITEURL) . '"></a>

			<a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&post=' . $row['slug'] . '">' . $row['title'] . '</a>
			  <p>' . $row['description'] . '</p>
			</li>';
		}
	}
	echo '</ul>';

	$db->close();
}

function get_randomPostList($number){
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

function display_post_tags($post_id, $db) {
	global $SITEURL;
	$stmt = $db->prepare("SELECT tags.* FROM tags 
						  INNER JOIN post_tags ON tags.id = post_tags.tag_id 
						  WHERE post_tags.post_id = ?");
	$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
	$result = $stmt->execute();

	$tags = [];
	while ($row = $result->fetchArray()) {
		$tags[] = $row;
	}

	if (!empty($tags)) {
		echo '<div class="post-tags">';
		echo '<span class="tags-label">' . i18n_r('simpleBlog/TAGS') . ': </span>';
		foreach ($tags as $tag) {
			if (get_setting($db, 'use_slug_routing')) {
				echo '<a href="' . $SITEURL . get_setting($db, 'root_page') . '/tag/' . $tag['slug'] . '" class="tag-link">' . htmlspecialchars($tag['name']) . '</a> ';
			} else {
				echo '<a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&tag=' . $tag['slug'] . '" class="tag-link">' . htmlspecialchars($tag['name']) . '</a> ';
			}
		}
		echo '</div>';
	}
}

function get_tagList() {
	$db = new SQLite3(GSDATAOTHERPATH . 'blog.db');
	$stmt = $db->prepare("SELECT * FROM tags ORDER BY name ASC");
	$result = $stmt->execute();
	global $SITEURL;

	echo '<ul class="simpleBlogTags">';
	while ($row = $result->fetchArray()) {
		if (get_setting($db, 'use_slug_routing')) {
			echo '<li><a href="' . $SITEURL . get_setting($db, 'root_page') . '/tag/' . $row['slug'] . '">' . htmlspecialchars($row['name']) . '</a></li>';
		} else {
			echo '<li><a href="' . $SITEURL . 'index.php?id=' . get_setting($db, 'root_page') . '&tag=' . $row['slug'] . '">' . htmlspecialchars($row['name']) . '</a></li>';
		}
	}
	echo '</ul>';

	$db->close();
}

//end function for frontend;

function blog_admin(){
	$pp = '
	<div id="donateButton" style="display:flex; align-items:center; justify-content:space-between;background:#fafafa;border:solid 1px #ddd;padding:10px;margin:10px 0;">
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
		<b>Buy me a small coffee!</b>
	</div>
	';

	global $thisfile;
	blog_init_db();
	$db = new SQLite3(GSDATAOTHERPATH . 'blog.db');

	echo '
	<style>
		.blog-admin-container { width:90%; margin: 20px auto; font-family: Arial, sans-serif; }
		.blog-admin-container h3 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
		.blog-admin-container h4 { color: #000; margin: 20px 0; font-size:1.3rem; display:block; }
		
		.blog-form { background: #fafafa; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border:solid 1px #ddd }
		.blog-form label { display: block; margin-bottom: 5px; font-weight: bold; }
		.blog-form input[type="text"], .blog-form input[type="email"], .blog-form textarea, .blog-form select {width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;}
		.blog-form input[type="file"] { margin-bottom: 15px; }
		.blog-form input[type="submit"] { background: #0073aa; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
		.blog-form input[type="submit"]:hover { background: #005177; }
		
		.post-list, .category-list { margin-top: 20px; }
		
		.post-item, .category-item { background: #fff; padding: 15px; margin-bottom: 10px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);}
		.post-item a, .category-item a { color: #0073aa; text-decoration: none; margin-left: 10px; }
		.post-item,.category-item {display: grid; grid-template-columns: 1fr 50px; align-items: center; justify-content: center; padding:20px; }
		.post-item a:hover, .category-item a:hover { text-decoration: underline; }
		
		.comment-item form { display: inline; }
		.category-item form, .post-item form { display: inline; }
		
		.category-item input[type="submit"], .post-item input[type="submit"] { border-radius: 4px; background: #d63638; color: #fff; padding: 5px 10px; }
		.category-item input[type="submit"]:hover, .post-item input[type="submit"]:hover { background: #b32d2e; }
		
		.comment-item { background: #f9f9f9; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
		.comment-item {display:grid; grid-template-columns: 1fr 80px 80px; }
		.comment-item p {margin:0; }
		.post-item a {text-decoration:none !important; color:rgba(0,0,0,0.5	) !important;}
		.post-item p {margin:0; }
		
		.help-section { margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 5px; border: 1px solid #ddd; }
		.help-section h5 { margin-top: 0; color: #333; }
		.help-section pre { background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; }
		
		.category-item p {margin:0; }
		
		.notification { background: #e0f7fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; color: #0073aa; }
	</style>';

	echo '<div class="blog-admin-container">';
	echo '<h3>' . i18n_r('simpleBlog/MANAGEMENT') . '</h3>';

	$tab = isset($_GET['tab']) ? $_GET['tab'] : 'posts';

	if (isset($_POST['action'])) {
		switch ($_POST['action']) {

			case 'add_post':
				$title = $_POST['title'];
				// Use the custom slug field if the user filled it in (and it contains
				// at least one alphanumeric character); otherwise fall back to the
				// title, same as before. generate_slug() handles sanitizing +
				// de-duplicating either way.
				$slug_input = trim($_POST['slug'] ?? '');
				$has_custom_slug = $slug_input !== '' && preg_replace('/[^A-Za-z0-9]/', '', $slug_input) !== '';
				$slug = generate_slug($has_custom_slug ? $slug_input : $title, $db);
				$content = $_POST['content'];
				// Jeśli nie wybrano kategorii, znajdź lub utwórz domyślną
				if (empty($_POST['category_id'])) {
					$stmt = $db->prepare("SELECT id FROM categories WHERE slug = 'uncategorized'");
					$result = $stmt->execute()->fetchArray();
					if ($result) {
						$category_id = $result['id'];
					} else {
						$uncategorized_names = [
							'de' => 'Ohne Kategorie', 'en' => 'Uncategorized', 'es' => 'Sin categoría',
							'pl' => 'Bez kategorii', 'ru' => 'Без категории', 'uk' => 'Без категорії'
						];
						$current_lang = function_exists('return_i18n_lang') ? return_i18n_lang() : 'en';
						$uncategorized_name = isset($uncategorized_names[$current_lang]) ? $uncategorized_names[$current_lang] : 'Uncategorized';
						$stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, 'uncategorized')");
						$stmt->bindValue(1, $uncategorized_name, SQLITE3_TEXT);
						$stmt->execute();
						$category_id = $db->lastInsertRowID();
					}
				} else {
					$category_id = $_POST['category_id'];
				}
				$tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
				// Published On: trust the explicit field if the user set one (or the
				$published_on_input = trim($_POST['published_on'] ?? '');
				$date = ($published_on_input !== '' && strtotime($published_on_input) !== false)
					? strtotime($published_on_input)
					: (!empty($_POST['scheduled_date']) ? strtotime($_POST['scheduled_date']) : time());
				$published = $_POST['published'];
				$scheduled_date = !empty($_POST['scheduled_date']) ? strtotime($_POST['scheduled_date']) : null;
				$description = $_POST['description'];
				$metadata = $_POST['metadane'];
				// Last Edited On: always the real save time, never user-editable.
				$updated_at = time();

				$cover_photo = $_POST['cover_photo'];
				$gallery = sb_sanitize_gallery_input($_POST['gallery'] ?? '');

				$stmt = $db->prepare("INSERT INTO posts (title, slug, content, category_id, cover_photo, date, published, scheduled_date, description ,metadata, gallery, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?)");
				$stmt->bindValue(1, $title, SQLITE3_TEXT);
				$stmt->bindValue(2, $slug, SQLITE3_TEXT);
				$stmt->bindValue(3, $content, SQLITE3_TEXT);
				$stmt->bindValue(4, $category_id, SQLITE3_INTEGER);
				$stmt->bindValue(5, $cover_photo, SQLITE3_TEXT);
				$stmt->bindValue(6, $date, SQLITE3_INTEGER);
				$stmt->bindValue(7, $published, SQLITE3_INTEGER);
				$stmt->bindValue(8, $scheduled_date, $scheduled_date ? SQLITE3_INTEGER : SQLITE3_NULL);
				$stmt->bindValue(9, $description, SQLITE3_TEXT);
				$stmt->bindValue(10, $metadata, SQLITE3_TEXT);
				$stmt->bindValue(11, $gallery, SQLITE3_TEXT);
				$stmt->bindValue(12, $updated_at, SQLITE3_INTEGER);
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

				get_sb_sitemap();
				global $SITEURL;
				sb_write_rss_file($db, $SITEURL);

				echo "<meta http-equiv='refresh' content='0;url=load.php?id=simpleBlog&blog_admin&tab=edit_post&post_id=" . $post_id . "&successpostcreated'>";

				break;

			case 'edit_post':
				// Validate and sanitize inputs
				$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
				$title = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
				$content = $_POST['content'] ?? null;
				// Published On: trust the explicit field (this is the fix for the
				$published_on_input = trim($_POST['published_on'] ?? '');
				$date = ($published_on_input !== '' && strtotime($published_on_input) !== false)
					? strtotime($published_on_input)
					: (!empty($_POST['scheduled_date']) ? strtotime($_POST['scheduled_date']) : time());
				$category_id_input = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
				if (empty($category_id_input)) {
					$stmt = $db->prepare("SELECT id FROM categories WHERE slug = 'uncategorized'");
					$result = $stmt->execute()->fetchArray();
					if ($result) {
						$category_id = $result['id'];
					} else {
						$uncategorized_names = [
							'de' => 'Ohne Kategorie', 'en' => 'Uncategorized', 'es' => 'Sin categoría',
							'pl' => 'Bez kategorii', 'ru' => 'Без категории', 'uk' => 'Без категорії'
						];
						$current_lang = function_exists('return_i18n_lang') ? return_i18n_lang() : 'en';
						$uncategorized_name = isset($uncategorized_names[$current_lang]) ? $uncategorized_names[$current_lang] : 'Uncategorized';
						$stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, 'uncategorized')");
						$stmt->bindValue(1, $uncategorized_name, SQLITE3_TEXT);
						$stmt->execute();
						$category_id = $db->lastInsertRowID();
					}
				} else {
					$category_id = $category_id_input;
				}
				$published = filter_input(INPUT_POST, 'published', FILTER_VALIDATE_INT);
				$description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);
				$metadata = filter_input(INPUT_POST, 'metadane', FILTER_UNSAFE_RAW); // Changed to metadata, verify DB schema
				$cover_photo = !empty($_POST['cover_photo']) ? filter_var($_POST['cover_photo'], FILTER_UNSAFE_RAW) : null;
				$gallery = sb_sanitize_gallery_input($_POST['gallery'] ?? '');
				$scheduled_date = !empty($_POST['scheduled_date']) ? strtotime($_POST['scheduled_date']) : null;
				if ($scheduled_date === false) {
					$scheduled_date = null; // Handle invalid date
				}
				$tags = isset($_POST['tags']) ? array_map('trim', explode(',', filter_var($_POST['tags'], FILTER_UNSAFE_RAW))) : [];
				// Last Edited On: always the real save time, never user-editable.
				$updated_at = time();

				// Check for required fields
				if (!$post_id || !$title || !$content) {
					die('<div class="notification error">Required fields are missing.</div>');
				}

				// Generate slug - use the custom slug field if the user filled it in
				// (and it contains at least one alphanumeric character); otherwise
				// fall back to the title, same as before.
				$slug_input = trim($_POST['slug'] ?? '');
				$has_custom_slug = $slug_input !== '' && preg_replace('/[^A-Za-z0-9]/', '', $slug_input) !== '';
				$slug = generate_slug($has_custom_slug ? $slug_input : $title, $db, 'posts', $post_id);

				// Start transaction
				$db->exec('BEGIN TRANSACTION');
				try {
					// Update post
					$stmt = $db->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, category_id = ?, cover_photo = ?, published = ?, scheduled_date = ?, description = ?, metadata = ?, date = ?, gallery = ?, updated_at = ? WHERE id = ?");
					if (!$stmt) {
						throw new Exception('Failed to prepare UPDATE statement: ' . $db->lastErrorMsg());
					}
					$stmt->bindValue(1, $title, SQLITE3_TEXT);
					$stmt->bindValue(2, $slug, SQLITE3_TEXT);
					$stmt->bindValue(3, $content, SQLITE3_TEXT);
					$stmt->bindValue(4, $category_id, SQLITE3_INTEGER);
					$stmt->bindValue(5, $cover_photo, SQLITE3_TEXT);
					$stmt->bindValue(6, $published, SQLITE3_INTEGER);
					$stmt->bindValue(7, $scheduled_date, $scheduled_date ? SQLITE3_INTEGER : SQLITE3_NULL);
					$stmt->bindValue(8, $description, SQLITE3_TEXT);
					$stmt->bindValue(9, $metadata, SQLITE3_TEXT);
					$stmt->bindValue(10, $date, SQLITE3_INTEGER);
					$stmt->bindValue(11, $gallery, SQLITE3_TEXT);
					$stmt->bindValue(12, $updated_at, SQLITE3_INTEGER);
					$stmt->bindValue(13, $post_id, SQLITE3_INTEGER);
					$stmt->execute();

					// Delete existing tags
					$stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
					if (!$stmt) {
						throw new Exception('Failed to prepare DELETE statement: ' . $db->lastErrorMsg());
					}
					$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
					$stmt->execute();

					// Insert new tags
					foreach ($tags as $tag) {
						if (empty($tag) || strlen($tag) > 50) { // Skip empty or overly long tags
							continue;
						}
						$tag_slug = generate_slug($tag, $db, 'tags');
						$stmt = $db->prepare("INSERT OR IGNORE INTO tags (name, slug) VALUES (?, ?)");
						if (!$stmt) {
							throw new Exception('Failed to prepare INSERT tags statement: ' . $db->lastErrorMsg());
						}
						$stmt->bindValue(1, $tag, SQLITE3_TEXT);
						$stmt->bindValue(2, $tag_slug, SQLITE3_TEXT);
						$stmt->execute();

						$stmt = $db->prepare("SELECT id FROM tags WHERE slug = ?");
						if (!$stmt) {
							throw new Exception('Failed to prepare SELECT tags statement: ' . $db->lastErrorMsg());
						}
						$stmt->bindValue(1, $tag_slug, SQLITE3_TEXT);
						$result = $stmt->execute()->fetchArray();
						if ($result && isset($result['id'])) {
							$tag_id = $result['id'];
							$stmt = $db->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
							if (!$stmt) {
								throw new Exception('Failed to prepare INSERT post_tags statement: ' . $db->lastErrorMsg());
							}
							$stmt->bindValue(1, $post_id, SQLITE3_INTEGER);
							$stmt->bindValue(2, $tag_id, SQLITE3_INTEGER);
							$stmt->execute();
						}
					}

					// Commit transaction
					$db->exec('COMMIT');
					get_sb_sitemap();
					global $SITEURL;
					sb_write_rss_file($db, $SITEURL);
					$notification = function_exists('i18n_r') ? i18n_r('simpleBlog/POST_UPDATED') : 'Post updated successfully.';
					echo '<div class="notification">' . htmlspecialchars($notification) . '</div>';
				} catch (Exception $e) {
					// Rollback on error
					$db->exec('ROLLBACK');
					echo '<div class="notification error">Error updating post: ' . htmlspecialchars($e->getMessage()) . '</div>';
				}
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

				get_sb_sitemap();
				global $SITEURL;
				sb_write_rss_file($db, $SITEURL);
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
				// Jeśli nie wybrano kategorii, znajdź lub utwórz domyślną
				if (empty($_POST['category_id'])) {
					$stmt = $db->prepare("SELECT id FROM categories WHERE slug = 'uncategorized'");
					$result = $stmt->execute()->fetchArray();
					if ($result) {
						$category_id = $result['id'];
					} else {
						$uncategorized_names = [
							'de' => 'Ohne Kategorie', 'en' => 'Uncategorized', 'es' => 'Sin categoría',
							'pl' => 'Bez kategorii', 'ru' => 'Без категории', 'uk' => 'Без категорії'
						];
						$current_lang = function_exists('return_i18n_lang') ? return_i18n_lang() : 'en';
						$uncategorized_name = isset($uncategorized_names[$current_lang]) ? $uncategorized_names[$current_lang] : 'Uncategorized';
						$stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, 'uncategorized')");
						$stmt->bindValue(1, $uncategorized_name, SQLITE3_TEXT);
						$stmt->execute();
						$category_id = $db->lastInsertRowID();
					}
				} else {
					$category_id = $_POST['category_id'];
				}
				$stmt = $db->prepare("UPDATE posts SET category_id = NULL WHERE category_id = ?");
				$stmt->bindValue(1, $category_id, SQLITE3_INTEGER);
				$stmt->execute();
				$stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
				$stmt->bindValue(1, $category_id, SQLITE3_INTEGER);
				$stmt->execute();
				echo '<div class="notification">' . i18n_r('simpleBlog/CATEGORY_DELETED') . '</div>';
				break;

			case 'add_tag':
				$name = $_POST['name'];
				$slug = generate_slug($name, $db, 'tags');
				$stmt = $db->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
				$stmt->bindValue(1, $name, SQLITE3_TEXT);
				$stmt->bindValue(2, $slug, SQLITE3_TEXT);
				$stmt->execute();
				echo '<div class="notification">Tag added successfully!</div>';
				break;

			case 'delete_tag':
				$tag_id = $_POST['tag_id'];
				$stmt = $db->prepare("DELETE FROM post_tags WHERE tag_id = ?");
				$stmt->bindValue(1, $tag_id, SQLITE3_INTEGER);
				$stmt->execute();
				$stmt = $db->prepare("DELETE FROM tags WHERE id = ?");
				$stmt->bindValue(1, $tag_id, SQLITE3_INTEGER);
				$stmt->execute();
				echo '<div class="notification">Tag deleted successfully!</div>';
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

				$stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('style_theme', ?)");
				$stmt->bindValue(1, $_POST['style_theme'] ?? 0, SQLITE3_TEXT);
				$stmt->execute();

				$stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('enable_rss', ?)");
				$stmt->bindValue(1, isset($_POST['enable_rss']) ? '1' : '0', SQLITE3_TEXT);
				$stmt->execute();

				$stmt = $db->prepare("INSERT OR REPLACE INTO settings (name, value) VALUES ('rss_post_count', ?)");
				$stmt->bindValue(1, (int)($_POST['rss_post_count'] ?? 20) ?: 20, SQLITE3_INTEGER);
				$stmt->execute();

				get_sb_sitemap();
				global $SITEURL;
				sb_write_rss_file($db, $SITEURL);
				echo '<div class="notification">' . i18n_r('simpleBlog/SETTINGS_SAVED') . '</div>';
				break;
		}
	}

	switch ($tab) {
		case 'snippet':

		echo '
		<style>
			pre {background: #fff;padding: 10px;border: 1px solid #ddd;border-radius: 4px;overflow-x: auto;margin:10px 0;}
			p {margin:0 !important;padding:0 !important;}
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
		echo '<p>Tags list:</p>';
		echo ' <pre>get_tagList()</pre>';
		echo '<p>Display post tags (in single post template):</p>';
		echo ' <pre>display_post_tags($post_id, $db)</pre>';

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
		echo '<label><input type="checkbox" name="enable_rss" value="1" ' . (get_setting($db, 'enable_rss') !== '0' ? 'checked' : '') . '> ' . i18n_r('simpleBlog/ENABLE_RSS') . '</label><br>';
		echo '<label>' . i18n_r('simpleBlog/RSS_POST_COUNT') . ':</label><input type="number" style="width:100%;padding:10px;border:solid 1px #ddd;border-radius:5px;margin-bottom:10px;" name="rss_post_count" value="' . htmlspecialchars(get_setting($db, 'rss_post_count') ?: 20) . '" min="1" max="100"><br>';

		echo '<label>Style</label><select name="style_theme">';

		foreach (glob(GSPLUGINPATH . 'simpleBlog/css/*.css') as $file) {

			echo '<option value="' . pathinfo($file)['filename'] . '" ' . (get_setting($db, 'style_theme') === pathinfo($file)['filename'] ? 'selected' : '') . '>' . pathinfo($file)['filename'] . '</option>';
		}

		echo '</select>';

		echo '<label>' . i18n_r('simpleBlog/POSTS_PER_PAGE') . ':</label><input type="number" style="width:100%;padding:10px;border:solid 1px #ddd;border-radius:5px;margin-bottom:10px;" name="posts_per_page" value="' . htmlspecialchars(get_setting($db, 'posts_per_page') ?: 10) . '" min="1" required><br>';

		echo '<label>' . i18n_r('simpleBlog/PARENT_PAGE') . '<select class="root_page" name="root_page">';

		foreach (glob(GSDATAPAGESPATH . '*.xml') as $file) {
			$file = pathinfo($file)['filename'];
			echo '<option value="' . $file . '">' . $file . '</option></label>';
		};

		echo '</select>';

		echo '<script>document.querySelector(".root_page").value = "' . get_setting($db, 'root_page') . '"</script>';

		echo '<input type="submit" style="background:#00AA2C; margin-top:30px" value="' . i18n_r('simpleBlog/SAVE_SETTINGS') . '">';

		echo '</form>';
		echo '</div>';

		echo '<div class="help-section">';
		echo '<h5>' . i18n_r('simpleBlog/RSS_FEED_URL') . '</h5>';
		global $SITEURL;
		if (get_setting($db, 'enable_rss') !== '0') {
			$rss_url = $SITEURL . 'rss.xml';
			echo '<input type="text" readonly value="' . htmlspecialchars($rss_url, ENT_QUOTES, 'UTF-8') . '" onclick="this.select()" style="width:100%;padding:10px;border:solid 1px #ddd;border-radius:5px;box-sizing:border-box;">';
			echo '<p style="font-size:.85rem;color:#777;margin:8px 0 0">' . i18n_r('simpleBlog/RSS_FEED_HELP') . '</p>';
			echo '<p style="font-size:.85rem;color:#777;margin:8px 0 0">In <code class="cke">.htaccess</code>, right before the line <code class="tpl">&lt;Files sitemap.xml></code>, add:</p>';
			echo '<pre class="cke">
&lt;Files rss.xml>
	&lt;IfModule !mod_authz_core.c>
		Allow from all
	&lt;/IfModule>
	&lt;IfModule mod_access_compat.c>
		Allow from all
	&lt;/IfModule>
	&lt;IfModule mod_authz_core.c>
		&lt;IfModule !mod_access_compat.c>
			Require all granted
		&lt;/IfModule>
	&lt;/IfModule>	
&lt;/Files></pre>';
		} else {
			echo '<p style="font-size:.9rem;color:#777">' . i18n_r('simpleBlog/RSS_DISABLED_HELP') . '</p>';
		}
		echo '</div>';

		echo '<div class="help-section">';
		echo '<h5>' . i18n_r('simpleBlog/SLUG_ROUTING') . '</h5>';
		echo '<button  style="border-radius: 4px; background: #0073aa;color:#fff;padding:10px;margin:5px 0"  onclick="copyToClipboard()">Copy to Clipboard</button>';
		
		if (get_setting($db, 'root_page') && get_setting($db, 'use_slug_routing')) {

			echo "
			<br>
			<b>In .htaccess, find the <span class='tpl'>following lines</span>, usually near 69-71 and paste the <span class='cke'>routing code </span>after that</b>
			<div class='tpl' style='background:lightgrey;padding:10px;line-height:1.3'>
			[...]<br>  
			# Usually RewriteBase is just '/', but <br>
			# replace it with your subdirectory path <br>
			RewriteBase / or /subfolder/
			</div>
			";

			echo '<div class="cke" style="background:#fafafa;border:solid 1px #ddd;padding:10px;line-height:1.3; ">';
			echo '#For Post<br>
			 RewriteRule ^' . get_setting($db, 'root_page') . '/([A-Za-z0-9-]+)/?$ index.php?id=' . get_setting($db, 'root_page') . '&post=$1 [L]';
			echo '<br>#For Category<br>';
			echo 'RewriteRule ^' . get_setting($db, 'root_page') . '/category/([A-Za-z0-9-]+)/?$ index.php?id=' . get_setting($db, 'root_page') . '&category=$1 [L]</div>';

			echo '
			<p>' . i18n_r('simpleBlog/SLUG_ROUTING_STEPS') . '</p>';
			echo '</div>';

			echo '
			<script>
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
	alert("Routing Code has been copied to clipboard!");
}
		</script>';

			} else {
				echo '<h4>MAKE PARENT PAGE and SLUG ROUTING</h4>
			</div>';
			}
			break;
			
			case 'tags':
			echo '<h4>Manage Tags</h4>';
			echo '<div class="blog-form">';
			echo '<form method="post">';
			echo '<input type="hidden" name="action" value="add_tag">';
			echo '<label>' . i18n_r('simpleBlog/NAME') . ':</label><input type="text" name="name" required><br>';
			echo '<input type="submit" value="' . i18n_r('simpleBlog/ADD_TAG') . '">';
			echo '</form>';
			echo '</div>';
			echo '<hr>';
			echo '<h4>' . i18n_r('simpleBlog/TAGS') . '</h4>';
			echo '<div class="category-list">';
			$results = $db->query("SELECT * FROM tags ORDER BY name ASC");
			while ($row = $results->fetchArray()) {
				echo '<div class="category-item">';
				echo '<p>' . htmlspecialchars($row['name']) . ' (' . $row['slug'] . ')</p>';
				echo '<form method="post" style="display:inline">';
				echo '<input type="hidden" name="action" value="delete_tag">';
				echo '<input type="hidden" name="tag_id" value="' . $row['id'] . '">';
				echo '<input type="submit" value="' . i18n_r('simpleBlog/DELETE') . '" onclick="return confirm(\'' . i18n_r('simpleBlog/CONFIRM_DELETE') . '\')">';
				echo '</form></div>';
			}
			echo '</div>';
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

			echo '
			<style>
				.addPhoto {background:#0073AA!important; color:#fff; border-radius:5px !important; border:none; padding:10px; margin-bottom:10px;}
				.addPhoto:hover {background:#006290;}
				.cur_photo {display:block; margin:10px 0;}
				
				.datetime {padding:5px 8px; border-radius:5px; }
				
				.sb-field-row {display:flex;gap:16px;flex-wrap:wrap;margin-bottom:10px}
				.sb-field-row > div{flex:1;min-width:200px}
				.sb-field-row label{display:block;margin-bottom:4px}
				.sb-field-row input[type="text"],.sb-field-row input.datetime,.sb-field-row select{width:100%;box-sizing:border-box}
				.sb-field-row p{margin:0}
				.cke_contents.cke_reset{height:300px!important;}
			</style>';

			global $SITEURL;
			echo '<h4>' . i18n_r('simpleBlog/ADD_POST') . '</h4>';
			echo '<div class="blog-form">';
			echo '<form method="post" enctype="multipart/form-data">';
			echo '<input type="hidden" name="action" value="add_post">';
			echo '<div class="sb-field-row">';
			echo '<div><label>' . i18n_r('simpleBlog/TITLE') . ':</label><input type="text" name="title" required></div>';
			echo '<div style="opacity:70%"><label>' . i18n_r('simpleBlog/SLUG') . ':</label><input type="text" name="slug" placeholder="' . htmlspecialchars(strip_tags(i18n_r('simpleBlog/SLUG_PLACEHOLDER')), ENT_QUOTES, 'UTF-8') . '"></div>';
			echo '</div>';

			echo '<div class="sb-field-row">';
			echo '<div><label>' . i18n_r('simpleBlog/CATEGORY') . ':</label>';
			$categories = $db->query("SELECT * FROM categories");
			if (!$categories) {
				echo "<p style='color:red'>First, create a category!</p>";
			} else {
				echo '<select name="category_id" required>';
				while ($cat = $categories->fetchArray()) {
					echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
				}
				echo '</select>';
			};
			echo '</div>';
			echo '<div><label>' . i18n_r('simpleBlog/STATUS') . ':</label><select name="published" required>';
			echo '<option value="1">' . i18n_r('simpleBlog/PUBLISHED') . '</option>';
			echo '<option value="0">' . i18n_r('simpleBlog/DRAFT') . '</option>';
			echo '</select></div>';
			echo '</div>';

			echo '<div class="sb-field-row">';
			echo '<div><label>' . i18n_r('simpleBlog/SCHEDULED_DATE') . ':</label><input class="datetime" type="datetime-local" name="scheduled_date" id="sb-scheduled-date"></div>';
			echo '<div><label>' . i18n_r('simpleBlog/PUBLISHED_ON') . ':</label><input class="datetime" type="datetime-local" name="published_on" id="sb-published-on" value="' . date('Y-m-d\TH:i') . '"></div>';
			echo '</div>';
			echo '<script>
			(function(){
				var pub = document.getElementById("sb-published-on");
				var sched = document.getElementById("sb-scheduled-date");
				if (!pub || !sched) return;
				var pubTouched = false;
				pub.addEventListener("input", function(){ pubTouched = true; });
				sched.addEventListener("change", function(){
					if (!pubTouched && sched.value) pub.value = sched.value;
				});
			})();
			</script>';
			echo '<label style="display:none">' . i18n_r('simpleBlog/TAGS') . ':</label><input type="hidden" name="tags"><br>';
			echo '<label>' . i18n_r('simpleBlog/COVER_PHOTO') . ':</label>
				
		   <input type="text" name="cover_photo" class="cover_photo">
				<button type="button" class="addPhoto">' . i18n_r('simpleBlog/ADD_PHOTO') . '</button>
				<img src="" style="max-width:200px;display:none" class="cur_photo">
				<script>
					document.querySelector(".addPhoto").addEventListener("click", (e) => {
			e.preventDefault();
			if (window.sbOpenPicker) window.sbOpenPicker("cover_photo");
			});
			</script>';
			echo sb_render_picker_modal($SITEURL);

			echo '<br>';

			echo '<label>' . i18n_r('simpleBlog/DESCRIPTION') . ':</label><textarea name="description"  style="height:100px"></textarea><br>';
			echo '<label>Metadesc (SEO):</label><textarea name="metadane" id="sb-metadane" style="height:70px"></textarea>';
			echo sb_render_char_counter('sb-metadane', 160);
			echo '<br>';
			echo '<label>' . i18n_r('simpleBlog/CONTENT') . ':</label><textarea id="post-content" name="content" required rows="5"></textarea><br>';
			echo sb_gallery_field_html('[]', $SITEURL);
			echo '<input type="submit" style="background:#00AA2C; margin-top:20px;" value="' . i18n_r('simpleBlog/ADD_POST') . '">';
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

			echo '
			<script type="text/javascript" src="template/js/ckeditor/ckeditor.js"?t=" . getDef("GSCKETSTAMP") : ""; ?>"></script>

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
					filebrowserWindowHeight: "350"
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

			echo '
			<style>
				.addPhoto{background:#0073AA; color:#fff; border-radius:5px !important; border:none; padding:10px;}
				.addPhoto:hover{background:#006290;}
				.cur_photo{display:block;margin:10px 0;}
				
				.datetime{padding:5px 8px;border-radius:5px;}
				
				.sb-field-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:10px}
				.sb-field-row > div{flex:1;min-width:200px}
				.sb-field-row label{display:block;margin-bottom:4px}
				.sb-field-row input[type="text"],.sb-field-row input.datetime,.sb-field-row select{width:100%;box-sizing:border-box}
				.sb-field-row p{margin:0}
				.cke_contents.cke_reset{height:300px!important;}
			</style>';

			if (isset($_GET['successpostcreated'])) {
				echo '<div class="notification">' . i18n_r('simpleBlog/POST_ADDED') . '</div>';
			};

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
					echo '<div class="sb-field-row">';
					echo '<div><label>' . i18n_r('simpleBlog/TITLE') . ':</label><input type="text" name="title" value="' . htmlspecialchars($post['title']) . '" required></div>';
					echo '<div style="opacity:70%"><label>' . i18n_r('simpleBlog/SLUG') . ':</label><input type="text" name="slug" value="' . htmlspecialchars($post['slug']) . '" placeholder="' . htmlspecialchars(strip_tags(i18n_r('simpleBlog/SLUG_PLACEHOLDER')), ENT_QUOTES, 'UTF-8') . '"></div>';
					echo '</div>';

					echo '<div class="sb-field-row">';
					echo '<div><label>' . i18n_r('simpleBlog/CATEGORY') . ':</label><select name="category_id" required>';
					$categories = $db->query("SELECT * FROM categories");
					while ($cat = $categories->fetchArray()) {
						$selected = $cat['id'] == $post['category_id'] ? ' selected' : '';
						echo "<option value='{$cat['id']}'{$selected}>{$cat['name']}</option>";
					}
					echo '</select></div>';
					echo '<div><label>' . i18n_r('simpleBlog/STATUS') . ':</label><select name="published" required>';
					echo '<option value="1"' . ($post['published'] == 1 ? ' selected' : '') . '>' . i18n_r('simpleBlog/PUBLISHED') . '</option>';
					echo '<option value="0"' . ($post['published'] == 0 ? ' selected' : '') . '>' . i18n_r('simpleBlog/DRAFT') . '</option>';
					echo '</select></div>';
					echo '</div>';

					echo '<div class="sb-field-row">';
					$scheduled_date = $post['scheduled_date'] ? date('Y-m-d\TH:i', $post['scheduled_date']) : '';
					echo '<div><label>' . i18n_r('simpleBlog/SCHEDULED_DATE') . ':</label><input class="datetime" type="datetime-local" name="scheduled_date" id="sb-scheduled-date" value="' . htmlspecialchars($scheduled_date) . '"></div>';
					$published_on = $post['date'] ? date('Y-m-d\TH:i', $post['date']) : date('Y-m-d\TH:i');
					echo '<div><label>' . i18n_r('simpleBlog/PUBLISHED_ON') . ':</label><input class="datetime" type="datetime-local" name="published_on" id="sb-published-on" value="' . htmlspecialchars($published_on) . '"></div>';
					if (!empty($post['updated_at'])) {
						echo '<div><label>' . i18n_r('simpleBlog/LAST_EDITED_ON') . ':</label><p style="padding:5px 0;color:#555">' . htmlspecialchars(date('Y-m-d H:i', $post['updated_at'])) . '</p></div>';
					}
					echo '</div>';
					echo '<script>
					(function(){
						var pub = document.getElementById("sb-published-on");
						var sched = document.getElementById("sb-scheduled-date");
						if (!pub || !sched) return;
						var pubTouched = false;
						pub.addEventListener("input", function(){ pubTouched = true; });
						sched.addEventListener("change", function(){
							if (!pubTouched && sched.value) pub.value = sched.value;
						});
					})();
					</script>';

					echo '
					<label  style="display:none">' . i18n_r('simpleBlog/TAGS') . ':</label><input type="hidden" name="tags" value="' . htmlspecialchars($tags_str) . '"><br>';
					echo '
					<label>' . i18n_r('simpleBlog/COVER_PHOTO') . ':</label> <input type="text" name="cover_photo" value="' . $post['cover_photo'] . '" class="cover_photo">
			<button type="button" class="addPhoto">' . i18n_r('simpleBlog/ADD_PHOTO') . '</button>
			<script>
				document.querySelector(".addPhoto").addEventListener("click", (e) => {
				e.preventDefault();
				if (window.sbOpenPicker) window.sbOpenPicker("cover_photo");
				});
			</script>';
					echo sb_render_picker_modal($SITEURL);
					echo '<br> <img src="' . ($post['cover_photo'] ? sb_resolve_image_url($post['cover_photo'], $GLOBALS['SITEURL']) : '') . '" style="max-width:200px' . ($post['cover_photo'] ? '' : ';display:none') . '" class="cur_photo">';
					echo '<br>';
					echo '<br>';

					echo '<label>' . i18n_r('simpleBlog/DESCRIPTION') . ':</label><textarea name="description" style="height:100px">' . htmlspecialchars($post['description']) . '</textarea><br>';
					echo '<label>Metadata (for SEO):</label><textarea name="metadane" id="sb-metadane-edit" style="height:70px">' . $post['metadata'] . '</textarea>';
					echo sb_render_char_counter('sb-metadane-edit', 160);
					echo '<br>';

					echo '<label>' . i18n_r('simpleBlog/CONTENT') . ':</label><textarea name="content" id="post-content" required rows="5">' . htmlspecialchars($post['content']) . '</textarea><br>';
					echo sb_gallery_field_html($post['gallery'] ?? '[]', $SITEURL);
					echo '<input type="submit" style="background:#00AA2C; margin-top:20px;" style="margin-top:20px" value="' . i18n_r('simpleBlog/UPDATE_POST') . '">';
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

					echo '
					<script type="text/javascript" src="template/js/ckeditor/ckeditor.js"?t=" . getDef("GSCKETSTAMP") : ""; ?>"></script>

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
							filebrowserWindowHeight: "350"
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
				.comment-item {display:flex; align-items:center; justify-content:space-between;}
				.comment-item .aprove {background:green; border:none; border-radius:5px !important; padding:5px;}
				.comment-item .delete {background:red; border:none; border-radius:5px !important; padding:5px;}
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
				echo '
				<button type="submit" class="aprove">
					<svg width="15px" height="15px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style></defs><title/><g id="checkmark"><line class="cls-1" x1="3" x2="12" y1="16" y2="25"/><line class="cls-1" x1="12" x2="29" y1="25" y2="7"/></g></svg>
				</button>';
				echo "</form>";
				echo "<form method='post' style='display:inline; margin-left:10px;'>";
				echo "<input type='hidden' name='action' value='delete_comment'>";
				echo "<input type='hidden' name='comment_id' value='{$row['id']}'>";
				echo "<button type='submit' class='delete' onclick='return confirm(\"" . i18n_r($thisfile . '/SIMPLEBLOG_CONFIRM_DELETE_COMMENT') . "\");'>";

				echo '
				<svg width="15px" height="15px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style></defs><title/><g id="cross"><line class="cls-1" x1="7" x2="25" y1="7" y2="25"/><line class="cls-1" x1="7" x2="25" y1="25" y2="7"/></g></svg></button>';

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
				echo '
				<svg width="15px" height="15px" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style></defs><title/><g id="cross"><line class="cls-1" x1="7" x2="25" y1="7" y2="25"/><line class="cls-1" x1="7" x2="25" y1="25" y2="7"/></g></svg></button>';

				echo "</form></div> ";
				echo '</div>';
			}
			break;

			case 'posts':
			default:
			
			echo '
			<style>
				.post-item {display: flex; flex-direction: row; align-items: center; justify-content: space-between; font-size:.85rem;}
				.post-item button {background: red; color: #fff; border: none; border-radius: 5px !important;}
				.btn-row {display: flex; align-items: center; justify-content: space-between; gap: 10px;}
				
				.pagination {margin: 20px 0; display: flex; gap: 5px; justify-content: center;}
				.pagination a {padding: 5px 10px; text-decoration: none; border: 1px solid #ddd; border-radius: 3px; text-decoration:none !important;}
				.pagination a.active {background: #007bff;color: white;}
				
				.search-container {margin-bottom: 20px;}
				#searchInput {width:100%;padding:10px !important;box-sizing:border-box;}
				
				.sb-pill{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.75rem;font-weight:600;line-height:1.7;white-space:nowrap;vertical-align:middle}
				.sb-pill-published{background:#dcfce7;color:#15803d}
				.sb-pill-draft{background:#f3f4f6;color:#4b5563}
				.sb-pill-scheduled{background:#e0e7ff;color:#4338ca}
				.sb-post-meta{color:#6b7280;font-size:.75rem;margin-top:2px}
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
			global $SITEURL;

			foreach ($displayed_posts as $row) {
				$tags_str = !empty($row['tags']) ? ' | ' . i18n_r('simpleBlog/TAGS') . ': ' . implode(', ', $row['tags']) : '';

				$status_pill = $row['published']
					? '<span class="sb-pill sb-pill-published">' . i18n_r('simpleBlog/PUBLISHED') . '</span>'
					: '<span class="sb-pill sb-pill-draft">' . i18n_r('simpleBlog/DRAFT') . '</span>';
				$scheduled_pill = $row['scheduled_date']
					? ' <span class="sb-pill sb-pill-scheduled">' . i18n_r('simpleBlog/SCHEDULED') . ': ' . date('Y-m-d H:i', $row['scheduled_date']) . '</span>'
					: '';

				echo '<div class="post-item" data-title="' . htmlspecialchars($row['title']) . '" data-category="' . htmlspecialchars($row['category_name'] ?? '') . '">';
				echo "<p><b>{$row['title']}</b> - {$row['category_name']} &nbsp; " . $status_pill . $scheduled_pill . "</p>";
				echo "<p class='sb-post-meta'>" .
					i18n_r('simpleBlog/PUBLISHED_ON') . ": " . date('Y-m-d H:i', $row['date']) .
					(!empty($row['updated_at']) ? " <br> " . i18n_r('simpleBlog/LAST_EDITED_ON') . ": " . date('Y-m-d H:i', $row['updated_at']) : "") .
					"</p>
			<div class='btn-row'> 
				<a target='_blank' href='" . $SITEURL . get_setting($db, 'root_page') . "?post={$row['slug']}'>
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

			echo '
			<script>
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

	echo $pp;
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

function get_blog_content($slug = null){
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
			$metad = $result['metadata'];
			$title = $result['title'];

			$tags_stmt = $db->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
			$tags_stmt->bindValue(1, $result['id'], SQLITE3_INTEGER);
			$tags_result = $tags_stmt->execute();
			$tags = [];
			while ($tag = $tags_result->fetchArray()) {
				$tags[] = $tag['name'];
			}
			$tags_str = !empty($tags) ? '<p class="tags" style="display:none">' . i18n_r('simpleBlog/TAGS') . ': ' . implode(', ', $tags) . '</p>' : '';

			if (get_setting($db, 'use_slug_routing')) {
				$output .= "<p><small>$timeIcon " . date('Y-m-d', $result['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/category/" . checkCategory($result['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$result['category_name']} </a></small></p>";

			} else {
				$output .= "<p><small>$timeIcon " . date('Y-m-d', $result['date']) . " | <a href ='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&category=" . checkCategory($result['category_id']) . "'>" . i18n_r('simpleBlog/CATEGORY') . ": {$result['category_name']} </a></small></p>";
			}

			if ($result['cover_photo']) {
				$output .= "<img class='blogcover' src='" . sb_resolve_image_url($result['cover_photo'], $GLOBALS['SITEURL']) . "' alt='{$result['title']}' style='max-width:100%'>";
			}
			$output .= "<div class='blogcontent'>{$result['content']}</div>";
			$output .= sb_render_public_gallery($result['gallery'] ?? '', $GLOBALS['SITEURL'], $result['id'], $result['title']);
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
					$output .= "<a href='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/{$row['slug']}'> <img class='blogcover' src='" . sb_resolve_image_url($row['cover_photo'], $GLOBALS['SITEURL']) . "' alt='{$row['title']}' ></a>";
				} else {
					$output .= "<a href='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&post={$row['slug']}'> <img src='" . sb_resolve_image_url($row['cover_photo'], $GLOBALS['SITEURL']) . "' alt='{$row['title']}' class='blogcover'></a>";
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
					$output .= "<a href='" . $GLOBALS['SITEURL'] . get_setting($db, 'root_page') . "/{$row['slug']}'> <img src='" . sb_resolve_image_url($row['cover_photo'], $GLOBALS['SITEURL']) . "' alt='{$row['title']}'  class='blogcover'></a>";
				} else {
					$output .= "<a href='" . $GLOBALS['SITEURL'] . 'index.php?id=' . get_setting($db, 'root_page') . "&post={$row['slug']}'> <img src='" . sb_resolve_image_url($row['cover_photo'], $GLOBALS['SITEURL']) . "' alt='{$row['title']}' class='blogcover'></a>";
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

function blog_theme_content(){
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
};