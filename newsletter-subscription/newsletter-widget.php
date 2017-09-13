<?php

class Newsletter_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct('newsletter', 'Newsletter', array('description' => 'Un formulaire d\'inscription à la newsletter.'));
    }
    
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo  $title; ?>" />
        </p>
        <?php
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        ?>
        <form action="" method="post">
            <p><?= apply_filters('widget_title', $instance['title']); ?></p>
            <input type="email" name="newsletter_email" placeholder="Votre email" />
            <input type="submit" name="newsletter_submit" value="S'inscrire" />
        </form>
        <?php
        echo $args['after_widget'];
    }
}