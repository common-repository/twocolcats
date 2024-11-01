<?php
/*
Plugin Name: twocolcats
Plugin URI: http://wordpress.org/plugins/twocolcats
Description: A simple widget that displays post categories in 2 or more columns.
Version: 2014.07.03
Author: Dino Chiesa
Author URI: http://www.dinochiesa.net
Donate URI: http://dinochiesa.github.io/TccWidgetDonate.html
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.txt
*/

class TwoColCatsWidget extends WP_Widget {
    /** constructor */
    function TwoColCatsWidget() {
        $opts = array('classname' => 'widget_twocolcats',
                     'description' => __( 'Display your categories in multiple columns') );
        parent::WP_Widget(false, $name = 'TwoColCats', $opts);

        // If in the future, I provide some possibilities for styling,
        // I may need to include the CSS and JS files here.
        //
        //$css = '/wp-content/plugins/gplus/css/gplus.css';
        //wp_enqueue_style('gplus', $css);
        //$js = '/wp-content/plugins/gplus/js/gplus.js';
        //wp_enqueue_script('gplus', $js);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;
        if ( $title ) {
            echo $before_title . $title . $after_title;
        }

        $this->renderCategories($instance);
        echo $after_widget;
    }

    function renderCategories($instance) {
        // $cols_to_show = intval($instance['cols_to_show']); // ignored
        $margin_right = intval($instance['margin_right']);
        $margin_bottom = intval($instance['margin_bottom']);
        $want_counts = $instance['want_counts'] ? 1 : 0;
        $nCols = intval($instance['cols_to_show']);
        if ($nCols < 0) { $nCols = 2; }
        elseif ($nCols > 8) { $nCols = 4; }

        echo "<div class='twocolcats'>\n";

        $opts = array( 'orderby' => 'name',
                     'order' => 'ASC' );
        $cats = get_categories($opts);

        // count up the non-empty categories.
        $n = 0;
        foreach($cats as $category) {
            if (intval($category->count) > 0) {
                $n++;
            }
        }

        $perCol = ceil($n/$nCols);

        $curCol = '';
        $i = 0;
        $c = 1;
        foreach($cats as $category) {
            if (intval($category->count) > 0) {
                $str = '<li><a href="' . get_category_link( $category->term_id ) .
                    '" title="' . sprintf( __( "View all posts in %s" ), $category->name ) .
                    '" >' . $category->name.'</a>' ;
                if ($want_counts) {
                    $str .= ' (' . $category->count . ')';
                }
                $str .= '</li>';
                $curCol .= $str;
                $i++;
                if ($i >= $perCol) {
                    echo "<ul class='col-" . $c .
                        "' style='float:left;margin-right:" . $margin_right .
                        "px;margin-bottom:". $margin_bottom . "px;'>\n" .
                        $curCol .
                        "\n</ul>\n";
                    $curCol = '';
                    $c++;
                    $i=0;
                }
            }
        }

        // last column
        echo "<ul class='col-" . $c .
            "' style='float:left;margin-bottom:". $margin_bottom . "px;'>\n" .
            $curCol .
            "\n</ul>\n";

        echo "</div>\n";
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['cols_to_show'] = intval($new_instance['cols_to_show']);
        $instance['margin_right'] = intval($new_instance['margin_right']);
        $instance['margin_bottom'] = intval($new_instance['margin_bottom']);
        $instance['want_counts'] = $new_instance['want_counts']?1:0;
        return $instance;
    }

    function renderFormTextBox($fieldId, $prompt, $value) {
        echo "  <p>\n" .
            "      <label for='" . $this->get_field_id($fieldId) . "'>" . __($prompt) .
            "</label>\n" .
            "      <input class='widefat' id='" . $this->get_field_id($fieldId) .
            "' name='" . $this->get_field_name($fieldId) .
            "' type='text' value='" .  $value ."'/>\n  </p>\n" ;
    }

    function renderFormCheckBox($fieldId, $prompt, $isChecked) {
        $checkState = $isChecked ? " checked='checked' " : " data-dpc='no' ";
        echo "  <p><input class='checkbox' type='checkbox'\n" .
            $checkState .
            "id='" . $this->get_field_id($fieldId) .
            "' name='" . $this->get_field_name($fieldId) . "' />\n" .
            "<label for='" . $this->get_field_id($fieldId) . "'>" .
            __($prompt) . "</label>\n</p>\n";
    }

    function form($instance) {
        $title = 'Categories';
        $cols_to_show = 2;
        $margin_right = 12;
        $margin_bottom = 18;
        $want_counts = 1;

        if ($instance) {
            $title = esc_attr($instance['title']);
            $cols_to_show = esc_attr($instance['cols_to_show']);
            $margin_right = esc_attr($instance['margin_right']);
            $margin_bottom = esc_attr($instance['margin_bottom']);
            $want_counts = $instance['want_counts']?1:0;
        }
        else
        {
            $defaults = array('title' => $title,
                              'cols_to_show' => 2,
                              'want_counts' => true,
                              'margin_bottom' => 18,
                              'margin_right' => 12);
            $instance = wp_parse_args( (array) $instance, $defaults );
        }

        $this->renderFormTextBox('title', 'Title:', $title);
        $this->renderFormTextBox('cols_to_show', '# of columns:', $cols_to_show);
        $this->renderFormTextBox('margin_right', 'margin between columns (pixels):',
                                 $margin_right);
        $this->renderFormTextBox('margin_bottom', 'bottom margin of columns (pixels):',
                                 $margin_bottom);
        $this->renderFormCheckBox('want_counts', 'display post counts', $want_counts);
    }
}


if ( !function_exists('dpc_emit_paypal_donation_button') ) {
    function dpc_emit_paypal_donation_button($widget, $clazzName, $buttonCode) {
        if (!is_object($widget)) {
            echo "Not object<br/>\n";
            return;
        }
        $clz = get_class($widget);
        if ($clz == $clazzName) {
            echo
                "<a target='_blank' " .
                "href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=" .
                $buttonCode . "'>" .
                "<img src='https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif' border='0' alt='donate via PayPal'>" .
                "</a>\n" ;
        }
    }
}

add_action( 'in_widget_form', 'twocolcats_appendDonation' );
function twocolcats_appendDonation($widget, $arg2=null, $arg3=null) {
    dpc_emit_paypal_donation_button($widget, 'TwoColCatsWidget', 'ES5T2NZ7BDLCE');
}


add_action( 'widgets_init', 'twocolcats_widget_init' );
function twocolcats_widget_init() {
    register_widget('TwoColCatsWidget');
}
