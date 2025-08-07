<?php
// Adicionar filtros personalizados na listagem de candidaturas
function sv_candidaturas_filtros_admin() {
    global $typenow;
    
    if ($typenow == 'candidatura') {
        // Filtro por vaga
        $vagas = get_posts(array(
            'post_type' => 'vaga',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        if ($vagas) {
            $vaga_selecionada = isset($_GET['vaga_filter']) ? $_GET['vaga_filter'] : '';
            echo '<select name="vaga_filter">';
            echo '<option value="">Todas as vagas</option>';
            foreach ($vagas as $vaga) {
                $selected = selected($vaga_selecionada, $vaga->ID, false);
                echo '<option value="' . $vaga->ID . '"' . $selected . '>' . esc_html($vaga->post_title) . '</option>';
            }
            echo '</select>';
        }
        
        // Filtro por status
        $status_selecionado = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
        $status_options = array(
            'nova' => 'Nova',
            'em_analise' => 'Em Análise',
            'entrevista' => 'Entrevista',
            'aprovada' => 'Aprovada',
            'rejeitada' => 'Rejeitada',
            'finalizada' => 'Finalizada'
        );
        
        echo '<select name="status_filter">';
        echo '<option value="">Todos os status</option>';
        foreach ($status_options as $value => $label) {
            $selected = selected($status_selecionado, $value, false);
            echo '<option value="' . $value . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'sv_candidaturas_filtros_admin');

// Aplicar filtros na query
function sv_candidaturas_aplicar_filtros($query) {
    global $pagenow;
    
    if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'candidatura') {
        $meta_query = array();
        
        // Filtro por vaga
        if (isset($_GET['vaga_filter']) && $_GET['vaga_filter'] != '') {
            $meta_query[] = array(
                'key' => '_candidatura_vaga_id',
                'value' => intval($_GET['vaga_filter']),
                'compare' => '='
            );
        }
        
        // Filtro por status
        if (isset($_GET['status_filter']) && $_GET['status_filter'] != '') {
            $meta_query[] = array(
                'key' => '_candidatura_status',
                'value' => sanitize_text_field($_GET['status_filter']),
                'compare' => '='
            );
        }
        
        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query['relation'] = 'AND';
            }
            $query->set('meta_query', $meta_query);
        }
    }
}
add_action('pre_get_posts', 'sv_candidaturas_aplicar_filtros');

// Adicionar ações em massa personalizadas
function sv_candidaturas_acoes_massa($actions) {
    $actions['marcar_em_analise'] = 'Marcar como Em Análise';
    $actions['marcar_entrevista'] = 'Marcar como Entrevista';
    $actions['marcar_aprovada'] = 'Marcar como Aprovada';
    $actions['marcar_rejeitada'] = 'Marcar como Rejeitada';
    return $actions;
}
add_filter('bulk_actions-edit-candidatura', 'sv_candidaturas_acoes_massa');

// Processar ações em massa COM ENVIO DE EMAIL
function sv_candidaturas_processar_acoes_massa($redirect_to, $doaction, $post_ids) {
    $status_map = array(
        'marcar_em_analise' => 'em_analise',
        'marcar_entrevista' => 'entrevista',
        'marcar_aprovada' => 'aprovada',
        'marcar_rejeitada' => 'rejeitada'
    );
    
    if (array_key_exists($doaction, $status_map)) {
        $novo_status = $status_map[$doaction];
        $contador = 0;
        $emails_enviados = 0;
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) == 'candidatura') {
                // Captura status anterior
                $status_anterior = get_post_meta($post_id, '_candidatura_status', true);
                
                // Atualiza o status
                update_post_meta($post_id, '_candidatura_status', $novo_status);
                
                // Envia email se o status mudou
                if ($status_anterior !== $novo_status) {
                    $email_enviado = sv_enviar_email_notificacao_status($post_id, $novo_status);
                    if ($email_enviado) {
                        $emails_enviados++;
                    }
                }
                
                $contador++;
            }
        }
        
        $redirect_to = add_query_arg(array(
            'candidaturas_atualizadas' => $contador,
            'emails_enviados' => $emails_enviados
        ), $redirect_to);
    }
    
    return $redirect_to;
}
add_filter('handle_bulk_actions-edit-candidatura', 'sv_candidaturas_processar_acoes_massa', 10, 3);

// Mostrar mensagem de confirmação das ações em massa
function sv_candidaturas_mensagem_acoes_massa() {
    if (isset($_REQUEST['candidaturas_atualizadas'])) {
        $count = intval($_REQUEST['candidaturas_atualizadas']);
        $emails_count = isset($_REQUEST['emails_enviados']) ? intval($_REQUEST['emails_enviados']) : 0;
        
        $mensagem = sprintf('%d candidatura(s) atualizada(s) com sucesso.', $count);
        if ($emails_count > 0) {
            $mensagem .= sprintf(' %d email(s) de notificação enviado(s).', $emails_count);
        }
        
        printf('<div id="message" class="updated notice is-dismissible"><p>%s</p></div>', $mensagem);
    }
}
add_action('admin_notices', 'sv_candidaturas_mensagem_acoes_massa');

