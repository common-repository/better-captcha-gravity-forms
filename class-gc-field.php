<?php

if ( !class_exists( "GFForms" ) ) {
    die;
}
class GCField extends GF_Field
{
    public  $type = "gcaptcha" ;
    public function get_form_editor_field_title()
    {
        return esc_attr__( "Gravity Captcha", "gravitycaptcha" );
    }
    
    public function get_form_editor_button()
    {
        return array(
            "group" => "advanced_fields",
            "text"  => $this->get_form_editor_field_title(),
        );
    }
    
    function get_form_editor_field_settings()
    {
        return array(
            "error_message_setting",
            "conditional_logic_field_setting",
            //"label_setting",
            //"label_placement_setting",
            "admin_label_setting",
            //"size_setting",
            "placeholder_setting",
            //"default_value_setting",
            //"visibility_setting",
            "description_setting",
        );
    }
    
    public function is_conditional_logic_supported()
    {
        return false;
    }
    
    public function get_field_input( $form, $value = "", $entry = null )
    {
        $form_id = absint( $form["id"] );
        $is_entry_detail = $this->is_entry_detail();
        $is_form_editor = $this->is_form_editor();
        $id = $this->id;
        $field_id = ( $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_{$id}" : "input_" . $form_id . "_{$id}" );
        $size = $this->size;
        $class_suffix = ( $is_entry_detail ? "_admin" : "" );
        $class = $size . $class_suffix;
        $css_class = trim( esc_attr( $class ) . " gfield_select" );
        $tabindex = $this->get_tabindex();
        $disabled_text = ( $is_form_editor ? "disabled='disabled'" : "" );
        $required_attribute = ( $this->isRequired ? "aria-required='true'" : "" );
        $invalid_attribute = ( $this->failed_validation ? "aria-invalid='true'" : "aria-invalid='false'" );
        $placeholder_attribute = $this->get_field_placeholder_attribute();
        $add_style = " style='background: #f7f7f7;padding: 10px 10px 14px;height: auto;margin-bottom: 0px !important;' ";
        $get_question_id = $this->__get( "question_id" );
        $question = gravity_captcha()->get_question_by_id( $get_question_id );
        //$value = $question["answer"];
        
        if ( isset( $question["placeholder"] ) && $question["placeholder"] != "" ) {
            $placeholder_value = $question["placeholder"];
            $placeholder_attribute = sprintf( "placeholder='%s'", esc_attr( $placeholder_value ) );
        }
        
        $input = "<input name='input_gcaptcha_{$id}' id='gcaptcha_{$field_id}' type='hidden' value='" . $question["id"] . "' />";
        $input .= "<input name='input_{$id}' id='{$field_id}' type='text' value='{$value}' class='{$class}' {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text} {$add_style}/>";
        
        if ( $is_entry_detail ) {
            $questions = gform_get_meta( $entry["id"], "gcaptcha_validated" );
            $question = $questions[$id];
            $value = $question["answer"];
            $input = "";
            $input .= "<input name='input_gcaptcha_{$id}' id='gcaptcha_{$field_id}' type='hidden' value='" . $question['id'] . "' />";
            $input .= "<input name='input_{$id}' id='{$field_id}' type='text' value='{$value}' class='{$class}' {$tabindex} {$placeholder_attribute} {$required_attribute} {$invalid_attribute} {$disabled_text}/>";
        }
        
        return sprintf( "<div class='ginput_container ginput_container_text'>%s</div>", $input );
    }
    
    public function get_field_placeholder_attribute()
    {
        $placeholder_value = GFCommon::replace_variables_prepopulate( $this->placeholder );
        return ( !rgblank( $placeholder_value ) ? sprintf( "placeholder='%s'", esc_attr( $placeholder_value ) ) : "" );
    }
    
    public function get_field_content( $value, $force_frontend_label, $form )
    {
        $field_label = $this->get_field_label( $force_frontend_label, $value );
        $validation_message_id = "validation_message_" . $form["id"] . "_" . $this->id;
        $validation_message = ( $this->failed_validation && !empty($this->validation_message) ? sprintf( "<div id='%s' class='gfield_description validation_message' aria-live='polite'>%s</div>", $validation_message_id, $this->validation_message ) : "" );
        $is_form_editor = $this->is_form_editor();
        $is_entry_detail = $this->is_entry_detail();
        $is_admin = $is_form_editor || $is_entry_detail;
        $required_div = ( $is_admin || $this->isRequired ? sprintf( "<span class='gfield_required'>%s</span>", ( $this->isRequired ? "*" : "" ) ) : "" );
        $admin_buttons = $this->get_admin_buttons();
        $target_input_id = $this->get_first_input_id( $form );
        $for_attribute = ( empty($target_input_id) ? "" : "for='{$target_input_id}'" );
        $question = $question = gravity_captcha()->get_question();
        $this->__set( "question_id", $question["id"] );
        $field_label = $question["question"];
        //." ".$question["id"];
        $description = $this->get_description( $this->description, "gfield_description" ) . " " . gravity_captcha()->get_question_description();
        $help = '';
        
        if ( $this->is_description_above( $form ) ) {
            $clear = ( $is_admin ? "<div class='gf_clear'></div>" : "" );
            $field_content = sprintf(
                "%s<label class='%s gcaptcha' {$for_attribute} >%s%s {$help}</label>%s{FIELD}%s{$clear}",
                $admin_buttons,
                esc_attr( $this->get_field_label_class() ),
                esc_html( $field_label ),
                $required_div,
                $description,
                $validation_message
            );
        } else {
            $field_content = sprintf(
                "%s<label class='%s gcaptcha' {$for_attribute} >%s%s {$help}</label>{FIELD}%s%s",
                $admin_buttons,
                esc_attr( $this->get_field_label_class() ),
                esc_html( $field_label ),
                $required_div,
                $description,
                $validation_message
            );
        }
        
        $field_input = $this->get_field_input( $form, $value );
        $field_content = str_replace( "{FIELD}", $field_input, $field_content );
        return $field_content;
    }
    
    public function validate( $value, $form )
    {
        $field_id = $this->id;
        
        if ( isset( $_POST["input_gcaptcha_" . $field_id] ) ) {
            $question_id = sanitize_text_field( $_POST["input_gcaptcha_" . $field_id] );
        } else {
            return $validate;
        }
        
        $question_id = $question_id;
        $answer = $value;
        $questions = gravity_captcha()->get_questions();
        $questions = wp_list_pluck( $questions, "answer", "id" );
        
        if ( !array_key_exists( $question_id, $questions ) ) {
            $this->failed_validation = true;
            $this->validation_message = esc_html__( "Captcha Expired. Refresh the Form.", "gravitycaptcha" );
        } else {
            
            if ( $value == $questions[$question_id] ) {
                $this->__set( "question_id", $question_id );
            } else {
                $this->failed_validation = true;
                $this->validation_message = ( empty($this->errorMessage) ? esc_html__( "Incorrect Response to Captcha", "gravitycaptcha" ) : $this->errorMessage );
            }
        
        }
        
        return;
    }
    
    public function get_value_entry_list(
        $value,
        $entry,
        $field_id,
        $columns,
        $form
    )
    {
        $return = esc_html( $value );
        return GFCommon::selection_display( $return, $this, $entry["currency"] );
    }
    
    public function get_value_entry_detail(
        $value,
        $currency = "",
        $use_text = false,
        $format = "html",
        $media = "screen"
    )
    {
        $return = esc_html( $value );
        return GFCommon::selection_display(
            $return,
            $this,
            $currency,
            $use_text
        );
    }
    
    public function sanitize_entry_value( $value, $form_id )
    {
        $value = wp_strip_all_tags( $value );
        return $value;
    }
    
    public function get_filter_operators()
    {
        $operators = ( $this->type == "product" ? array( "is" ) : array(
            "is",
            "isnot",
            ">",
            "<"
        ) );
        return $operators;
    }

}
GF_Fields::register( new GCField() );