<?php
 
class eztweet_widget extends WP_Widget {
 
    function __construct(){
        // Constructor del Widget
        $widget_ops = array('classname' => 'eztweet_widget', 'description' => "Ez Single Tweet" );
        parent::__construct('eztweet_widget', "Ez Single Tweet", $widget_ops);
    }
 
    function widget($args,$instance){
        // Contenido del Widget que se mostrará en la Sidebar
        extract($args);
        $id = preg_replace("/[^0-9]/","",$args["widget_id"]);
        echo $before_widget; 
        $title =  $instance["eztweet_title"];
        $description = $instance["eztweet_descr"];
        $times = (isset($instance["eztweet_times"])) ? $instance["eztweet_times"] : 1;
        if (isset($title))
                echo "<h3 class=\"widget-title\">".$title."</h3>";
        if (isset($description))
                echo "<p id=\"widget-desc\">".$description."</p>";
        $tweets = new eztweet_plugin;
        echo $tweets->print_the_tweets_forwidget($times);
        echo $after_widget;
    }
 
    function update($new_instance, $old_instance){
        // Función de guardado de opciones  
        $instance = $old_instance;
        $instance["eztweet_title"] = strip_tags($new_instance["eztweet_title"]);
        $instance["eztweet_descr"] = strip_tags($new_instance["eztweet_descr"]);
        $instance["eztweet_times"] = strip_tags($new_instance["eztweet_times"]);
        // Repetimos esto para tantos campos como tengamos en el formulario.
        return $instance;      
    }
 
    function form($instance){
        // Formulario de opciones del Widget, que aparece cuando añadimos el Widget a una Sidebar
            $defaults = array( 'eztweet_categories' => array() );
            $instance = wp_parse_args( (array) $instance, $defaults );    
     ?>
        <p>
            <label for="<?php echo $this->get_field_id('eztweet_title'); ?>"><?php _e('Title','eztweet'); ?></label>
            <input type="text" id="<?php echo $this->get_field_id('eztweet_title'); ?>" name="<?php echo $this->get_field_name('eztweet_title'); ?>" <?php if (isset($instance["eztweet_title"])) { ?> value="<?php echo $title = $instance["eztweet_title"]; ?>" <?php } ?>>
        </p>
         <p>
            <label for="<?php echo $this->get_field_id('eztweet_descr'); ?>"><?php _e('Description','eztweet'); ?></label>
            <input type="text" id="<?php echo $this->get_field_id('eztweet_descr'); ?>" name="<?php echo $this->get_field_name('eztweet_descr'); ?>" <?php if (isset($instance["eztweet_descr"])) { ?> value="<?php echo $title = $instance["eztweet_descr"]; ?>" <?php } ?>>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('eztweet_times'); ?>"><?php _e('Tweets to Display','eztweet'); ?></label>
            <select name="<?php echo $this->get_field_name('eztweet_times'); ?>" id="<?php echo $this->get_field_id('eztweet_times'); ?>">
                <?php
                for($i=1;$i<=20;$i++) {
                    echo "<option value=\"" . $i . "\" ";
                    if (isset($instance["eztweet_times"]) && $instance["eztweet_times"] == $i) {
                        echo "selected=\"selected\"";
                    }
                    echo ">" . $i . "</option>";
                } ?>
                </select>
        </p>
        <?php
    }    
} 
?>