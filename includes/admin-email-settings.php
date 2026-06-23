<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// 1. Adicionar o submenu na página de Candidaturas
function sv_adicionar_submenu_email_settings() {
    add_submenu_page(
        'edit.php?post_type=candidatura',
        'Configurações de E-mail',
        'Configurações de E-mail',
        'manage_options',
        'sv-email-settings',
        'sv_renderizar_pagina_email_settings'
    );
}
add_action('admin_menu', 'sv_adicionar_submenu_email_settings');

// 2. Registrar as configurações usando a Settings API
function sv_registrar_configuracoes_email() {
    register_setting(
        'sv_email_templates_group', // Nome do grupo de configurações
        'sv_email_templates',       // Nome da opção no banco de dados
        'sv_sanitize_email_templates' // Função de sanitização (opcional, mas recomendado)
    );

    add_settings_section(
        'sv_email_templates_section',
        'Templates de E-mail por Status',
        'sv_email_templates_section_callback',
        'sv-email-settings'
    );

    $statuses = array(
        'em_analise' => 'Em Análise',
        'entrevista' => 'Entrevista Agendada',
        'aprovada'   => 'Aprovada',
        'rejeitada'  => 'Rejeitada',
        'finalizada' => 'Processo Finalizado'
    );

    foreach ($statuses as $key => $label) {
        // Campo para o Assunto do E-mail
        add_settings_field(
            'sv_email_subject_' . $key,
            'Assunto: ' . $label,
            'sv_render_email_field',
            'sv-email-settings',
            'sv_email_templates_section',
            [
                'type' => 'subject',
                'status' => $key,
                'label_for' => 'sv_email_subject_' . $key
            ]
        );
        // Campo para o Corpo da Mensagem
        add_settings_field(
            'sv_email_message_' . $key,
            'Mensagem: ' . $label,
            'sv_render_email_field',
            'sv-email-settings',
            'sv_email_templates_section',
            [
                'type' => 'message',
                'status' => $key,
                'label_for' => 'sv_email_message_' . $key
            ]
        );
    }
}
add_action('admin_init', 'sv_registrar_configuracoes_email');

// 3. Callback para a seção de configurações
function sv_email_templates_section_callback() {
    echo '<p>Personalize as mensagens de e-mail enviadas aos candidatos quando o status de suas candidaturas é alterado.</p>';
    echo '<h4>Placeholders Disponíveis:</h4>';
    echo '<p><code>[nome_candidato]</code> - O nome do candidato.<br>';
    echo '<code>[titulo_vaga]</code> - O título da vaga para a qual ele se candidatou.<br>';
    echo '<code>[nome_empresa]</code> - O nome da empresa contratante.<br>';
    echo '<code>[link_vaga]</code> - O link permanente para a página da vaga.<br>';
    echo '<code>[remuneracao]</code> - A remuneração/salário definido na vaga.<br>';
    echo '<code>[nome_site]</code> - O nome do seu site.</p>';
}

// 4. Função para renderizar os campos (Assunto e Mensagem)
function sv_render_email_field($args) {
    $options = get_option('sv_email_templates');
    $status = $args['status'];
    $type = $args['type'];
    $value = isset($options[$status][$type]) ? $options[$status][$type] : '';
    $name = 'sv_email_templates[' . $status . '][' . $type . ']';

    if ($type === 'subject') {
        echo "<input type='text' id='sv_email_subject_{$status}' name='{$name}' value='" . esc_attr($value) . "' class='regular-text' />";
    } else {
        echo "<textarea id='sv_email_message_{$status}' name='{$name}' rows='8' class='large-text'>" . esc_textarea($value) . "</textarea>";
    }
}

// 5. Função para renderizar a página de configurações
function sv_renderizar_pagina_email_settings() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('sv_email_templates_group');
            do_settings_sections('sv-email-settings');
            submit_button('Salvar Alterações');
            ?>
        </form>
    </div>
    <?php
}

// 6. Função de sanitização
function sv_sanitize_email_templates($input) {
    $new_input = [];
    if (is_array($input)) {
        foreach ($input as $status => $fields) {
            $status = sanitize_key($status);
            if (is_array($fields)) {
                $new_input[$status]['subject'] = sanitize_text_field($fields['subject']);
                $new_input[$status]['message'] = wp_kses_post($fields['message']);
            }
        }
    }
    return $new_input;
}