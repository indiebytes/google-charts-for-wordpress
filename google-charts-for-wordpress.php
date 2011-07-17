<?php
/*
Plugin Name: Google Charts for WordPress
Plugin URI: 
Description: Adds a custom post type for Google Charts and a shortcode for including charts in posts and pages.
Version: 1.0.0
Author: Andreas Karlsson <andreas.karlsson@indiebytes.se>
Author URI: http://andreaskarlsson.info/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Constants
 */
define('GOOGLE_CHARTS_FOR_WORDPRESS_VERSION', '1.0.0');
define('GOOGLE_CHARTS_FOR_WORDPRESS_PLUGIN_URL', plugin_dir_url(__FILE__ ));

/**
 * Locale
 */
if (!load_plugin_textdomain('gcwp', false, '/wp-content/languages/')) {
    load_plugin_textdomain(
        'gcwp',
        false,
        dirname(__FILE__) . '/languages/'
    );
}

/**
 * Google Charts For WordPress
 *
 * Pie Charts
 * ------------------------------------------------------------------------------
 * Two columns. The first column should be a string, and contain the slice label.
 * The second column should be a number, and contain the slice value.
 *
 * Column Charts
 * ------------------------------------------------------------------------------
 * Each row in the table represents a group of adjacent bars. The first column in
 * the table should be a string, and represents the label of that group of bars. 
 * Any number of columns can follow, all numeric, each representing the bars with
 * the same color and relative position in each group. The value at a given row
 * and column controls the height of the single bar represented by this row and
 * column.
 *
 * @package default
 * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
 **/
class GoogleChartsForWordPress
{
    public $defaultData = array(
        'chart_format' => 'pie-chart',
        'data_table' => array(
            'columns' => array(
                array('Tasks', 'Write a blog post', 'Create a Google Chart'),
                array('Hours per week', 1, 1),
            )
        ),
        'columns' => 2,
        'rows' => 2
    );
    
    /**
     * Constructor
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    public function __construct()
    {
        /**
         * Activation and deactivation
         */
        register_activation_hook(__FILE__, array(&$this, 'activation'));
        register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
        
        /**
         * Actions
         */
        add_action('init', array(&$this, 'createPostType'));
        add_action('init', array(&$this, 'init'));
        add_action('admin_init', array(&$this, 'adminInit'));
        add_action('admin_menu', array(&$this, 'menu'));
        add_action('save_post', array(&$this, 'postTypeSave'));
        
