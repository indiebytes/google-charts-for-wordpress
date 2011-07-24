<?php
/**
 * Google Charts for WordPress
 *
 * PHP version 5.3
 *
 * @category WordPress
 * @package  Google-charts-for-wordpress
 * @author   Andreas Karlsson <andreas.karlsson@indiebytes.se>
 * @license  GNU General Public License version 3 or later <http://www.gnu.org/licenses/>
 * @link     https://github.com/indiebytes/google-charts-for-wordpress
 */
 
/*
Plugin Name: Google Charts for WordPress
Plugin URI: https://github.com/indiebytes/google-charts-for-wordpress
Description: Adds a custom post type for Google Charts and a shortcode for including charts in posts and pages.
Version: 1.0.0
Author: Andreas Karlsson <andreas.karlsson@indiebytes.se>
Author URI: https://github.com/indiebytes/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Constants
 */
define('GOOGLE_CHARTS_FOR_WORDPRESS_VERSION', '1.0.0');

if (version_compare(phpversion(), '5.3.0', '<') === true) {
    $relPath = basename(dirname(__FILE__));
} else {
    $relPath = basename(__DIR__);
}

define('GOOGLE_CHARTS_FOR_WORDPRESS_PLUGIN_RELPATH', $relPath);
define(
    'GOOGLE_CHARTS_FOR_WORDPRESS_PLUGIN_URL',
    trailingslashit(
        plugins_url(
            $relPath
        )
    )
);

/**
 * Locale
 */
load_plugin_textdomain('gcwp', false, GOOGLE_CHARTS_FOR_WORDPRESS_PLUGIN_RELPATH . '/languages/');

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
 * @category WordPress
 * @package  Google-charts-for-wordpress
 * @author   Andreas Karlsson <andreas.karlsson@indiebytes.se>
 * @license  GNU General Public License version 3 or later <http://www.gnu.org/licenses/>
 * @link     https://github.com/indiebytes/google-charts-for-wordpress
 **/
class GoogleChartsForWordPress
{
    public $defaultData;
    
    /**
     * Constructor
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    public function __construct()
    {
        $this->defaultData = array(
            'chart_format' => 'pie-chart',
            'data_table' => array(
                'columns' => array(
                    array(__('Tasks', 'gcwp'), __('Write blog posts', 'gcwp'), __('Create charts', 'gcwp')),
                    array(__('Hours per week', 'gcwp'), 1, 1),
                )
            ),
            'columns' => 2,
            'rows' => 2
        );

        /**
         * Actions
         */
        add_action('init', array(&$this, 'createPostType'));
        add_action('init', array(&$this, 'init'));
        add_action('admin_init', array(&$this, 'adminInit'));
        add_action('save_post', array(&$this, 'postTypeSave'));
        add_action('admin_print_footer_scripts', array(&$this, 'jsLocale'));
        
