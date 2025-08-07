<?php
/*
Plugin Name: Sistema de Vagas
Plugin URI:  https://seusite.com/sistema-vagas 
Description: Sistema completo para gerenciar vagas e candidaturas no WordPress.
Version:     1.2
Author:      RUGEMTUGEM
Author URI:  https://rugemtugem.com.br
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html 
Text Domain: sistema-vagas
*/

if (!defined("ABSPATH")) {
    exit; // Exit if accessed directly
}

// Define constantes do plugin
define("SISTEMA_VAGAS_PLUGIN_URL", plugin_dir_url(__FILE__));
define("SISTEMA_VAGAS_PLUGIN_PATH", plugin_dir_path(__FILE__));

// Inclui os arquivos necessários
require_once SISTEMA_VAGAS_PLUGIN_PATH . "includes/cpt-vagas.php";
require_once SISTEMA_VAGAS_PLUGIN_PATH . "includes/cpt-candidaturas.php";
require_once SISTEMA_VAGAS_PLUGIN_PATH . "includes/formulario.php";
require_once SISTEMA_VAGAS_PLUGIN_PATH . "includes/admin-candidaturas.php";
require_once SISTEMA_VAGAS_PLUGIN_PATH . "includes/admin-email-settings.php";

// Hook de ativação do plugin
register_activation_hook(__FILE__, "sistema_vagas_ativar");

function sistema_vagas_ativar() {
    // Registra os CPTs
    sv_criar_cpt_vagas();
    sv_criar_cpt_candidaturas();
    
    // Força a atualização das regras de rewrite
    flush_rewrite_rules();
    
    // Cria tabelas personalizadas se necessário
    sv_criar_tabelas_candidaturas();
}

// Hook de desativação do plugin
register_deactivation_hook(__FILE__, "sistema_vagas_desativar");

function sistema_vagas_desativar() {
    // Limpa as regras de rewrite
    flush_rewrite_rules();
}

// Função para criar tabelas personalizadas
function sv_criar_tabelas_candidaturas() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'candidaturas_vagas';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vaga_id bigint(20) NOT NULL,
        nome varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        telefone varchar(50),
        linkedin text,
        pretensao_salarial varchar(100),
        mensagem text,
        curriculo_url text,
        status varchar(50) DEFAULT 'nova',
        data_candidatura datetime DEFAULT CURRENT_TIMESTAMP,
        ip_candidato varchar(45),
        user_agent text,
        PRIMARY KEY (id),
        KEY vaga_id (vaga_id),
        KEY email (email),
        KEY status (status),
        KEY data_candidatura (data_candidatura)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