// Adicionar colunas sortáveis
function sv_candidaturas_colunas_sortaveis($columns) {
    $columns['vaga_relacionada'] = 'vaga_relacionada';
    $columns['email_candidato'] = 'email_candidato';
    $columns['status_candidatura'] = 'status_candidatura';
    $columns['data_candidatura'] = 'data_candidatura';
    return $columns;
}
add_filter('manage_edit-candidatura_sortable_columns', 'sv_candidaturas_colunas_sortaveis');

// Processar ordenação das colunas
function sv_candidaturas_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    $orderby = $query->get('orderby');
    
    switch ($orderby) {
        case 'vaga_relacionada':
            $query->set('meta_key', '_candidatura_vaga_id');
            $query->set('orderby', 'meta_value_num');
            break;
            
        case 'email_candidato':
            $query->set('meta_key', '_candidatura_email');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'status_candidatura':
            $query->set('meta_key', '_candidatura_status');
            $query->set('orderby', 'meta_value');
            break;
            
        case 'data_candidatura':
            $query->set('meta_key', '_candidatura_data');
            $query->set('orderby', 'meta_value');
            break;
    }
}
add_action('pre_get_posts', 'sv_candidaturas_orderby');

// Adicionar dashboard widget com estatísticas
function sv_candidaturas_dashboard_widget() {
    wp_add_dashboard_widget(
        'sv_candidaturas_stats',
        'Estatísticas de Candidaturas',
        'sv_candidaturas_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'sv_candidaturas_dashboard_widget');

function sv_candidaturas_dashboard_widget_content() {
    // Estatísticas gerais
    $total_candidaturas = wp_count_posts('candidatura')->publish;
    $total_vagas = wp_count_posts('vaga')->publish;
    
    // Candidaturas por status
    $candidaturas_nova = get_posts(array(
        'post_type' => 'candidatura',
        'meta_query' => array(
            array(
                'key' => '_candidatura_status',
                'value' => 'nova',
                'compare' => '='
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $candidaturas_em_analise = get_posts(array(
        'post_type' => 'candidatura',
        'meta_query' => array(
            array(
                'key' => '_candidatura_status',
                'value' => 'em_analise',
                'compare' => '='
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    // Candidaturas recentes (últimos 7 dias)
    $candidaturas_recentes = get_posts(array(
        'post_type' => 'candidatura',
        'date_query' => array(
            array(
                'after' => '1 week ago'
            )
        ),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div style="text-align: center; padding: 15px; background: #f0f6fc; border-radius: 8px; border-left: 4px solid #0073aa;">
            <div style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo $total_candidaturas; ?></div>
            <div style="font-size: 12px; color: #666;">Total de Candidaturas</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #f6f7f7; border-radius: 8px; border-left: 4px solid #46b450;">
            <div style="font-size: 24px; font-weight: bold; color: #46b450;"><?php echo $total_vagas; ?></div>
            <div style="font-size: 12px; color: #666;">Vagas Ativas</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #fff8e1; border-radius: 8px; border-left: 4px solid #f0b849;">
            <div style="font-size: 24px; font-weight: bold; color: #f0b849;"><?php echo count($candidaturas_nova); ?></div>
            <div style="font-size: 12px; color: #666;">Novas</div>
        </div>
        
        <div style="text-align: center; padding: 15px; background: #f3e5f5; border-radius: 8px; border-left: 4px solid #9b59b6;">
            <div style="font-size: 24px; font-weight: bold; color: #9b59b6;"><?php echo count($candidaturas_em_analise); ?></div>
            <div style="font-size: 12px; color: #666;">Em Análise</div>
        </div>
    </div>
    
    <div style="margin-bottom: 15px;">
        <strong>📈 Últimos 7 dias:</strong> <?php echo count($candidaturas_recentes); ?> nova(s) candidatura(s)
    </div>
    
    <div style="text-align: center;">
        <a href="<?php echo admin_url('edit.php?post_type=candidatura'); ?>" class="button button-primary">Ver Todas as Candidaturas</a>
        <a href="<?php echo admin_url('edit.php?post_type=vaga'); ?>" class="button button-secondary">Gerenciar Vagas</a>
    </div>
    
    <?php
    
    // Candidaturas recentes
    $candidaturas_lista = get_posts(array(
        'post_type' => 'candidatura',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if ($candidaturas_lista) {
        echo '<div style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 15px;">';
        echo '<h4 style="margin-bottom: 10px;">🕒 Candidaturas Recentes</h4>';
        
        foreach ($candidaturas_lista as $candidatura) {
            $vaga_id = get_post_meta($candidatura->ID, '_candidatura_vaga_id', true);
            $vaga = get_post($vaga_id);
            $status = get_post_meta($candidatura->ID, '_candidatura_status', true);
            $status = $status ? $status : 'nova';
            
            $status_colors = array(
                'nova' => '#007cba',
                'em_analise' => '#f0b849',
                'aprovada' => '#46b450',
                'rejeitada' => '#dc3232',
                'entrevista' => '#9b59b6',
                'finalizada' => '#6c757d'
            );
            
            $color = isset($status_colors[$status]) ? $status_colors[$status] : '#6c757d';
            
            echo '<div style="padding: 8px 0; border-bottom: 1px solid #eee; font-size: 13px;">';
            echo '<div style="font-weight: bold;"><a href="' . get_edit_post_link($candidatura->ID) . '">' . esc_html($candidatura->post_title) . '</a></div>';
            echo '<div style="color: #666;">Vaga: ' . ($vaga ? esc_html($vaga->post_title) : 'Vaga removida') . '</div>';
            echo '<div style="color: ' . $color . '; font-weight: bold; font-size: 11px;">● ' . ucfirst(str_replace('_', ' ', $status)) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
}

// Adicionar menu de relatórios
function sv_candidaturas_menu_relatorios() {
    add_submenu_page(
        'edit.php?post_type=candidatura',
        'Relatórios de Candidaturas',
        'Relatórios',
        'manage_options',
        'candidaturas-relatorios',
        'sv_candidaturas_pagina_relatorios'
    );
}
add_action('admin_menu', 'sv_candidaturas_menu_relatorios');

function sv_candidaturas_pagina_relatorios() {
    ?>
    <div class="wrap">
        <h1>📊 Relatórios de Candidaturas</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
            
            <!-- Relatório por Vaga -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3>📋 Candidaturas por Vaga</h3>
                <?php
                $vagas = get_posts(array(
                    'post_type' => 'vaga',
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ));
                
                foreach ($vagas as $vaga) {
                    $candidaturas_count = get_posts(array(
                        'post_type' => 'candidatura',
                        'meta_query' => array(
                            array(
                                'key' => '_candidatura_vaga_id',
                                'value' => $vaga->ID,
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1,
                        'fields' => 'ids'
                    ));
                    
                    $count = count($candidaturas_count);
                    echo '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">';
                    echo '<span>' . esc_html($vaga->post_title) . '</span>';
                    echo '<span style="font-weight: bold; color: #0073aa;">' . $count . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Relatório por Status -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3>📈 Candidaturas por Status</h3>
                <?php
                $status_options = array(
                    'nova' => 'Nova',
                    'em_analise' => 'Em Análise',
                    'entrevista' => 'Entrevista',
                    'aprovada' => 'Aprovada',
                    'rejeitada' => 'Rejeitada',
                    'finalizada' => 'Finalizada'
                );
                
                $status_colors = array(
                    'nova' => '#007cba',
                    'em_analise' => '#f0b849',
                    'aprovada' => '#46b450',
                    'rejeitada' => '#dc3232',
                    'entrevista' => '#9b59b6',
                    'finalizada' => '#6c757d'
                );
                
                foreach ($status_options as $status => $label) {
                    $candidaturas_count = get_posts(array(
                        'post_type' => 'candidatura',
                        'meta_query' => array(
                            array(
                                'key' => '_candidatura_status',
                                'value' => $status,
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1,
                        'fields' => 'ids'
                    ));
                    
                    $count = count($candidaturas_count);
                    $color = isset($status_colors[$status]) ? $status_colors[$status] : '#6c757d';
                    
                    echo '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">';
                    echo '<span style="color: ' . $color . ';">● ' . esc_html($label) . '</span>';
                    echo '<span style="font-weight: bold; color: ' . $color . ';">' . $count . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Relatório Temporal -->
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3>📅 Candidaturas por Período</h3>
                <?php
                $periodos = array(
                    'Hoje' => '1 day ago',
                    'Últimos 7 dias' => '1 week ago',
                    'Últimos 30 dias' => '1 month ago',
                    'Últimos 3 meses' => '3 months ago'
                );
                
                foreach ($periodos as $label => $periodo) {
                    $candidaturas_count = get_posts(array(
                        'post_type' => 'candidatura',
                        'date_query' => array(
                            array(
                                'after' => $periodo
                            )
                        ),
                        'posts_per_page' => -1,
                        'fields' => 'ids'
                    ));
                    
                    $count = count($candidaturas_count);
                    echo '<div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">';
                    echo '<span>' . esc_html($label) . '</span>';
                    echo '<span style="font-weight: bold; color: #0073aa;">' . $count . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px;">
            <h3>🔗 Links Úteis</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="<?php echo admin_url('edit.php?post_type=candidatura'); ?>" class="button button-primary" style="text-align: center; padding: 15px;">
                    📋 Gerenciar Candidaturas
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=vaga'); ?>" class="button button-secondary" style="text-align: center; padding: 15px;">
                    💼 Gerenciar Vagas
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=vaga'); ?>" class="button button-secondary" style="text-align: center; padding: 15px;">
                    ➕ Nova Vaga
                </a>
            </div>
        </div>
    </div>
    <?php
}

