<?php
// Registrar Custom Post Type Candidaturas
function sv_criar_cpt_candidaturas() {
    $labels = array(
        'name'                  => 'Candidaturas',
        'singular_name'         => 'Candidatura',
        'menu_name'             => 'Candidaturas',
        'name_admin_bar'        => 'Candidatura',
        'archives'              => 'Arquivo de Candidaturas',
        'attributes'            => 'Atributos da Candidatura',
        'parent_item_colon'     => 'Candidatura Pai:',
        'all_items'             => 'Todas as Candidaturas',
        'add_new_item'          => 'Adicionar Nova Candidatura',
        'add_new'               => 'Adicionar Nova',
        'new_item'              => 'Nova Candidatura',
        'edit_item'             => 'Editar Candidatura',
        'update_item'           => 'Atualizar Candidatura',
        'view_item'             => 'Ver Candidatura',
        'view_items'            => 'Ver Candidaturas',
        'search_items'          => 'Buscar Candidaturas',
        'not_found'             => 'Nenhuma candidatura encontrada',
        'not_found_in_trash'    => 'Nenhuma candidatura encontrada na lixeira',
        'featured_image'        => 'Foto do Candidato',
        'set_featured_image'    => 'Definir foto do candidato',
        'remove_featured_image' => 'Remover foto do candidato',
        'use_featured_image'    => 'Usar como foto do candidato',
        'insert_into_item'      => 'Inserir na candidatura',
        'uploaded_to_this_item' => 'Enviado para esta candidatura',
        'items_list'            => 'Lista de candidaturas',
        'items_list_navigation' => 'Navegação da lista de candidaturas',
        'filter_items_list'     => 'Filtrar lista de candidaturas',
    );

    $args = array(
        'label'                 => 'Candidatura',
        'description'           => 'Candidaturas recebidas para as vagas',
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'custom-fields'),
        'taxonomies'            => array(),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 21,
        'menu_icon'             => 'dashicons-groups',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'post',
        'show_in_rest'          => false,
    );
    
    register_post_type('candidatura', $args);
}
add_action('init', 'sv_criar_cpt_candidaturas');

// Adicionar colunas personalizadas na listagem de candidaturas no admin
function sv_candidaturas_colunas_admin($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = 'Candidato';
    $new_columns['vaga_relacionada'] = 'Vaga';
    $new_columns['email_candidato'] = 'Email';
    $new_columns['telefone_candidato'] = 'Telefone';
    $new_columns['status_candidatura'] = 'Status';
    $new_columns['data_candidatura'] = 'Data da Candidatura';
    
    return $new_columns;
}
add_filter('manage_candidatura_posts_columns', 'sv_candidaturas_colunas_admin');

