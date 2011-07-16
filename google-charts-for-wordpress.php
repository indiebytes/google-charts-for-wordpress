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

        /*
        add_meta_box(
            "gcwp-columns",
            __('Columns', 'gcwp'),
            array(&$this, "postTypeMetaColumns"),
            "gcwp_chart",
            "normal",
            "high"
        );
        */

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
            plugins_url('css/style.css', __FILE__),
            array(), GOOGLE_CHARTS_FOR_WORDPRESS_VERSION
        );
        wp_register_script(
            'gcwp_js',
            plugins_url('/js/script.js', __FILE__),
            array(), GOOGLE_CHARTS_FOR_WORDPRESS_VERSION, true
        );
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
            'shortcode' => __('Shortcode', 'gcwp'),
        );
        return $columns;
    }

    function postTypeColumnOutput($column)
    {
        global $post;
        switch ($column) {
            case 'shortcode':
                printf('[googlechart id="%d"]', $post->ID);
            default:
                break;
        }
    }
    
    function postTypeMetaFormat()
    { ?>

    <div id="post-formats-select">
        <input type="radio" name="post_format" class="post-format" id="post-format-pie" value="pie-chart" checked="checked"> <label for="post-format-pie"><?php _e('Pie Chart', 'gcwp'); ?></label>
        <br />
        <input type="radio" name="post_format" class="post-format" id="post-format-column" value="column-chart"> <label for="post-format-column"><?php _e('Column Chart', 'gcwp'); ?></label>
    </div>
    <script>
        jQuery("#post-formats-select").delegate("input", "change", function() {
                jQuery(".gcwp-data").hide();
                jQuery("#gcwp-" + this.value).toggle();
            }
        );
    </script>
    <?php }

    function postTypeMetaPreview()
    { ?>

    <div id="gcwp-preview">Low priority, but nice to have.</div>

    <?php }

    function postTypeMetaColumns()
    { ?>

    <h4>Label</h4>
    <input type="text" />
    <h4>Column</h4>
    <table>
        <thead>
        <tr>
            <th>A</th>
            <th>B</th>
            <th>C</th>
        </tr>
        </thead>
    </table>
    <a href="#">Add another column</a>

    <?php }

    function postTypeMetaDataTable()
    { ?>
        <div id="gcwp-pie-chart" class="gcwp-data">
            <table>
                <tr>
                    <th>
                        <h4>Slices Title</h4>
                        <p><input type="hidden" value="0" /> <input type="text" value="Tasks" /></p>
                    </th>
                    <th>
                        <h4>Values Title</h4>
                        <p><input type="hidden" value="0" /> <input type="text" value="Hours per Day" /></p>
                    </th>
                    <!--<th>Color</th>-->
                </tr>
                <tr>
                    <td>
                        <input type="text" value="Work" />
                    </td>
                    <td>
                        <input type="text" value="8" />
                    </td>
                    <!--<td>
                        <input type="text" value="cccc00" />
                        <input type="hidden" value="1" />
                    </td>-->
                </tr>
                <tr>
                    <td>
                        <input type="text" value="Watch TV" />
                    </td>
                    <td>
                        <input type="text" value="12" />
                    </td>
                    <!--<td>
                        <input type="text" value="00cccc" />
                        <input type="hidden" value="1" />
                    </td>-->
                </tr>
            </table>
            <p><a href="#">Add row</a></p>
        </div>

        <div id="gcwp-column-chart" class="gcwp-data" style="display: none;">
            <table>
                <tr>
                    <th>
                        <h4>Bar Group Title</h4>
                        <p><input type="hidden" value="0" /> <input type="text" value="Year" /></p>
                    </th>
                    <th>
                        <h4>Values Title</h4>
                        <p><input type="hidden" value="0" /> <input type="text" value="Sales" /></p>
                    </th>
                    <th>
                        <h4>Values Title</h4>
                        <p><input type="hidden" value="0" /> <input type="text" value="Expenses" /></p>
                    </th>
                </tr>
                <tr>
                    <td>
                        <input type="text" value="2004" />
                    </td>
                    <td>
                        <input type="text" value="1000" />
                    </td>
                    <td>
                        <input type="text" value="400" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="text" value="2005" />
                    </td>
                    <td>
                        <input type="text" value="1170" />
                    </td>
                    <td>
                        <input type="text" value="460" />
                    </td>
                </tr>
            </table>
            <p><a href="#">Add row</a> | <a href="#">Add column</a></p>
        </div>
    <?php }

    function postTypeMeta() { echo "Lorem ipsum"; }
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
        
        // 2. Load and populate js-template based on chart type
        
        // 3. Return HTML and javascript

        $js = <<< JS
            <script type="text/javascript">
              google.load("visualization", "1", {packages:["corechart"]});
              google.setOnLoadCallback(drawChart);
              function drawChart() {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Task');
                data.addColumn('number', 'Hours per Day');
                data.addRows(5);
                data.setValue(0, 0, 'Work');
                data.setValue(0, 1, 11);
                data.setValue(1, 0, 'Eat');
                data.setValue(1, 1, 2);
                data.setValue(2, 0, 'Commute');
                data.setValue(2, 1, 2);
                data.setValue(3, 0, 'Watch TV');
                data.setValue(3, 1, 2);
                data.setValue(4, 0, 'Sleep');
                data.setValue(4, 1, 7);

                var chart = new google.visualization.PieChart(document.getElementById('gcwp-2'));
                chart.draw(data, {width: 450, height: 300, title: 'My Daily Activities'});
              }
            </script>
JS;

        return sprintf('<div id="gcwp-%d">%d</div>%s', $id, $id, $js);
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
            add_action(
                'admin_print_scripts-' . $page,
                array(&$this, 'addJs')
            );
            add_action(
                'admin_print_styles-' . $page,
                array(&$this, 'addCss')
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
