<?php
/*
 * Plugin Name: Static File Browser
 * Plugin URI: http://www.fabian-scheidt.de
 * Description: Lists the contents of <code>/wp-content/uploads/static/</code> in the media selection popover
 * Author: Fabian Scheidt
 * Author URI: http://www.fabian-scheidt.de/
 * Version: 1.0
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
*/

/**
 * Initializes the plugin by hooking into the media browser
 */
function sfb_init() {
    if ( current_user_can('upload_files') ) {
        add_filter('media_upload_tabs', 'sfb_media_menu');
        add_action('media_upload_file_browser', 'sfb_media_iframe');
    }
}

/**
 * Adds a tab to the media browser
 */
function sfb_media_menu($tabs) {
    $tabs['sfb'] = 'Static File Browser';
    return $tabs;
}

/**
 * Registers the iframe for the media browser
 */
function sfb_media_iframe() {
    wp_iframe('sfb_media_iframe_content');
}

/**
 * Returns the content of the file browser
 */
function sfb_media_iframe_content() {

    // Find dir for static uploads and the assiciated url
    $upload_dir = wp_upload_dir();
    $site_dir = $upload_dir['basedir'];
    $site_url = $upload_dir['baseurl'];
    $network_dir = preg_replace('/^(.*)\/sites\/[0-9]+$/', '$1', $site_dir);
    $network_url = preg_replace('/^(.*)\/sites\/[0-9]+$/', '$1', $site_url);
    $dir = $network_dir . '/static/';
    $url = $network_url . '/static/';

    if (!is_dir($dir)) {
        echo '<p>The folder <code>' . $dir . '</code> does not exist.';
        return;
    }

    // Find the relative paths of all files
    $files = sfb_get_dir_contents($dir);
    foreach ($files as $key => $file) {
        if (substr($file, 0, strlen($dir)) == $dir) {
            $files[$key] = substr($file, strlen($dir));
        }
    }

    ?>
    <style type="text/css">
        form {
            margin: 1em;
        }

        .insert-button {
            display: block;
            line-height: 36px;
            float: right;
            margin-right: 10px;
        }
    </style>
    <script type="text/javascript">
    function sfb_insert_file(filename) {
        var network_url = '<?=$url; ?>';
        var link = '<a href="' + network_url + filename + '" target="_blank">' + filename + '</a>';
        var win = window.dialogArguments || opener || parent || top;
        win.send_to_editor(link);
    }
    </script>

    <form class="media-upload-form">
        <p>Files in <code><?=$dir; ?></code></p>
        <div class="media-items">
            <?php foreach ($files as $file): ?>
            <div class="media-item">
                <a href="javascript:sfb_insert_file('<?=$file; ?>')" class="insert-button">Insert</a>
                <div class="filename">
                    <span class="title"><?=$file; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </form>
    <?php
}

/**
 * Lists the content of the provided directory recursively
 */
function sfb_get_dir_contents($dir, &$results = array()){
    $files = scandir($dir);

    foreach($files as $key => $value){
        $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
        if(!is_dir($path)) {
            $results[] = $path;
        } else if($value != "." && $value != "..") {
            sfb_get_dir_contents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}

// Init hook
add_action( 'init', 'sfb_init' );