<?php
/**
 * Transform a Wordpress DB into Dash/Snippets
 * @author mkrech
 * @license http://opensource.org/licenses/MIT MIT License
 */

require_once("SQLDB.php");
require_once("vendor/autoload.php");

use \Html2Text;

/**
 * DB config
 */
$filePath = __DIR__ . "/snippets.dash";

define('DB_NAME', 'ey-caramba');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');

/**
 * Create DB connects
 */
$pdo = new PDO('sqlite:' . $filePath);
$sQLiteDB = new SQLDB($pdo, 'sqlite');

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET, DB_USER, DB_PASSWORD);
$mysqlDB = new SQLDB($pdo, 'mysql');

/**
 * Empty Dash DB
 */
$sQLiteDB->truncate('snippets');
$sQLiteDB->truncate('tags');
$sQLiteDB->truncate('tagsIndex');

/**
 * Select wp_posts and insert into Dash DB
 */
$rows = $mysqlDB->select('wp_posts', array('ID', 'post_title', 'post_content'), "post_status = 'publish'");
foreach ($rows as $row) {
    $sid = $row['ID'];
    $post_title = trim($row['post_title']);
    $post_content = $row['post_content'];
    $h2t = new \Html2Text\Html2Text($post_content);
    $post_content = $h2t->getText();
    $sQLiteDB->insert('snippets', array('sid' => $sid, 'title' => $post_title, 'body' => $post_content, 'syntax' => 'standard', 'usageCount' => '0'));
}

/**
 * Select wp_terms and insert into Dash DB
 */
$rows = $mysqlDB->select('wp_terms', array('*'), '1 = 1');
foreach ($rows as $row) {
    $tag = trim($row['name']);
    $tid = trim($row['term_id']);
    $sQLiteDB->insert('tags', array('tid' => $tid, 'tag' => $tag));
}

/**
 * Select wp_terms wp_posts reference and insert into Dash DB
 */
$rows = $mysqlDB->select('wp_terms, wp_term_taxonomy, wp_term_relationships', array('object_id', 'wp_terms.term_id'), 'wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id AND
        wp_term_taxonomy.term_id = wp_terms.term_id');
foreach ($rows as $row) {
    $sid = trim($row['object_id']);
    $tid = trim($row['term_id']);
    $sQLiteDB->insert('tagsIndex', array('tid' => $tid, 'sid' => $sid));
}


/**
 * Download the Dash DB
 */
ignore_user_abort(true);
set_time_limit(0); // disable the time limit for this script

$fullPath = $filePath;

if ($fd = fopen($fullPath, "r")) {
    $fsize = filesize($fullPath);
    $path_parts = pathinfo($fullPath);
    $ext = strtolower($path_parts["extension"]);
    switch ($ext) {
        case "pdf":
            header("Content-type: application/pdf");
            header("Content-Disposition: attachment; filename=\"" . $path_parts["basename"] . "\""); // use 'attachment' to force a file download
            break;
        // add more headers for other content types here
        default;
            header("Content-type: application/octet-stream");
            header("Content-Disposition: filename=\"" . $path_parts["basename"] . "\"");
            break;
    }
    header("Content-length: $fsize");
    header("Cache-control: private"); //use this to open files directly
    while (!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
    }
}
fclose($fd);
exit;