        /**
         * Shortcodes
         */
        add_shortcode('googlechart', array(&$this, 'shortcode'));
    }

    /**
     * Locale variables for javascript
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function jsLocale()
    {
        printf('<script>var gcwp_delete = "%s";</script>', __('Delete', 'gcwp'));
    }

    /**
     * Initialization
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function init()
    {
        wp_register_script(
            'googlejsapi',
            'https://www.google.com/jsapi',
            array(), GOOGLE_CHARTS_FOR_WORDPRESS_VERSION
        );
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

        register_setting('gcwp', 'gcwp');

        /**
         * Register and enqueue stylesheet and javascript
         */
        wp_register_style(
            'gcwp_css',
            GOOGLE_CHARTS_FOR_WORDPRESS_PLUGIN_URL . 'css/style.css',
            array(), GOOGLE_CHARTS_FOR_WORDPRESS_VERSION
        );

        wp_register_script(
            'gcwp_js',
            GOOGLE_CHARTS_FOR_WORDPRESS_PLUGIN_URL . 'js/script.js',
            array(), GOOGLE_CHARTS_FOR_WORDPRESS_VERSION
        );

        wp_enqueue_style('gcwp_css');
        wp_enqueue_script('gcwp_js');
    }

    /**
     * Create post type
     *
     * @return void
     * @author Andreas Karlsson
     **/
    function createPostType()
    {
        register_post_type(
            'gcwp_chart',
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
    
    /**
     * List columns
     *
     * @return array
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function postTypeColumns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Title', 'gcwp'),
            'format' => __('Chart Format', 'gcwp'),
            'shortcode' => __('Shortcode', 'gcwp'),
        );
        return $columns;
    }

    /**
     * Column output for custom list columns
     *
     * @param string $column List column to generate output for
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function postTypeColumnOutput($column)
    {
        global $post;

        $serializedData = get_post_meta($post->ID, 'gcwp', true);
        $data = $serializedData ? unserialize($serializedData) : $this->defaultData;

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
    
    /**
     * Meta box for Google Chart format
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function postTypeMetaFormat()
    {
        global $post;
        $serializedData = get_post_meta($post->ID, 'gcwp', true);
        $data = $serializedData ? unserialize($serializedData) : $this->defaultData;
        ?>
        <div id="post-formats-select">
            <input type="radio" name="gcwp[chart_format]" class="post-format" id="post-format-pie" value="pie-chart"<?php checked('pie-chart', $data['chart_format']); ?>> <label for="post-format-pie"><?php _e('Pie Chart', 'gcwp'); ?></label>
            <br />
            <input type="radio" name="gcwp[chart_format]" class="post-format" id="post-format-column" value="column-chart"<?php checked('column-chart', $data['chart_format']); ?>> <label for="post-format-column"><?php _e('Column Chart', 'gcwp'); ?></label>
        </div>
        <?php echo '<input type="hidden" name="gcwp_noncename" id="gcwp_noncename" value="' .
            wp_create_nonce(plugin_basename(__FILE__)) . '" />'; ?>

        <?php
    }

    /**
     * Preview meta box
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function postTypeMetaPreview()
    { 
        global $post;
        ?>

        <div id="gcwp-preview">
            <p><?php _e('The preview chart is updated when the chart is saved or updated.', 'gcwp'); ?></p>
            <?php echo do_shortcode("[googlechart id='$post->ID']")?>
        </div>

        <?php
    }

    /**
     * Post meta box for data table
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function postTypeMetaDataTable()
    {
        global $post;
        $serializedData = get_post_meta($post->ID, 'gcwp', true);
        $data = $serializedData ? unserialize($serializedData) : $this->defaultData;
        ?>
        <div id="gcwp-pie-chart-information" class="gcwp-chart-information">
            <h4><?php _e('Data Tables for Pie Charts', 'gcwp'); ?></h4>
            <p>
                <?php _e('Two columns. The first column should be a string, and contain the slice label. The second column should be a number, and contain the slice value.', 'gcwp'); ?>
            </p>
        </div>

        <div id="gcwp-column-chart-information" class="gcwp-chart-information">
            <h4><?php _e('Data Tables for Column Charts', 'gcwp'); ?></h4>
            <p><?php _e('Each row in the table represents a group of adjacent bars. The first column in the table should be a string, and represents the label of that group of bars. Any number of columns can follow, all numeric, each representing the bars withthe same color and relative position in each group. The value at a given row and column controls the height of the single bar represented by this row and column.', 'gcwp'); ?></p>
        </div>
        
        <div class="gcwp-actions top-actions"><a href="#" class="button gcwp-add-row"><?php _e('Add New Row', 'gcwp'); ?></a> <a href="#" class="button gcwp-add-column"><?php _e('Add New Column', 'gcwp'); ?></a></div>
        <div id="gcwp-data">
    <?php
        $html = '<table>';
        $html .= '<tr><td></td>';

        foreach ($data['data_table']['columns'] as $columnIndex => $column) {
            if ($columnIndex > 1) {
                $html .= sprintf('<td class="action"><a href="#" class="gcwp-delete-column submitdelete">%s</a></td>', __('Delete', 'gcwp'));
            } else {
                $html .= '<td></td>';
            }
        }

        $html .= '<td></td></tr>';

        for ($i = 0; $i <= $data['rows']; $i++) {
            if ($i > 2) {
                $html .= sprintf('<tr><td class="action"><a href="#" class="gcwp-delete-row submitdelete">%s</a></td>', __('Delete', 'gcwp'));
            } else {
                $html .= '<tr><td></td>';
            }
            foreach ($data['data_table']['columns'] as $columnIndex => $column) {
                $html .= sprintf('<%s><input type="text" value="%s" name="gcwp[data_table][columns][%s][]" /></%s>', $i == 0 ? 'th' : 'td', $column[$i], $columnIndex, $i == 0 ? 'th' : 'td');
            }
            if ($i > 2) {
                $html .= sprintf('<td class="action"><a href="#" class="gcwp-delete-row submitdelete">%s</a></td></tr>', __('Delete', 'gcwp'));;
            } else {
                $html .= '<td></td></tr>';
            }
        }

        $html .= '<tr><td></td>';

        foreach ($data['data_table']['columns'] as $columnIndex => $column) {
            if ($columnIndex > 1) {
                $html .= sprintf('<td class="action"><a href="#" class="gcwp-delete-column submitdelete">%s</a></td>', __('Delete', 'gcwp'));
            } else {
                $html .= '<td></td>';
            }
        }

        $html .= '<td></td></tr>';
        $html .= '</table>';
        echo $html;
        ?>
        </div>
        <div class="gcwp-actions bottom-actions">
            <a href="#" class="button gcwp-add-row"><?php _e('Add New Row', 'gcwp'); ?></a>
            <a href="#" class="button gcwp-add-column"><?php _e('Add New Column', 'gcwp'); ?></a>
        </div>
    <?php
    }

    /**
     * Save Google Chart
     *
     * @param int $postId Id for the Google Chart post
     *
     * @return void
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
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

        if (!array_key_exists('gcwp_noncename', $_POST)
            && !wp_verify_nonce($_POST['gcwp_noncename'], plugin_basename(__FILE__))
        ) {
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

        $serializedData = serialize($data);

        if (get_post_meta($post->ID, 'gcwp', false)) {
            update_post_meta($post->ID, 'gcwp', $serializedData);
        } else {
            add_post_meta($post->ID, 'gcwp', $serializedData);
        }

        if (!$_POST['gcwp']) {
            delete_post_meta($post->ID, 'gcwp');
        }
    }

    /**
     * Shortcode to include Google Chart in post or page
     *
     * @param array $atts Attributes sent with the shortcode
     *
     * @return string
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
     **/
    function shortcode($atts)
    {
        extract(
            shortcode_atts(
                array(
                    'id' => null,
                    'width' => 450,
                    'height' => 300
                ), 
                $atts
            )
        );

        // 1. Get chart
        $post = get_post($id);

        // 2. Load and populate js-template based on chart type
        $serializedData = get_post_meta($id, 'gcwp', true);
        $data = unserialize($serializedData);
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

                    var chart = new google.visualization.$googleChartObject(
                        document.getElementById('gcwp-$id')
                    );
                    chart.draw(
                        data,
                        {width: $width, height: $height, title: '$post->post_title'}
                    );
                }
            </script>
JS;

        return sprintf('<div id="gcwp-%d"></div>%s', $id, $js);
    }

    /**
     * Generate data table in javascript
     *
     * @param array  $data   Information to build the data table from.
     * @param string $format Deprecated
     *
     * @return string
     * @author Andreas Karlsson <andreas.karlsson@indiebytes.se>
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
            $js .= sprintf(
                "data.addColumn('%s', '%s');",
                $columnIndex == 0 ? 'string' : 'number',
                $column[0] == '' ? __('Label missing', 'gcwp') : $column[0]
            );

            foreach ($column as $rowIndex => $value) {
                if ($rowIndex == 0 || $columnIndex > $data['columns']) {
                    continue;
                } else {
                    if ($value == '') {
                        $value = $columnIndex == 0 ? __('Label missing', 'gcwp') : 0;
                    }

                    $setValues .= sprintf(
                        'data.setValue(%d, %d, %s);',
                        $rowIndex-1,
                        $columnIndex,
                        $columnIndex == 0 ? "'$value'" : $value
                    );
                }
            }
        }

        $js .= sprintf('data.addRows(%d);', $data['rows']);
        $js .= $setValues;

        return $js;
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
