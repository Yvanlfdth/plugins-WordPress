<?php
/*
Plugin Name: Guestbook code
Plugin URI: /
Description: Génère des codes pour le livre d'or en admin. Les utilisateurs qui ont un code valide peuvent ajouter un message dans le livre d'or.
Version: 0.1
Author: Yvan le Fevere de ten Hove
Author URI: /
License: GPL2
*/

class Guestbook_Code {
    function __construct() {
        include_once plugin_dir_path( __FILE__ ).'/guestbook.php';
        include_once plugin_dir_path( __FILE__ ).'/guestbook-shortcode.php';
        register_activation_hook(__FILE__, array('Guestbook', 'install'));
        register_uninstall_hook(__FILE__, array('Guestbook', 'uninstall'));
        add_action('init', array($this, 'session_begin'));
        add_action('init', array($this, 'guestbook_style'));
        add_action('admin_init', array($this, 'guestbook_style'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        new Guestbook();
        new Guestbook_Shortcode();
    }
    
    function session_begin() {
        if (!session_id()) {
            session_start();
        }
    }
    
    function guestbook_style() {
        wp_register_style('guestbook_style', plugins_url('style.css',__FILE__ ));
        wp_enqueue_style('guestbook_style');
    }
    
    public function add_admin_menu() {
        add_menu_page('Guestbook code - Générer des codes', 'Guestbook code', 'manage_options', 'guestbook-code', array($this, 'content_page_generate'), 'dashicons-book');
        $hook_generate = add_submenu_page('guestbook-code', 'Générer des codes', 'Générer des codes', 'manage_options', 'guestbook-code-generate', array($this, 'content_page_generate'));
        $hook_messages = add_submenu_page('guestbook-code', 'Messages enregistrés', 'Messages enregistrés', 'manage_options', 'guestbook-code-messages', array($this, 'content_page_messages'));
        add_action('load-'.$hook_generate, array($this, 'process_action_generate'));
        add_action('load-'.$hook_messages, array($this, 'process_action_messages'));
        remove_submenu_page('guestbook-code','guestbook-code');
    }
    
    public function content_page_generate() {
        global $wpdb;
        
        $codes = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}guestbook_codes ORDER BY id ASC");
        ?>
        <h1><?=get_admin_page_title()?></h1>
        <p>Générer des codes pour le livre d'or</p>
        <form action="" method="post">
            <input type="hidden" name="generate_codes" value="1" />
            <?php submit_button("Générer 10 codes"); ?>
        </form>
        <h2>Liste des codes générés</h2>
        
        <?php if(!empty($codes)) : ?>
            <table class="guestbook-table">
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Utilisé</th>
                </tr>
                <?php $count = 1; ?>
                <?php foreach($codes as $code) : ?>
                    <?php
                        if($code->spent) {
                            $spent = "Utilisé";
                        }
                        else {
                            $spent = "Non utilisé";
                        }
                    ?>
                    <tr>
                        <td><?=$count?></td>
                        <td><?=$code->code?></td>
                        <td><?=$spent?></td>
                    </tr>
                    <?php $count++ ?>
                <?php endforeach; ?>
            </table>
        <?php else : ?>
            <p>Il n'y a aucun code généré.</p>
        <?php endif; ?>
        <?php
    }
    
    public function process_action_generate() {
        if (isset($_POST['generate_codes'])) {
            $codes = $this->generate_codes();
        }
    }
    
    public function content_page_messages() {
        global $wpdb;
        
        $messages = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}guestbook_messages ORDER BY date DESC");
        ?>
        <h1><?=get_admin_page_title()?></h1>
        <p>Liste des messages du livre d'or</p>
        <?php if(!empty($messages)) : ?>
            <form action="" method="post">
                <table class="guestbook-table">
                    <tr>
                        <th class="guestbook-list-number">#</th>
                        <th class="guestbook-list-name">Nom</th>
                        <th class="guestbook-list-email">Email</th>
                        <th class="guestbook-list-room">Chambre</th>
                        <th class="guestbook-list-message">Message</th>
                        <th class="guestbook-list-delete">Supprimer</th>
                    </tr>
                    <?php $count = 1; ?>
                    <?php foreach($messages as $message) : ?>
                        <tr>
                            <td class="guestbook-list-number"><?=$count?></td>
                            <td class="guestbook-list-name"><?=$message->name?></td>
                            <td class="guestbook-list-email"><?=$message->email?></td>
                            <td class="guestbook-list-room"><?=$this->getRoomByNumber($message->room)?></td>
                            <td class="guestbook-list-message"><?=nl2br(stripslashes($message->message))?></td>
                            <td class="guestbook-list-delete"><input type="checkbox" name="guestbook_message[<?=$message->id?>]" value="1" /></td>
                        </tr>
                        <?php $count++; ?>
                    <?php endforeach; ?>
                </table>
                <input type="hidden" name="delete_messages" value="1" />
                <?php submit_button("Supprimer la sélection"); ?>
            </form>
        <?php else : ?>
            <p>il n'y a pas encore de message enregistré dans le livre d'or.</p>
        <?php endif; ?>
        <?php
    }
    
    public function process_action_messages() {
        global $wpdb;
        
        if(isset($_POST['delete_messages'])) {
            foreach($_POST['guestbook_message'] as $key => $message) {
                $wpdb->query("DELETE FROM {$wpdb->prefix}guestbook_messages WHERE id = $key");
            }
        }
    }
    
    public function generate_codes($number = 10) {
        global $wpdb;
        
        $array_codes = [];
        for($i = 0; $i < $number; $i++) {
            $code = $this->randomString();
            $wpdb->insert("{$wpdb->prefix}guestbook_codes", array('code' => $code, 'spent' => 0));
            $array_codes[] = $code;
        }
        
        return $array_codes;
    }
    
    public function randomString($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters_length = strlen($characters);
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, $characters_length - 1)];
        }
        
        return $random_string;
    }
    
    public function getRoomByNumber($number) {
        if($number == 1) {
            return "Chambre \"Potiron\"";
        }
        elseif($number == 2) {
            return "Chambre \"Noisette\"";
        }
    }
}

new Guestbook_Code();