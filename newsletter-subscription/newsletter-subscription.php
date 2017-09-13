<?php
/*
Plugin Name: Newsletter subscription
Plugin URI: /
Description: Enregistre les inscriptions à la newsletter. Plugin développé pour le site "Chez Berthe".
Version: 0.1
Author: Yvan le Fevere de ten Hove
Author URI: /
License: GPL2
*/

class Newsletter_Subscription {
    function __construct() {
        include_once plugin_dir_path( __FILE__ ).'/newsletter.php';
        register_activation_hook(__FILE__, array('Newsletter', 'install'));
        register_uninstall_hook(__FILE__, array('Newsletter', 'uninstall'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        new Newsletter();
    }
    
    public function add_admin_menu() {
        $hook = add_menu_page('Newsletter subscription - Export CSV', 'Newsletter subscription', 'manage_options', 'newsletter-subscription', array($this, 'content_page'), 'dashicons-email-alt');
        add_action('load-'.$hook, array($this, 'process_action'));
    }
    
    public function content_page() {
        ?>
        <h1><?=get_admin_page_title()?></h1>
        <p>Générer un fichier .csv avec toutes les adresses email enregistrées.</p>
        <form action="" method="post">
            <input type="hidden" name="generate_csv" value="1" />
            <?php submit_button("Générer le csv"); ?>
        </form>
        <?php
    }
    
    public function process_action() {
        if (isset($_POST['generate_csv'])) {
            $this->generate_csv();
        }
    }
    
    public function generate_csv() {
        global $wpdb;
        
        $emails = $wpdb->get_results("SELECT email FROM {$wpdb->prefix}newsletter_email");
        if(!empty($emails)) {
            $fp = fopen('./newsletter-inscriptions.csv', 'w');     // Création du fichier csv
            fputcsv($fp, array("Emails"), ';');         // Intitulé de la première ligne
            // Chaque email est ajouté
            foreach ($emails as $email) {
                fputcsv($fp, array($email->email), ';');
            }
            fclose($fp);                        // Fermeture du fichier
            $file = './newsletter-inscriptions.csv';
        }
        
        // Récupération du fichier et téléchargement
        $fichier = "newsletter-inscriptions.csv";
        $chemin="./newsletter-inscriptions.csv";

        header('Content-disposition: attachment; filename="' . $fichier . '"');
        header('Content-Type: application/force-download');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '. filesize($chemin));
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        readfile($chemin);
    }
}

new Newsletter_Subscription();