// Preencher as colunas personalizadas
function sv_candidaturas_colunas_conteudo($column, $post_id) {
    switch ($column) {
        case 'vaga_relacionada':
            $vaga_id = get_post_meta($post_id, '_candidatura_vaga_id', true);
            if ($vaga_id) {
                $vaga = get_post($vaga_id);
                if ($vaga) {
                    echo '<a href="' . get_edit_post_link($vaga_id) . '">' . esc_html($vaga->post_title) . '</a>';
                } else {
                    echo '<span style="color: #dc3545;">Vaga removida</span>';
                }
            } else {
                echo '<span style="color: #6c757d;">Não informado</span>';
            }
            break;
            
        case 'email_candidato':
            $email = get_post_meta($post_id, '_candidatura_email', true);
            if ($email) {
                echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
            } else {
                echo '<span style="color: #6c757d;">Não informado</span>';
            }
            break;
            
        case 'telefone_candidato':
            $telefone = get_post_meta($post_id, '_candidatura_telefone', true);
            if ($telefone) {
                echo '<a href="tel:' . esc_attr($telefone) . '">' . esc_html($telefone) . '</a>';
            } else {
                echo '<span style="color: #6c757d;">Não informado</span>';
            }
            break;
            
        case 'status_candidatura':
            $status = get_post_meta($post_id, '_candidatura_status', true);
            $status = $status ? $status : 'nova';
            
            $status_colors = array(
                'nova' => '#007cba',
                'em_analise' => '#f0b849',
                'aprovada' => '#46b450',
                'rejeitada' => '#dc3232',
                'entrevista' => '#9b59b6',
                'finalizada' => '#6c757d'
            );
            
            $status_labels = array(
                'nova' => 'Nova',
                'em_analise' => 'Em Análise',
                'aprovada' => 'Aprovada',
                'rejeitada' => 'Rejeitada',
                'entrevista' => 'Entrevista',
                'finalizada' => 'Finalizada'
            );
            
            $color = isset($status_colors[$status]) ? $status_colors[$status] : '#6c757d';
            $label = isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status);
            
            echo '<span style="color: ' . $color . '; font-weight: bold;">● ' . esc_html($label) . '</span>';
            break;
            
        case 'data_candidatura':
            $data = get_post_meta($post_id, '_candidatura_data', true);
            if ($data) {
                echo date('d/m/Y H:i', strtotime($data));
            } else {
                echo get_the_date('d/m/Y H:i', $post_id);
            }
            break;
    }
}
add_action('manage_candidatura_posts_custom_column', 'sv_candidaturas_colunas_conteudo', 10, 2);

