<?php

GFForms::include_addon_framework();
class GravityCaptchaAddon extends GFAddon
{
    protected  $_version = ZZD_GC_VER ;
    protected  $_min_gravityforms_version = "1.9" ;
    protected  $_full_path = __FILE__ ;
    protected  $_title = "Gravity Captcha" ;
    protected  $_short_title = "Gravity Captcha" ;
    protected  $_slug = "gravitycaptcha" ;
    private static  $_instance = null ;
    public static function get_instance()
    {
        if ( self::$_instance == null ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function pre_init()
    {
        parent::pre_init();
        if ( $this->is_gravityforms_supported() && class_exists( "GF_Field" ) && file_exists( ZZD_GC_DIR . "class-gc-field.php" ) ) {
            require_once ZZD_GC_DIR . "class-gc-field.php";
        }
        $initial_questions = ( rgget( ZZD_GC_BOOTSTRAP ) == ZZD_GC_INNER_SLUG ? $this->set_questions() : $this->get_questions() );
    }
    
    public function init()
    {
        parent::init();
        require_once GFCommon::get_base_path() . "/tooltips.php";
        wp_register_style(
            "gc-css",
            ZZD_GC_URL . "assets/css/gravity-captcha.css",
            array(),
            rand( 1, 100 )
        );
        wp_register_script(
            "gc-js",
            ZZD_GC_URL . "assets/js/gcaptcha.js",
            array( "jquery" ),
            rand( 1, 100 )
        );
        wp_register_script(
            "gc-js-admin",
            ZZD_GC_URL . "assets/js/gcaptcha-admin.js",
            array( "jquery" ),
            rand( 1, 100 )
        );
        wp_enqueue_style( "gc-css" );
        add_filter(
            "gform_addon_navigation",
            array( $this, "add_admin_page" ),
            10,
            1
        );
        add_filter(
            "gform_filter_links_entry_list",
            array( $this, "gform_filter_links_entry_list" ),
            10,
            3
        );
        add_action(
            "gform_post_entry_list",
            array( $this, "gform_post_entry_list" ),
            10,
            1
        );
        add_action( "admin_enqueue_scripts", array( $this, "enqueue_required_scripts" ) );
        add_action( "wp_enqueue_scripts", array( $this, "enqueue_required_scripts" ) );
        add_filter(
            "gform_noconflict_styles",
            array( $this, "gform_noconflict_styles_fn" ),
            10,
            1
        );
        add_filter(
            "gform_noconflict_scripts",
            array( $this, "gform_noconflict_scripts_fn" ),
            10,
            1
        );
        add_action( "admin_head", array( $this, "set_gc_assets_and_inline_css" ) );
        add_action( "admin_init", array( $this, "set_entry_status_from_admin" ) );
        add_action(
            "gform_after_submission",
            array( $this, "gform_after_submission" ),
            100,
            2
        );
        add_filter(
            "gform_entry_is_spam",
            array( $this, "enable_spam_link" ),
            10,
            3
        );
        add_action(
            "gform_update_status",
            array( $this, "check_if_spam" ),
            10,
            3
        );
        add_filter(
            "gform_admin_pre_render",
            array( $this, "gform_admin_pre_render" ),
            10,
            1
        );
        add_filter(
            "gform_custom_merge_tags",
            array( $this, "gform_custom_merge_tags" ),
            10,
            4
        );
        add_filter(
            "gform_replace_merge_tags",
            array( $this, "gform_replace_merge_tags" ),
            10,
            7
        );
        add_action( "gform_editor_js_set_default_values", array( $this, "gform_editor_js_set_default_values" ) );
    }
    
    public function enqueue_required_scripts()
    {
        $base_url = GFCommon::get_base_url();
        wp_enqueue_script( "gc-js" );
        if ( rgget( "page" ) == ZZD_GC_ADMIN_PAGE ) {
            wp_enqueue_script( "gc-js-admin" );
        }
        if ( rgget( "page" ) != ZZD_GC_ADMIN_PAGE ) {
            return;
        }
        $scripts = array(
            "wp-lists",
            "wp-ajax-response",
            "thickbox",
            "gform_json",
            "gform_field_filter",
            "sack"
        );
        foreach ( $scripts as $script ) {
            wp_enqueue_script( $script );
        }
    }
    
    function gform_noconflict_styles_fn( $styles )
    {
        $styles[] = 'gc-css';
        return $styles;
    }
    
    function gform_noconflict_scripts_fn( $scripts )
    {
        $scripts[] = 'gc-js';
        $scripts[] = 'gc-js-admin';
        return $scripts;
    }
    
    public function set_entry_status_from_admin()
    {
        if ( rgget( "page" ) != ZZD_GC_ADMIN_PAGE ) {
            return;
        }
        $action = "reportentrygc";
        
        if ( rgget( "gcaptcha" ) == $action && is_admin() && current_user_can( ZZD_GC_CAP ) ) {
            $form = rgget( "form" );
            $lead_id = rgget( "lead" );
            $status = "spam";
            GFFormsModel::update_entry_property( $lead_id, "status", $status );
            $url = admin_url( "admin.php" );
            $query_args = array(
                "page"      => ZZD_GC_ADMIN_PAGE,
                "id"        => $form,
                "gcsuccess" => 1,
            );
            $send_url = add_query_arg( $query_args, $url );
            wp_redirect( $send_url );
            exit;
        }
        
        
        if ( rgget( "gcsuccess" ) == 1 ) {
            $type = "error";
            $text = __( "Entry Marked as Spam", "gravitycaptcha" );
            $key = "gravitycaptcha";
            GFCommon::add_dismissible_message( $text, $key, $type );
        }
    
    }
    
    public function set_gc_assets_and_inline_css()
    {
        if ( rgget( "page" ) != ZZD_GC_ADMIN_PAGE ) {
            return;
        }
        $_GET["filter"] = "spam";
        echo  "<style>#entry_search_container, #doaction, #bulk-action-selector-top, #doaction2, #bulk-action-selector-bottom{ display: none; }</style>" ;
    }
    
    public function gform_filter_links_entry_list( $filter_links, $form, $include_counts )
    {
        if ( rgget( "page" ) != ZZD_GC_ADMIN_PAGE ) {
            return $filter_links;
        }
        $all_link = false;
        foreach ( $filter_links as $key => $link ) {
            if ( $link["id"] == "all" ) {
                $all_link = $link;
            }
            
            if ( $link["id"] != "spam" ) {
                unset( $filter_links[$key] );
            } else {
                $filter_links[$key]["label"] = __( "Spam Submissions", "gravitycaptcha" );
            }
        
        }
        return $filter_links;
    }
    
    public function gform_replace_merge_tags(
        $text,
        $form,
        $entry,
        $url_encode,
        $esc_html,
        $nl2br,
        $format
    )
    {
        $lead_id = $entry["id"];
        $form_id = $form["id"];
        $url = admin_url( "admin.php" );
        $query_args = array(
            "page"     => ZZD_GC_ADMIN_PAGE,
            "lead"     => $lead_id,
            "form"     => $form_id,
            "id"       => $form_id,
            "gcaptcha" => "reportentrygc",
        );
        $send_url = add_query_arg( $query_args, $url );
        $text = str_replace( "{spam_report_link_href}", $send_url, $text );
        $html = __( "Was this entry a spam? Click here to report", "gravitycaptcha" );
        $link = "<a href='{$send_url}'>{$html}</a>";
        $text = str_replace( "{spam_report_link}", $link, $text );
        return $text;
    }
    
    public function gform_custom_merge_tags(
        $group,
        $form_id,
        $fields,
        $element_id
    )
    {
        $group[] = array(
            "tag"   => "{spam_report_link_href}",
            "label" => esc_html__( "Gravity Captcha Spam Report Link URL", "gravitycaptcha" ),
        );
        $group[] = array(
            "tag"   => "{spam_report_link}",
            "label" => esc_html__( "Gravity Captcha Spam Report Link", "gravitycaptcha" ),
        );
        return $group;
    }
    
    public function gform_after_submission( $entry, $form )
    {
        $setup_captcha = array();
        foreach ( $form["fields"] as $field ) {
            
            if ( $field->type == "gcaptcha" && isset( $field->question_id ) && $field->question_id != "" ) {
                $question_id = $field->question_id;
                $question = $this->get_question_by_id( $question_id );
                
                if ( $question ) {
                    $setup_captcha[$field->id] = $question;
                    $this->send_submission( $question_id );
                }
            
            }
        
        }
        if ( empty($setup_captcha) ) {
            $setup_captcha = false;
        }
        gform_update_meta( $entry["id"], "gcaptcha_validated", $setup_captcha );
    }
    
    public function enable_spam_link( $is_spam, $form, $lead )
    {
        return $is_spam;
    }
    
    public function check_if_spam( $lead_id, $property_value, $previous_value )
    {
        $status = rgpost( "status" );
        if ( $status == "" ) {
            
            if ( $property_value == "active" && $previous_value == "spam" ) {
                $status = "unspam";
            } else {
                if ( $property_value == "spam" && $previous_value == "active" ) {
                    $status = "spam";
                }
            }
        
        }
        $entry = GFAPI::get_entry( $lead_id );
        $form = GFAPI::get_form( $entry["form_id"] );
        switch ( $status ) {
            case "spam":
                $this->report_question_to_server( 1, $lead_id, $form_id );
                break;
            case "unspam":
                $this->report_question_to_server( 0, $lead_id, $form_id );
                break;
        }
    }
    
    public function report_question_to_server( $is_spam, $lead_id, $form_id )
    {
        $meta = gform_get_meta( $lead_id, "gcaptcha_validated" );
        if ( isset( $meta ) && $meta ) {
            foreach ( $meta as $key => $question ) {
                $this->update_questions_database( $question["id"], $is_spam );
            }
        }
    }
    
    public function update_questions_database( $question_id, $spam )
    {
        $site_url = site_url();
        $url = "https://gcapi.zerozendesign.com/update.php";
        $body = array(
            'postquestionid' => $question_id,
            'site_url'       => $site_url,
            'spam'           => $spam,
        );
        $args = array(
            'body' => $body,
        );
        $response = wp_remote_post( $url, $args );
        $data = false;
        if ( !is_wp_error( $response ) && isset( $response['body'] ) ) {
            $data = $response['body'];
        }
        return $data;
    }
    
    public function send_submission( $question_id )
    {
        $site_url = site_url();
        $url = "https://gcapi.zerozendesign.com/submission.php";
        $body = array(
            'postquestionid' => $question_id,
            'site_url'       => $site_url,
        );
        $args = array(
            'body' => $body,
        );
        $response = wp_remote_post( $url, $args );
        $data = false;
        if ( !is_wp_error( $response ) && isset( $response['body'] ) ) {
            $data = $response['body'];
        }
        // echo "<pre>"; print_r($response); echo "</pre>";
        return $data;
    }
    
    public function gform_admin_pre_render( $form )
    {
        
        if ( isset( $_GET["lid"] ) && isset( $_GET["id"] ) && isset( $_GET["page"] ) && $_GET["page"] == "gf_entries" ) {
            $entry_id = sanitize_text_field( $_GET["lid"] );
            $questions = gform_get_meta( $entry_id, "gcaptcha_validated" );
            foreach ( $form["fields"] as $key => $field ) {
                if ( $field->type == "gcaptcha" ) {
                    
                    if ( isset( $questions[$field->id] ) ) {
                        $question = $questions[$field->id];
                        $new_label = $question["question"];
                        $field->label = $new_label;
                        $form["fields"][$key] = $field;
                    }
                
                }
            }
        }
        
        return $form;
    }
    
    public function get_questions()
    {
        $questions = get_option( "gravity_captcha_questions", false );
        $new_questions = false;
        
        if ( !$questions ) {
            $new_questions = $this->set_questions();
        } else {
            if ( current_time( "timestamp" ) > $questions["time"] + MONTH_IN_SECONDS ) {
                $new_questions = $this->set_questions();
            }
        }
        
        if ( $new_questions ) {
            $questions = $new_questions;
        }
        $this->question_descriptions();
        return $questions["opts"];
    }
    
    public function set_questions()
    {
        $questions = $this->get_questions_from_api();
        $update_value = false;
        
        if ( $questions ) {
            $questions = json_decode( $questions, true );
            $questions = $this->setup_options( $questions );
            $update_value = array(
                "time" => current_time( "timestamp" ),
                "opts" => $questions,
            );
            update_option( "gravity_captcha_questions", $update_value );
            
            if ( rgget( ZZD_GC_BOOTSTRAP ) == ZZD_GC_INNER_SLUG ) {
                echo  "<pre>" ;
                print_r( $questions );
                echo  "</pre>" ;
                exit;
            }
        
        }
        
        return $update_value;
    }
    
    public function get_questions_from_api()
    {
        $url = "https://gcapi.zerozendesign.com/read.php";
        $args = array();
        $response = wp_remote_post( $url, $args );
        $body = false;
        if ( !is_wp_error( $response ) && isset( $response['body'] ) ) {
            $body = $response['body'];
        }
        return $body;
    }
    
    public function question_descriptions()
    {
        $url = "https://gcapi.zerozendesign.com/description.php";
        $args = array();
        $response = wp_remote_get( $url, $args );
        $body = get_option( "question_description", true );
        
        if ( !is_wp_error( $response ) && isset( $response['body'] ) ) {
            $body = $response['body'];
            update_option( "question_description", $body );
        }
        
        return $body;
    }
    
    public function get_help_message()
    {
        $lockicon = ZZD_GC_URL . "assets/images/lock.svg";
        //Hide Lock Icon to avoid
        return "<div class='help-tip'>\r\n\t\t\t<img src='{$lockicon}' height=18 width=18>\r\n\t\t\t<p>this Spam Captcha is powered by Gravity Captcha.</p>\r\n\t\t</div>";
    }
    
    public function get_question_description()
    {
        $description = get_option( "question_description", true );
        $help_message = "<div class='help-msg'>" . $this->get_help_message( true ) . "</div>";
        $description = $help_message . $description;
        $description = str_replace( "help-tip", "help-tip-bottom", $description );
        return $description;
    }
    
    public function setup_options( $questions )
    {
        $setup_questions = array();
        foreach ( $questions as $question ) {
            $question["question"] = strrev( base64_decode( $question["question"] ) );
            $question["answer"] = strrev( base64_decode( $question["answer"] ) );
            $question["placeholder"] = strrev( base64_decode( $question["placeholder"] ) );
            $setup_questions[] = $question;
        }
        return $setup_questions;
    }
    
    public function get_question()
    {
        $questions = $this->get_questions();
        $random = array_rand( $questions, 1 );
        $question = $questions[$random];
        return $question;
    }
    
    public function get_question_by_id( $question_id )
    {
        $questions = $this->get_questions();
        $result = false;
        foreach ( $questions as $question ) {
            if ( $question["id"] == $question_id ) {
                $result = $question;
            }
        }
        return $result;
    }
    
    public function add_admin_page( $addon_menus )
    {
        $addon_menus[] = array(
            "label"      => __( "Spam Submissions", "gravitycaptcha" ),
            "permission" => "update_plugins",
            "name"       => ZZD_GC_ADMIN_PAGE,
            "callback"   => array( "GFForms", "all_leads_page" ),
        );
        return $addon_menus;
    }
    
    public function gform_get_entries_args_entry_list( $args )
    {
        if ( rgget( "page" ) != ZZD_GC_ADMIN_PAGE ) {
            return $args;
        }
        $args["search_criteria"] = array(
            "status" => "spam",
        );
        return $args;
    }
    
    public function gform_post_entry_list( $form_id )
    {
        if ( rgget( "gcsuccess" ) != 1 ) {
            return;
        }
        echo  "<div class='admin-zzd-message'>\r\n\t\t\t\t<div class='message-wrapper'>\r\n\t\t\t\t\t<p>Thanks for your feedback. The captcha entry you reported will be reviewed by our team. If you marked this as spam in error, please contact us at <a href='mailto:generalsupport@zerozendesign.com'>generalsupport@zerozendesign.com</a></p>\r\n\t\t\t\t</div>\r\n\t\t</div>" ;
    }
    
    public function gform_editor_js_set_default_values()
    {
        ?>
		case "gcaptcha" :		
		field.label = <?php 
        echo  json_encode( esc_html__( "Gravity Captcha", "gravitycaptcha" ) ) ;
        ?>;
		field.isRequired = true;		
		break;
		<?php 
    }

}
function gravity_captcha()
{
    return GravityCaptchaAddon::get_instance();
}