        /**
         * Shortcodes
         */
        add_shortcode('googlechart', array(&$this, 'shortcode'));
    }

    /**
     * Plugin activation
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function activation()
    {
    }

    /**
     * Plugin activation
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function deactivation()
    {
    }

    function init()
    {
        wp_register_script('googlejsapi', 'https://www.google.com/jsapi');
        wp_enqueue_script('googlejsapi');
    }

    /**
     * Initialize
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function adminInit()
    {
        /**
         * Post type columns
         */
        add_filter(
            "manage_edit-gcwp_chart_columns",
            array(&$this, "postTypeColumns")
        );
        add_action(
            "manage_posts_custom_column",
            array(&$this, "postTypeColumnOutput")
        );

        /**
         * Post type meta boxes
         */
        add_meta_box(
            "gcwp-format",
            __('Format', 'gcwp'),
            array(&$this, "postTypeMetaFormat"),
            "gcwp_chart",
            "side",
            "low"
        );

        add_meta_box(
            "gcwp-preview",
            __('Preview', 'gcwp'),
            array(&$this, "postTypeMetaPreview"),
            "gcwp_chart",
            "normal",
            "high"
        );

        add_meta_box(
            "gcwp-data-table",
            __('Data Table', 'gcwp'),
            array(&$this, "postTypeMetaDataTable"),
            "gcwp_chart",
            "normal",
            "low"
        );

        wp_register_style(
            'gcwp_css',
            plugins_url(basename(dirname(plugin_basename(__FILE__))) . '/css/style.css'),
            array(), GOOGLE_CHARTS_FOR_WORDPRESS_VERSION
        );
        wp_register_script(
            'gcwp_js',
            plugins_url(basename(dirname(plugin_basename(__FILE__))) . '/js/script.js')
        );
        wp_enqueue_style('gcwp_css');
        wp_enqueue_script('gcwp_js');

        register_setting('gcwp', 'gcwp');
    }

    /**
     * Create post type
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function createPostType()
    {
        register_post_type('gcwp_chart',
            array(
                'labels' => array(
                    'name' => __('Google Charts', 'gcwp'),
                    'singular_name' => __('Google Chart', 'gcwp'),
                    'add_new' => __('Add New', 'gcwp'),
                    'add_new_item' => __('Add New Chart', 'gcwp'),
                    'edit_item' => __('Edit Chart', 'gcwp'),
                    'new_item' => __('New Chart', 'gcwp'),
                    'view_item' => __('View Chart', 'gcwp'),
                    'search_items' => __('Search Charts', 'gcwp'),
                    'not_found' =>  __('No charts found', 'gcwp'),
                    'not_found_in_trash' => __('No charts found in Trash', 'gcwp'),
                    'parent_item_colon' => '',
                    'menu_name' => __('Google Charts', 'gcwp'),
                ),
                'show_ui' => true,
                'has_archive' => false,
                'show_in_menu' => true,
                'menu_position' => 15,
                'supports' => array(
                    'title',
                ),
            )
        );
    }
    function postTypeColumns($columns)
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Title', 'gcwp'),
            'format' => __('Chart Format', 'gcwp'),
            'shortcode' => __('Shortcode', 'gcwp'),
        );
        return $columns;
    }

    function postTypeColumnOutput($column)
    {
        global $post;
        $json = get_post_meta($post->ID, 'gcwp_json', true);
        $data = $json ? unserialize($json) : $this->defaultData;
        switch ($column) {
            case 'format':
                $googleChartObject = '';
                foreach (explode('-', $data['chart_format']) as $word) {
                    $googleChartObject .= ucfirst($word);
                }
                echo $googleChartObject;
                break;
            case 'shortcode':
                printf('[googlechart id="%d"]', $post->ID);
                break;
            default:
                break;
        }
    }
    
    function postTypeMetaFormat()
    {
        global $post;
        $json = get_post_meta($post->ID, 'gcwp_json', true);
        $data = $json ? unserialize($json) : $this->defaultData;
?>
    <div id="post-formats-select">
        <input type="radio" name="gcwp[chart_format]" class="post-format" id="post-format-pie" value="pie-chart"<?php checked('pie-chart', $data['chart_format']); ?>> <label for="post-format-pie"><?php _e('Pie Chart', 'gcwp'); ?></label>
        <br />
        <input type="radio" name="gcwp[chart_format]" class="post-format" id="post-format-column" value="column-chart"<?php checked('column-chart', $data['chart_format']); ?>> <label for="post-format-column"><?php _e('Column Chart', 'gcwp'); ?></label>
    </div>
    <?php echo '<input type="hidden" name="gcwp_noncename" id="gcwp_noncename" value="' .
            wp_create_nonce(plugin_basename(__FILE__)) . '" />'; ?>

    <?php }

    function postTypeMetaPreview()
    { 
        global $post;
?>

    <div id="gcwp-preview">
        <p><?php _e('The preview chart is updated when the chart is saved or updated.', 'gcwp'); ?></p>
        <?php echo do_shortcode("[googlechart id='$post->ID']")?>
    </div>

    <?php }

    function postTypeMetaDataTable()
    { ?>
        <div class="gcwp-actions top-actions"><a href="#" class="button gcwp-add-row">Add New Row</a> <a href="#" class="button gcwp-add-column">Add New Column</a></div>
        <div id="gcwp-data">
            <?php
                global $post;
                $json = get_post_meta($post->ID, 'gcwp_json', true);
                $data = $json ? unserialize($json) : $this->defaultData;
                $html = '<table>';
                $html .= '<tr>';
                foreach ($data['data_table']['columns'] as $columnIndex => $column) {
                    if ($columnIndex > 1) {
                        $html .= '<td class="action"><a href="#" class="gcwp-delete-column submitdelete">Delete</a></td>';
                    } else {
                        $html .= '<td></td>';
                    }
                }
                $html .= '</tr>';
                for ($i = 0; $i <= $data['rows']; $i++) {
                    $html .= '<tr>';
                    foreach ($data['data_table']['columns'] as $columnIndex => $column) {
                        $html .= sprintf('<%s><input type="text" value="%s" name="gcwp[data_table][columns][%s][]" /></%s>', $i == 0 ? 'th' : 'td', $column[$i], $columnIndex, $i == 0 ? 'th' : 'td');
                    }
                    $html .= '</tr>';
                }
                $html .= '<tr>';
                foreach ($data['data_table']['columns'] as $columnIndex => $column) {
                    if ($columnIndex > 1) {
                        $html .= '<td class="action"><a href="#" class="gcwp-delete-column submitdelete">Delete</a></td>';
                    } else {
                        $html .= '<td></td>';
                    }
                }
                $html .= '</tr>';
                $html .= '</table>';
                echo $html;

            ?>
        </div>
        <div class="gcwp-actions bottom-actions"><a href="#" class="button gcwp-add-row">Add New Row</a> <a href="#" class="button gcwp-add-column">Add New Column</a></div>
    <?php }

    function postTypeMeta() { echo "Lorem ipsum"; }

    function encodeItems(&$item, $key)
    {
        $item = utf8_encode($item);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function postTypeSave($postId)
    {
        global $post;

        /**
         * Verify if this is an auto save routine. If it is our form has not
         * been submitted, so we dont want to do anything.
         **/
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post === null || $post->post_type == 'revision') {
            return;
        }

        if (!wp_verify_nonce($_POST['gcwp_noncename'], plugin_basename( __FILE__ ))) {
            return;
        }

        // Check permissions
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $postId)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $postId)) {
                return;
            }
        }

        $data = $_POST['gcwp'];
        $data['rows'] = count($data['data_table']['columns'][0]) - 1;
        $data['columns'] = count($data['data_table']['columns']);

        $json = serialize($data);

        if (get_post_meta($post->ID, 'gcwp_json', false)) {
            update_post_meta($post->ID, 'gcwp_json', $json);
        } else {
            add_post_meta($post->ID, 'gcwp_json', $json);
        }

        if (!$_POST['gcwp']) {
            delete_post_meta($post->ID, 'gcwp_json');
        }
    }

    /**
     * Shortcode
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function shortcode($atts) {
        extract(
            shortcode_atts(
                array(
                    'id' => null,
                    'title' => null,
                ), 
                $atts
            )
        );

        // 1. Get chart
        $post = get_post($id);

        // 2. Load and populate js-template based on chart type
        $json = get_post_meta($id, 'gcwp_json', true);
        $data = unserialize($json);
        $jsDataTable = $this->generateJsDataTable($data);

        $googleChartObject = '';

        foreach (explode('-', $data['chart_format']) as $word) {
            $googleChartObject .= ucfirst($word);
        }

        // 3. Return HTML and javascript
        $js = <<< JS
            <script type="text/javascript">
                google.load("visualization", "1", {packages:["corechart"]});
                google.setOnLoadCallback(drawChart);
                function drawChart() {
                    $jsDataTable

                    var chart = new google.visualization.$googleChartObject(document.getElementById('gcwp-$id'));
                    chart.draw(data, {width: 450, height: 300, title: '$post->post_title'});
                }
            </script>
JS;

        return sprintf('<div id="gcwp-%d"></div>%s', $id, $js);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function generateJsDataTable($data, $format = 'javascript')
    {
        if (!is_array($data)) {
            return '';
        }

        $js = 'var data = new google.visualization.DataTable();';
        $setValues = '';

        if ($data['chart_format'] == 'pie-chart') {
            $data['columns'] = 2;
        }

        foreach ($data['data_table']['columns'] as $columnIndex => $column) {
            $js .= sprintf("data.addColumn('%s', '%s');", $columnIndex == 0 ? 'string' : 'number', $column[0] == '' ? __('Label missing', 'gcwp') : $column[0]);

            foreach ($column as $rowIndex => $value) {
                if ($rowIndex == 0 || $columnIndex > $data['columns']) {
                    continue;
                } else {
                    if ($value == '') {
                        $value = $columnIndex == 0 ? __('Label missing', 'gcwp') : 0;
                    }

                    $setValues .= sprintf('data.setValue(%d, %d, %s);', $rowIndex-1, $columnIndex, $columnIndex == 0 ? "'$value'" : $value);
                }
            }
        }

        $js .= sprintf('data.addRows(%d);', $data['rows']);
        $js .= $setValues;

        return $js;
    }

    /**
     * Menu
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function menu()
    {
        if (function_exists('add_submenu_page')) {
            $page = add_submenu_page(
                'options-general.php',
                __('Google Charts for WordPress', 'gcwp'),
                __('Google Charts for WordPress', 'gcwp'),
                'manage_options',
                'gcwp-config',
                array(&$this, 'page')
            );
        }
    }

    /**
     * Load settings page
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function page()
    {
        // require_once 'admin/settings.php';
    }

    /**
     * Enqueue stylesheets
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function addCss()
    {
        wp_enqueue_style('gcwp_css');
    }

    /**
     * Enqueue javascipt
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function addJs()
    {
        wp_enqueue_script('gcwp_js');
    }
}

$gcwp = new GoogleChartsForWordPress();