// Adicionar meta boxes para informações da candidatura
function sv_adicionar_meta_boxes_candidatura() {
    add_meta_box(
        'candidatura_detalhes',
        'Detalhes da Candidatura',
        'sv_meta_box_candidatura_detalhes',
        'candidatura',
        'normal',
        'high'
    );
    
    add_meta_box(
        'candidatura_arquivos',
        'Arquivos Anexados',
        'sv_meta_box_candidatura_arquivos',
        'candidatura',
        'side',
        'default'
    );
    
    add_meta_box(
        'candidatura_status',
        'Status da Candidatura',
        'sv_meta_box_candidatura_status',
        'candidatura',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'sv_adicionar_meta_boxes_candidatura');

// Conteúdo da meta box de detalhes
function sv_meta_box_candidatura_detalhes($post) {
    wp_nonce_field('sv_salvar_candidatura_detalhes', 'sv_candidatura_detalhes_nonce');
    
    $vaga_id = get_post_meta($post->ID, '_candidatura_vaga_id', true);
    $email = get_post_meta($post->ID, '_candidatura_email', true);
    $telefone = get_post_meta($post->ID, '_candidatura_telefone', true);
    $linkedin = get_post_meta($post->ID, '_candidatura_linkedin', true);
    $pretensao = get_post_meta($post->ID, '_candidatura_pretensao', true);
    $mensagem = get_post_meta($post->ID, '_candidatura_mensagem', true);
    $ip = get_post_meta($post->ID, '_candidatura_ip', true);
    $user_agent = get_post_meta($post->ID, '_candidatura_user_agent', true);
    $data = get_post_meta($post->ID, '_candidatura_data', true);
    
    // Buscar vagas disponíveis
    $vagas = get_posts(array(
        'post_type' => 'vaga',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    ?>
    
    <table class="form-table">
        <tr>
            <th><label for="candidatura_vaga_id">Vaga:</label></th>
            <td>
                <select id="candidatura_vaga_id" name="candidatura_vaga_id" style="width: 100%;">
                    <option value="">Selecione uma vaga...</option>
                    <?php foreach ($vagas as $vaga): ?>
                        <option value="<?php echo $vaga->ID; ?>" <?php selected($vaga_id, $vaga->ID); ?>>
                            <?php echo esc_html($vaga->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="candidatura_email">Email:</label></th>
            <td><input type="email" id="candidatura_email" name="candidatura_email" value="<?php echo esc_attr($email); ?>" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><label for="candidatura_telefone">Telefone:</label></th>
            <td><input type="text" id="candidatura_telefone" name="candidatura_telefone" value="<?php echo esc_attr($telefone); ?>" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><label for="candidatura_linkedin">LinkedIn/Portfólio:</label></th>
            <td><input type="url" id="candidatura_linkedin" name="candidatura_linkedin" value="<?php echo esc_attr($linkedin); ?>" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><label for="candidatura_pretensao">Pretensão Salarial:</label></th>
            <td><input type="text" id="candidatura_pretensao" name="candidatura_pretensao" value="<?php echo esc_attr($pretensao); ?>" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><label for="candidatura_mensagem">Mensagem:</label></th>
            <td><textarea id="candidatura_mensagem" name="candidatura_mensagem" rows="5" style="width: 100%;"><?php echo esc_textarea($mensagem); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="candidatura_data">Data da Candidatura:</label></th>
            <td><input type="datetime-local" id="candidatura_data" name="candidatura_data" value="<?php echo $data ? date('Y-m-d\TH:i', strtotime($data)) : ''; ?>" style="width: 100%;" /></td>
        </tr>
    </table>
    
    <?php if ($ip || $user_agent): ?>
    <h4>Informações Técnicas</h4>
    <table class="form-table">
        <?php if ($ip): ?>
        <tr>
            <th>IP do Candidato:</th>
            <td><code><?php echo esc_html($ip); ?></code></td>
        </tr>
        <?php endif; ?>
        <?php if ($user_agent): ?>
        <tr>
            <th>User Agent:</th>
            <td><code style="word-break: break-all;"><?php echo esc_html($user_agent); ?></code></td>
        </tr>
        <?php endif; ?>
    </table>
    <?php endif; ?>
    
    <?php
}

// Conteúdo da meta box de arquivos
function sv_meta_box_candidatura_arquivos($post) {
    $curriculo_url = get_post_meta($post->ID, '_candidatura_curriculo_url', true);
    ?>
    
    <div style="padding: 10px 0;">
        <?php if ($curriculo_url): ?>
            <p><strong>Currículo anexado:</strong></p>
            <p><a href="<?php echo esc_url($curriculo_url); ?>" target="_blank" class="button button-secondary">📄 Visualizar Currículo</a></p>
        <?php else: ?>
            <p style="color: #666; font-style: italic;">Nenhum arquivo anexado.</p>
        <?php endif; ?>
    </div>
    
    <div style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
        <label for="candidatura_curriculo_url"><strong>URL do Currículo:</strong></label>
        <input type="url" id="candidatura_curriculo_url" name="candidatura_curriculo_url" value="<?php echo esc_attr($curriculo_url); ?>" style="width: 100%; margin-top: 5px;" placeholder="https://..." />
        <p style="font-size: 12px; color: #666; margin-top: 5px;">Cole aqui a URL do currículo se necessário.</p>
    </div>
    
    <?php
}

// Conteúdo da meta box de status
function sv_meta_box_candidatura_status($post) {
    $status = get_post_meta($post->ID, '_candidatura_status', true);
    $status = $status ? $status : 'nova';
    
    $observacoes = get_post_meta($post->ID, '_candidatura_observacoes', true);
    ?>
    
    <div style="padding: 10px 0;">
        <label for="candidatura_status"><strong>Status:</strong></label>
        <select id="candidatura_status" name="candidatura_status" style="width: 100%; margin-top: 5px;">
            <option value="nova" <?php selected($status, 'nova'); ?>>Nova</option>
            <option value="em_analise" <?php selected($status, 'em_analise'); ?>>Em Análise</option>
            <option value="entrevista" <?php selected($status, 'entrevista'); ?>>Entrevista</option>
            <option value="aprovada" <?php selected($status, 'aprovada'); ?>>Aprovada</option>
            <option value="rejeitada" <?php selected($status, 'rejeitada'); ?>>Rejeitada</option>
            <option value="finalizada" <?php selected($status, 'finalizada'); ?>>Finalizada</option>
        </select>
    </div>
    
    <div style="border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px;">
        <label for="candidatura_observacoes"><strong>Observações Internas:</strong></label>
        <textarea id="candidatura_observacoes" name="candidatura_observacoes" rows="4" style="width: 100%; margin-top: 5px;" placeholder="Adicione observações sobre esta candidatura..."><?php echo esc_textarea($observacoes); ?></textarea>
    </div>
    
    <?php
}

// Salvar os dados das meta boxes
function sv_salvar_candidatura_detalhes($post_id) {
    if (!isset($_POST['sv_candidatura_detalhes_nonce']) || !wp_verify_nonce($_POST['sv_candidatura_detalhes_nonce'], 'sv_salvar_candidatura_detalhes')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Pega o status antigo ANTES de salvar o novo
    $status_antigo = get_post_meta($post_id, '_candidatura_status', true);
    if(empty($status_antigo)) $status_antigo = 'nova';

    $campos = array(
        'candidatura_vaga_id', 'candidatura_email', 'candidatura_telefone', 
        'candidatura_linkedin', 'candidatura_pretensao', 'candidatura_mensagem',
        'candidatura_curriculo_url', 'candidatura_status', 'candidatura_observacoes',
        'candidatura_data'
    );
    
    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            $meta_key = '_' . $campo;
            $value = $_POST[$campo];

            if (in_array($campo, array('candidatura_mensagem', 'candidatura_observacoes'))) {
                update_post_meta($post_id, $meta_key, sanitize_textarea_field($value));
            } elseif (in_array($campo, array('candidatura_linkedin', 'candidatura_curriculo_url'))) {
                update_post_meta($post_id, $meta_key, esc_url_raw($value));
            } elseif ($campo === 'candidatura_email') {
                update_post_meta($post_id, $meta_key, sanitize_email($value));
            } elseif ($campo === 'candidatura_vaga_id') {
                update_post_meta($post_id, $meta_key, intval($value));
            } else {
                update_post_meta($post_id, $meta_key, sanitize_text_field($value));
            }
        }
    }
    
    // Pega o status novo
    $status_novo = isset($_POST['candidatura_status']) ? sanitize_text_field($_POST['candidatura_status']) : $status_antigo;

    // Se o status mudou, chama a função de notificação
    if ($status_novo !== $status_antigo) {
        sv_enviar_email_mudanca_status($post_id, $status_novo);
    }
}
// Mantenha a action 'save_post' como está
add_action('save_post', 'sv_salvar_candidatura_detalhes');


// ADICIONE ESTA NOVA FUNÇÃO NO FINAL DO ARQUIVO cpt-candidaturas.php
/**
 * Envia um e-mail para o candidato quando o status da candidatura muda.
 *
 * @param int    $candidatura_id O ID do post da candidatura.
 * @param string $novo_status    O novo status da candidatura.
 */
function sv_enviar_email_mudanca_status($candidatura_id, $novo_status) {
    // 1. Obter os templates de e-mail salvos nas configurações
    $templates = get_option('sv_email_templates');

    // Verifica se existe um template para o novo status
    if (!isset($templates[$novo_status]) || empty($templates[$novo_status]['message'])) {
        return; // Não faz nada se não houver template definido
    }

    // 2. Obter todos os dados necessários
    $candidato_nome = get_the_title($candidatura_id);
    $candidato_email = get_post_meta($candidatura_id, '_candidatura_email', true);
    $vaga_id = get_post_meta($candidatura_id, '_candidatura_vaga_id', true);
    
    if (!$candidato_email || !$vaga_id) {
        return; // Dados essenciais faltando
    }
    
    $vaga = get_post($vaga_id);
    $vaga_titulo = $vaga ? $vaga->post_title : 'Vaga não encontrada';
    $vaga_link = $vaga ? get_permalink($vaga_id) : home_url();
    $empresa_nome = $vaga ? get_post_meta($vaga_id, '_vaga_empresa', true) : get_bloginfo('name');
    
    // 3. Preparar os placeholders e os valores de substituição
    $placeholders = [
        '[nome_candidato]' => $candidato_nome,
        '[titulo_vaga]'    => $vaga_titulo,
        '[nome_empresa]'   => $empresa_nome,
        '[link_vaga]'      => $vaga_link,
        '[nome_site]'      => get_bloginfo('name'),
    ];

    // 4. Substituir os placeholders no assunto e na mensagem
    $assunto = $templates[$novo_status]['subject'];
    $mensagem = $templates[$novo_status]['message'];

    foreach ($placeholders as $placeholder => $value) {
        $assunto = str_replace($placeholder, $value, $assunto);
        $mensagem = str_replace($placeholder, $value, $mensagem);
    }

    // 5. Preparar e enviar o e-mail
    $headers = [
        "From: " . get_bloginfo('name') . " <rh@salesprime.com.br>",
        "Reply-To: rh@salesprime.com.br",
        "Content-Type: text/html; charset=UTF-8"
    ];

    // Usa wpautop para adicionar parágrafos e quebras de linha
    $corpo_html = wpautop($mensagem);

    wp_mail($candidato_email, $assunto, $corpo_html, $headers);
}
// Função para enviar email de notificação de status ao candidato
function sv_enviar_email_notificacao_status($post_id, $novo_status) {
    $candidato_email = get_post_meta($post_id, '_candidatura_email', true);
    $candidato_nome = get_the_title($post_id);
    $vaga_id = get_post_meta($post_id, '_candidatura_vaga_id', true);
    $vaga_titulo = get_the_title($vaga_id);

    // Verifica se temos os dados necessários
    if (empty($candidato_email) || empty($candidato_nome) || empty($vaga_titulo)) {
        return false;
    }

    $status_labels = array(
        'nova' => 'Nova',
        'em_analise' => 'Em Análise',
        'entrevista' => 'Entrevista',
        'aprovada' => 'Aprovada',
        'rejeitada' => 'Rejeitada',
        'finalizada' => 'Finalizada'
    );

    $status_label = isset($status_labels[$novo_status]) ? $status_labels[$novo_status] : ucfirst($novo_status);

    $assunto = 'Atualização de Status da sua Candidatura - ' . $vaga_titulo;
    
    $mensagem = "Olá " . $candidato_nome . ",\n\n";
    $mensagem .= "Informamos que o status da sua candidatura para a vaga \"" . $vaga_titulo . "\" foi atualizado.\n\n";
    $mensagem .= "=== STATUS ATUAL ===\n";
    $mensagem .= "Status: " . $status_label . "\n\n";
    
    // Mensagens personalizadas por status
    switch ($novo_status) {
        case 'em_analise':
            $mensagem .= "Sua candidatura está sendo analisada por nossa equipe de RH. Aguarde nosso contato.\n\n";
            break;
        case 'entrevista':
            $mensagem .= "Parabéns! Você foi selecionado(a) para a próxima etapa do processo seletivo. Nossa equipe entrará em contato em breve para agendar a entrevista.\n\n";
            break;
        case 'aprovada':
            $mensagem .= "Parabéns! Sua candidatura foi aprovada! Nossa equipe de RH entrará em contato para os próximos passos.\n\n";
            break;
        case 'rejeitada':
            $mensagem .= "Agradecemos seu interesse na vaga. Infelizmente, sua candidatura não foi selecionada para esta posição. Continue acompanhando nossas oportunidades.\n\n";
            break;
        case 'finalizada':
            $mensagem .= "O processo seletivo para esta vaga foi finalizado. Agradecemos sua participação.\n\n";
            break;
        default:
            $mensagem .= "Acompanhe o status da sua candidatura através do nosso site.\n\n";
    }
    
    $mensagem .= "=== DETALHES DA VAGA ===\n";
    $mensagem .= "Vaga: " . $vaga_titulo . "\n";
    $mensagem .= "Empresa: " . get_bloginfo('name') . "\n\n";
    
    $mensagem .= "Caso tenha dúvidas, responda a este email.\n\n";
    $mensagem .= "Atenciosamente,\n";
    $mensagem .= "Equipe de Recrutamento\n";
    $mensagem .= get_bloginfo('name') . "\n";
    $mensagem .= get_bloginfo('url') . "\n";

    $headers = array(
        "From: " . get_bloginfo('name') . " <rh@salesprime.com.br>",
        "Reply-To: rh@salesprime.com.br",
        "Content-Type: text/plain; charset=UTF-8"
    );

    return wp_mail($candidato_email, $assunto, $mensagem, $headers);
}

// Função para criar uma candidatura programaticamente
function sv_criar_candidatura($dados) {
    $candidatura_data = array(
        'post_title'    => $dados['nome'],
        'post_content'  => $dados['mensagem'],
        'post_status'   => 'publish',
        'post_type'     => 'candidatura',
        'post_author'   => 1,
    );

    $candidatura_id = wp_insert_post($candidatura_data);

    if ($candidatura_id) {
        // Salva os meta dados
        update_post_meta($candidatura_id, '_candidatura_vaga_id', $dados['vaga_id']);
        update_post_meta($candidatura_id, '_candidatura_email', $dados['email']);
        update_post_meta($candidatura_id, '_candidatura_telefone', $dados['telefone']);
        update_post_meta($candidatura_id, '_candidatura_linkedin', $dados['linkedin']);
        update_post_meta($candidatura_id, '_candidatura_pretensao', $dados['pretensao']);
        update_post_meta($candidatura_id, '_candidatura_mensagem', $dados['mensagem']);
        update_post_meta($candidatura_id, '_candidatura_curriculo_url', $dados['curriculo_url']);
        update_post_meta($candidatura_id, '_candidatura_status', 'nova');
        update_post_meta($candidatura_id, '_candidatura_data', current_time('mysql'));
        update_post_meta($candidatura_id, '_candidatura_ip', $dados['ip']);
        update_post_meta($candidatura_id, '_candidatura_user_agent', $dados['user_agent']);
    }

    return $candidatura_id;
}

// Adicionar hook para enviar email quando status é alterado via ações em massa
function sv_candidatura_status_atualizado_massa($post_id, $novo_status) {
    // Verifica se é uma candidatura
    if (get_post_type($post_id) !== 'candidatura') {
        return;
    }

    // Captura o status anterior
    $status_anterior = get_post_meta($post_id, '_candidatura_status', true);
    
    // Se o status mudou, envia email
    if ($status_anterior !== $novo_status) {
        sv_enviar_email_notificacao_status($post_id, $novo_status);
    }
}

// Modificar a função de ações em massa para incluir envio de email
function sv_candidaturas_processar_acoes_massa_com_email($redirect_to, $doaction, $post_ids) {
    $status_map = array(
        'marcar_em_analise' => 'em_analise',
        'marcar_entrevista' => 'entrevista',
        'marcar_aprovada' => 'aprovada',
        'marcar_rejeitada' => 'rejeitada'
    );
    
    if (array_key_exists($doaction, $status_map)) {
        $novo_status = $status_map[$doaction];
        $contador = 0;
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) == 'candidatura') {
                // Captura status anterior
                $status_anterior = get_post_meta($post_id, '_candidatura_status', true);
                
                // Atualiza o status
                update_post_meta($post_id, '_candidatura_status', $novo_status);
                
                // Envia email se o status mudou
                if ($status_anterior !== $novo_status) {
                    sv_enviar_email_notificacao_status($post_id, $novo_status);
                }
                
                $contador++;
            }
        }
        
        $redirect_to = add_query_arg('candidaturas_atualizadas', $contador, $redirect_to);
    }
    
    return $redirect_to;
}

// Remove o hook antigo e adiciona o novo
remove_filter('handle_bulk_actions-edit-candidatura', 'sv_candidaturas_processar_acoes_massa', 10, 3);
add_filter('handle_bulk_actions-edit-candidatura', 'sv_candidaturas_processar_acoes_massa_com_email', 10, 3);

