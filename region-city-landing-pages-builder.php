<?php
/*
  Plugin Name: Region city landing pages builder
  Plugin URI: http://sukanyasoftwares.com/shop/
  Description: Builds city,state geographically targeted landing pages in bulk. Use a custom page template and nest bulk pages under a parent page. Use the [ss-city] shortcode to make text relevant to that city.
  Version: 1.0.0
  Author: Sudhir Puranik
  Author URI: http://sukanyasoftwares.com/our-team/
  License: GPLv2 or later
  Text Domain: rclpb-plugin
 */

/*
  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */


if (is_admin()) {
    global $table_prefix, $wpdb;
    add_action('admin_menu', 'rclpb_auto_generate_plugin_admin_menu');

    // Functions to be called in wp admin
    function rclpb_auto_generate_plugin_admin_menu() {

        add_options_page('Manage Contents', 'Region city landing pages builder', 'administrator', 'content_settings', 'rclpb_ss_contents_settings_html_page');
    }

}

//Function Generates HTML to be displayed when admin want to enter new cities
function rclpb_ss_contents_settings_html_page() {
    global $table_prefix, $wpdb;

    if (isset($_REQUEST['action'])) {
        $cityname_arr = explode("\n", $_POST['cityname']);
        $title = sanitize_text_field($_POST['page_title']);
        //Cities Entered
        //  $cityname_san =  sanitize_text_field($cityname_arr);
        foreach ($cityname_arr as $cityname) {
            $cityname = sanitize_text_field($cityname);
            $title2 = str_ireplace("[ss-city]", $cityname, $title);
            $page_contents = str_ireplace("[ss-city]", $cityname, str_replace(array("\n", "\r", "\r\n", "\n\r"), " ", sanitize_text_field($_POST['page_contents'])));
            // post array with values to be provided to the wp_insert_post
            $post_array = array(
                'post_content' => $page_contents,
                'post_status' => 'draft',
                'post_title' => $title2,
                'post_type' => 'page',
                'post_name' => str_replace(" ", "", str_replace(",", "-", $cityname)) //post slug
            );
            // Post Array End
            $new_cityname = sanitize_text_field($cityname, 'rclpb-plugin');
            $parentpage = (int) sanitize_text_field($_POST['parent_page'], 'rclpb-plugin');
            if ($parentpage && $parentpage >= 1)
                $post_array['post_parent'] = $parentpage;


            $post = $post_array;
            $id = wp_insert_post($post);
            update_post_meta($id, 'ss-city', $new_cityname);

            $pagetemplate = (int) sanitize_text_field($_POST['page_template'], 'rclpb-plugin');
            if ($pagetemplate && $pagetemplate != '')
                update_post_meta($id, '_wp_page_template', $pagetemplate);
            $update_message = '<div id="message" class="updated">
            <p>
              Pages Created. Click here to <a href="edit.php?post_status=draft&post_type=page">View Pages</a>
            </p>
                       </div>';
        }
        //End Foreach cityname_arr
    }
    //END IF 'Action'
    else {
        //   echo "cityname is blank";
        $cityname = '';
        $update_message = '';
    }    //End Else
    ?>

    <div id="ss-city-form">
        <?php echo $update_message; ?>
        <h2><?php _e('Auto Generated Page Contents Options', 'rclpb-plugin'); ?></h2>
        <form method="post" action="" onsubmit="return validate_submit()">
            <?php wp_nonce_field('update-options'); ?>
            <table>
                <tr>
                    <td valign="top"><?php _e('City Text Field', 'rclpb-plugin'); ?></td>
                    <td>
                        <textarea id="cityname" type="text" cols="40" rows="5" name="cityname" <?php echo esc_textarea($_POST['cityname']); ?> placeholder="Ex: Alabama, Montgomery" ></textarea>
                        <br><label><?php _e("Please list one City,State per line", 'rclpb-plugin'); ?></label>
                    </td>
                </tr>
                <tr>
                    <td valign="top"><?php _e('Parent Page', 'rclpb-plugin'); ?></td>
                    <td><?php
                        $pages = get_pages(); // Gets all pages type posts
                        if ($pages && !empty($pages)) {
                            echo "<select name='parent_page' id='parent_page' ><option value='0'>None</option>";
                            foreach ($pages as $page) {
                                $option = '<option value="' . $page->ID . '">';
                                $option .= $page->post_title;
                                $option .= '</option>';
                                echo $option;
                            }
                            echo "</splect>";
                        }// End if Pages
                        ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top"><?php _e('Page Template', 'rclpb-plugin'); ?></td><td>

                        <?php
                        $templates = get_page_templates(); //Get all available templates in the current themes
                        if ($templates && !empty($templates)) {
                            echo "<select name='page_template' id='page_template' ><option value=''>Default</option>";
                            foreach ($templates as $template_name => $template_filename) {
                                $option = '<option value="' . $template_filename . '">';
                                $option .= $template_name;
                                $option .= '</option>';
                                echo $option;
                            }
                            echo "</splect>";
                        } //End IF Templates
                        ?>
                    </td>
                </tr>
                <tr>
                    <td valign="top"><?php _e('Page Title', 'rclpb-plugin'); ?></td><td>
                        <input placeholder="<?php _e('ex: Find Web Design in [ss-city]', 'rclpb-plugin'); ?>" type="text" name="page_title" id="page_title" size="70" value="<?php echo esc_attr($_POST['page_title']); ?>">
                    </td>
                </tr>
                <tr>
                    <td valign="top" ><?php _e('Page Contents', 'rclpb-plugin'); ?></td>
                    <td>
                        <textarea rows="15" cols="120"name="page_contents" id="page_contents" <?php echo esc_textarea($_POST['page_contents']); ?> ></textarea><br />
                        <?php _e('HTML Markup accepted as well as the [ss-city] shortcode. Shortcode will be transformed into your city name. That is, once the page is created [ss-city] will be replaced with the city name', 'rclpb-plugin'); ?>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="action" value="update" value="<?php echo esc_attr($_POST['action']); ?>"  >
            <p>
                <input type="submit" value="<?php _e('Create Pages', 'rclpb-plugin'); ?>" >
            </p>
        </form>
    </div>
    <script language="javascript">

            function validate_submit() // Form values validation function
            {
                msg = '';


                if (document.getElementById('cityname').value == '')
                {
                    msg = msg + "<?php _e('Please enter cityname', 'rclpb-plugin'); ?>\n";

                }
                if (document.getElementById('page_title').value == '')
                {
                    msg = msg + "<?php _e('Please enter page title', 'rclpb-plugin'); ?>\n";
                }
                if (document.getElementById('page_contents').value == '')
                {
                    msg = msg + "<?php _e('Please enter some contents', 'rclpb-plugin'); ?>\n";
                }
                if (msg == '')
                {
                    return true
                } //End If msg
                else
                {
                    alert(msg); // Message will be displayed to the user if there is any invalid values
                    return false;
                } //end else msg
            }// Validation FUnction Ends


    </script>    
    <?php
}

?>