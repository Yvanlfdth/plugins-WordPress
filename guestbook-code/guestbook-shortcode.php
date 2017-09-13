<?php
class Guestbook_Shortcode {    
    public function __construct() {
        add_shortcode('guestbook_code_add', array($this, 'guestbook_add'));
        add_shortcode('guestbook_code_list', array($this, 'guestbook_list'));
    }
    
    public function guestbook_add($att, $content) {
        $checked_chambre = [];
        $checked_chambre[1] = (isset($_SESSION['guestbook_room']) && $_SESSION['guestbook_room'] == 1) ? "checked" : "";
        $checked_chambre[2] = (isset($_SESSION['guestbook_room']) && $_SESSION['guestbook_room'] == 2) ? "checked" : "";
        $message_erreur = (isset($_SESSION['guestbook_erreur'])) ? "<p class='rouge'>" . $_SESSION['guestbook_erreur'] . "</p>" : "";
        
        // Ajouter un message
        $html = "<h2>Ajouter un message</h2>";
        $html .= "<form class='padding-20-bottom guestbook-form' action='' method='post'>";
        $html .= "<label>Tous les champs sont requis mais votre adresse email ne sera pas affichée.</label>";
        $html .= "<label for='guestbook_name'>Votre nom</label>";
        $html .= "<input type='text' class='form-control' name='guestbook_name' id='guestbook_name' value='" . $_SESSION['guestbook_name'] . "' />";
        $html .= "<label for='guestbook_email'>Votre email</label>";
        $html .= "<input type='email' class='form-control' name='guestbook_email' id='guestbook_email' value='" . $_SESSION['guestbook_email'] . "' />";
        $html .= "<label for='guestbook_code'>Votre code</label>";
        $html .= "<input type='text' class='form-control' name='guestbook_code' id='guestbook_code' value='" . $_SESSION['guestbook_code'] . "' />";
        $html .= "<label for='guestbook_room'>Votre chambre</label>";
        $html .= "<select class='form-control' name='guestbook_room' id='guestbook_room'>";
        $html .= "<option value='1' $checked_chambre[1]>Chambre \"Potiron\"</option>";
        $html .= "<option value='2' $checked_chambre[2]>Chambre \"Noisette\"</option>";
        $html .= "</select>";
        $html .= "<label for='guestbook_message'>Votre message</label>";
        $html .= "<textarea class='form-control' name='guestbook_message' id='guestbook_message'>" . $_SESSION['guestbook_code'] . "</textarea>";
        $html .= "<input type='submit' name='guestbook_submit' id='guestbook_submit' value='Envoyer'>";
        $html .= $message_erreur;
        $html .= "</form>";
        
        echo $html;
    }
    
    public function guestbook_list($atts, $content)
    {
        //$atts = shortcode_atts(array('numberposts' => -1, 'order' => 'DESC', 'orderby' => 'date'), $atts);
        $args = array('limit' => -1, 'order' => 'DESC', 'orderby' => 'date');
        $messages = $this->getGuestbooksMessages($args);
        
        // Liste des messages
        $html = "<h2>Messages</h2>";
        if(!empty($messages)) {
            $html .= "<section class='padding-20-bottom'>";
            foreach ($messages as $message) {
                $html .= "<article class='guestbook-comment'>";
                $html .= "<div>";
                $html .= "<div class='guestbook-message'><em>" . nl2br(stripslashes($message->message)) . "</em></div>";
                $html .= "<div class='guestbook-infos-sup'>Ecrit par $message->name, le " . $this->formatDate($message->date) . "</div>";
                $html .= "</div>";
                $html .= "</article>";
            }
            $html .= "</section>";
        }
        else {
            $html .= "<p>Il n'y a aucun message enregistré. Soyez le premier !</p>";
        }
        echo $html;
    }
    
    public function getGuestbooksMessages($args) {
        global $wpdb;
        
        if($args['limit'] != -1) {
            $messages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}guestbook_messages ORDER BY " . $args['orderby']. " " . $args['order'] . "LIMIT " . $args['limit']);
        }
        else {
            $messages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}guestbook_messages ORDER BY " . $args['orderby']. " " . $args['order']);
        }
        
        return $messages;
    }
    
    public function formatDate($date) {
        return date("d/m/Y", strtotime($date));
    }
}