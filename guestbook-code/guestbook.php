<?php
class Guestbook {
    public function __construct() {        
        add_action('wp_loaded', array($this, 'save_message'));
    }
    
    public static function install() {
        global $wpdb;
        
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}guestbook_codes (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(255) NOT NULL, spent TINYINT);");
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}guestbook_messages (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, message TEXT NOT NULL, room SMALLINT NOT NULL, date TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}guestbook_attempts (id INT AUTO_INCREMENT PRIMARY KEY, address_IP TEXT NOT NULL, date TIMESTAMP DEFAULT CURRENT_TIMESTAMP);");
    }
    
    public static function uninstall() {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}guestbook_codes;");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}guestbook_messages;");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}guestbook_attempts;");
    }
    
    public function save_message() {
        if(isset($_POST['guestbook_submit'])) {
            if (isset($_POST['guestbook_email'], $_POST['guestbook_name'], $_POST['guestbook_code'], $_POST['guestbook_room'], $_POST['guestbook_message']) &&
                !empty($_POST['guestbook_email']) && !empty($_POST['guestbook_name']) && !empty($_POST['guestbook_code']) && !empty($_POST['guestbook_room']) && !empty($_POST['guestbook_message'])
                ) {
                global $wpdb;
                
                $_SESSION['guestbook_email']      = $_POST['guestbook_email'];
                $_SESSION['guestbook_name']       = $_POST['guestbook_name'];
                $_SESSION['guestbook_code']       = $_POST['guestbook_code'];
                $_SESSION['guestbook_room']       = $_POST['guestbook_room'];
                $_SESSION['guestbook_message']    = $_POST['guestbook_message'];

                // Vérifie si l'utilisateur n'a pas essayé trop de fois un mauvais code
                $address_ip = $_SERVER['REMOTE_ADDR'];  // Adresse IP de l'utilisateur
                $date = time();
                $check_ip = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}guestbook_attempts WHERE address_IP = '$address_ip'");
                $count = 0;
                foreach($check_ip as $ip) {
                    if(($date - strtotime($ip->date)) <= 1440) {
                        $count++;
                    }
                }
                
                // Si l'utilisateur a moins de 10 tentatives (mauvais codes), le process suit son cours
                if($count < 10) {
                    $email      = $_POST['guestbook_email'];
                    $name       = $_POST['guestbook_name'];
                    $code       = $_POST['guestbook_code'];
                    $room       = $_POST['guestbook_room'];
                    $message    = $_POST['guestbook_message'];

                    $check_code = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}guestbook_codes WHERE code = '$code'");
                    if(!is_null($check_code)) {         // Le code existe en DB
                        if(!$check_code->spent) {       // Le code n'a pas encore été utilisé
                            if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $wpdb->insert("{$wpdb->prefix}guestbook_messages", array(
                                                                                           'email'      => $email,
                                                                                           'name'       => $name,
                                                                                           'room'       => $room,
                                                                                           'message'    => $message
                                                                                        ));
                                $wpdb->update("{$wpdb->prefix}guestbook_codes", array('spent' => 1), array('id' => $check_code->id));
                                unset($_SESSION['guestbook_email']);
                                unset($_SESSION['guestbook_name']);
                                unset($_SESSION['guestbook_code']);
                                unset($_SESSION['guestbook_room']);
                                unset($_SESSION['guestbook_message']);
                                if(isset($_SESSION['guestbook_erreur'])) {
                                    unset($_SESSION['guestbook_erreur']);
                                }
                            }
                            else {
                                $_SESSION['guestbook_erreur'] = "Veuillez entrer une adresse email valide";
                            }
                        }
                        else {
                            $_SESSION['guestbook_erreur'] = "Ce code a déjà été utilisé.";
                        }
                    }
                    else {
                        $wpdb->insert("{$wpdb->prefix}guestbook_attempts", array('address_ip' => $address_ip));     // Ajout de la tentative échouée en DB (la date est celle du jour)
                        $_SESSION['guestbook_erreur'] = "Ce code n'existe pas.";
                    }
                }
                else {
                    $_SESSION['guestbook_erreur'] = "Vous avez essayé trop de codes inexistants. Par mesure de sécurité, vous ne pouvez plus poster pendant 24 heures.";
                }
            }
            else {
                $_SESSION['guestbook_erreur'] = "Veuillez remplir tous les champs.";
            }
            
            wp_login_url(get_permalink());
        }
    }
}