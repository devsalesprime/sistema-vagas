<?php
// Registrar Custom Post Type Vagas
function sv_criar_cpt_vagas() {
    $labels = array(
        'name'                  => 'Vagas',
        'singular_name'         => 'Vaga',
        'menu_name'             => 'Vagas',
        'name_admin_bar'        => 'Vaga',
        'archives'              => 'Arquivo de Vagas',
        'attributes'            => 'Atributos da Vaga',
        'parent_item_colon'     => 'Vaga Pai:',
        'all_items'             => 'Todas as Vagas',
        'add_new_item'          => 'Adicionar Nova Vaga',
        'add_new'               => 'Adicionar Nova',
        'new_item'              => 'Nova Vaga',
        'edit_item'             => 'Editar Vaga',
        'update_item'           => 'Atualizar Vaga',
        'view_item'             => 'Ver Vaga',
        'view_items'            => 'Ver Vagas',
        'search_items'          => 'Buscar Vagas',
        'not_found'             => 'Nenhuma vaga encontrada',
        'not_found_in_trash'    => 'Nenhuma vaga encontrada na lixeira',
        'featured_image'        => 'Imagem da Vaga',
        'set_featured_image'    => 'Definir imagem da vaga',
        'remove_featured_image' => 'Remover imagem da vaga',
        'use_featured_image'    => 'Usar como imagem da vaga',
        'insert_into_item'      => 'Inserir na vaga',
        'uploaded_to_this_item' => 'Enviado para esta vaga',
        'items_list'            => 'Lista de vagas',
        'items_list_navigation' => 'Navegação da lista de vagas',
        'filter_items_list'     => 'Filtrar lista de vagas',
    );

    $args = array(
        'label'                 => 'Vaga',
        'description'           => 'Vagas de emprego disponíveis',
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
        'taxonomies'            => array(),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-clipboard',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rewrite'               => array(
            'slug'                  => 'vagas',
            'with_front'            => false,
            'pages'                 => true,
            'feeds'                 => true
        ),
    );
    
    register_post_type('vaga', $args);
}
add_action('init', 'sv_criar_cpt_vagas');

// Adicionar colunas personalizadas na listagem de vagas no admin
function sv_vagas_colunas_admin($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['title'] = $columns['title'];
    $new_columns['vaga_empresa'] = 'Empresa';
    $new_columns['vaga_status'] = 'Status';
    $new_columns['vaga_candidaturas'] = 'Candidaturas';
    $new_columns['vaga_data'] = 'Data de Publicação';
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}
add_filter('manage_vaga_posts_columns', 'sv_vagas_colunas_admin');

// Preencher as colunas personalizadas
function sv_vagas_colunas_conteudo($column, $post_id) {
    switch ($column) {
        case 'vaga_empresa':
            $empresa = get_post_meta($post_id, '_vaga_empresa', true);
            if ($empresa) {
                echo '<strong>' . esc_html($empresa) . '</strong>';
            } else {
                echo '<span style="color: #6c757d;">Não informado</span>';
            }
            break;
            
        case 'vaga_status':
            $status = get_post_status($post_id);
            if ($status == 'publish') {
                echo '<span style="color: green;">✅ Ativa</span>';
            } else {
                echo '<span style="color: orange;">⏸️ Inativa</span>';
            }
            break;
            
        case 'vaga_candidaturas':
            $candidaturas = get_posts(array(
                'post_type' => 'candidatura',
                'meta_query' => array(
                    array(
                        'key' => '_candidatura_vaga_id',
                        'value' => $post_id,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            $total = count($candidaturas);
            if ($total > 0) {
                $url = admin_url('edit.php?post_type=candidatura&vaga_filter=' . $post_id);
                echo '<a href="' . $url . '" style="color: #0073aa; font-weight: bold;">' . $total . ' candidatura' . ($total > 1 ? 's' : '') . '</a>';
            } else {
                echo '<span style="color: #6c757d;">0 candidaturas</span>';
            }
            break;
            
        case 'vaga_data':
            echo get_the_date('d/m/Y', $post_id);
            break;
    }
}
add_action('manage_vaga_posts_custom_column', 'sv_vagas_colunas_conteudo', 10, 2);

// Adicionar meta boxes para informações adicionais da vaga
function sv_adicionar_meta_boxes_vaga() {
    add_meta_box(
        'vaga_detalhes',
        'Detalhes da Vaga',
        'sv_meta_box_vaga_detalhes',
        'vaga',
        'normal',
        'high'
    );
    
    add_meta_box(
        'vaga_candidaturas_relacionadas',
        'Candidaturas Recebidas',
        'sv_meta_box_vaga_candidaturas',
        'vaga',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'sv_adicionar_meta_boxes_vaga');

// Verifica se um campo da vaga deve aparecer na página pública (default: visível)
function sv_vaga_campo_visivel($post_id, $campo) {
    return get_post_meta($post_id, '_vaga_mostrar_' . $campo, true) !== '0';
}

// Renderiza o switch (liga/desliga) ao lado do campo no meta box
function sv_vaga_toggle_html($campo, $checado) {
    ob_start(); ?>
    <label class="sv-switch" title="Quando desligado, este campo não aparece na página pública da vaga">
        <input type="checkbox" class="sv-toggle" name="vaga_mostrar_<?php echo esc_attr($campo); ?>" data-target="vaga_<?php echo esc_attr($campo); ?>" <?php checked($checado); ?> />
        <span class="sv-slider"></span>
    </label>
    <?php return ob_get_clean();
}

// Conteúdo da meta box
function sv_meta_box_vaga_detalhes($post) {
    wp_nonce_field('sv_salvar_vaga_detalhes', 'sv_vaga_detalhes_nonce');

    $salario = get_post_meta($post->ID, '_vaga_salario', true);
    $localizacao = get_post_meta($post->ID, '_vaga_localizacao', true);
    $tipo_contrato = get_post_meta($post->ID, '_vaga_tipo_contrato', true);
    $nivel = get_post_meta($post->ID, '_vaga_nivel', true);
    $empresa = get_post_meta($post->ID, '_vaga_empresa', true);
    $modalidade = get_post_meta($post->ID, '_vaga_modalidade', true);
    $beneficios = get_post_meta($post->ID, '_vaga_beneficios', true);
    $requisitos = get_post_meta($post->ID, '_vaga_requisitos', true);

    // Estado de visibilidade de cada campo (default: visível)
    $mostrar_empresa       = sv_vaga_campo_visivel($post->ID, 'empresa');
    $mostrar_salario       = sv_vaga_campo_visivel($post->ID, 'salario');
    $mostrar_localizacao   = sv_vaga_campo_visivel($post->ID, 'localizacao');
    $mostrar_modalidade    = sv_vaga_campo_visivel($post->ID, 'modalidade');
    $mostrar_tipo_contrato = sv_vaga_campo_visivel($post->ID, 'tipo_contrato');
    $mostrar_nivel         = sv_vaga_campo_visivel($post->ID, 'nivel');
    $mostrar_requisitos    = sv_vaga_campo_visivel($post->ID, 'requisitos');
    $mostrar_beneficios    = sv_vaga_campo_visivel($post->ID, 'beneficios');
    ?>

    <style>
        .sv-th-flex { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .sv-switch { position: relative; display: inline-block; width: 42px; height: 22px; flex: 0 0 auto; }
        .sv-switch input { opacity: 0; width: 0; height: 0; margin: 0; }
        .sv-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #c3c4c7; transition: .2s; border-radius: 22px; }
        .sv-slider:before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; bottom: 3px; background: #fff; transition: .2s; border-radius: 50%; }
        .sv-switch input:checked + .sv-slider { background: #2271b1; }
        .sv-switch input:checked + .sv-slider:before { transform: translateX(20px); }
        .sv-field-off { opacity: .45; background: #f6f7f7; }
    </style>

    <table class="form-table">
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_empresa">Empresa:</label><?php echo sv_vaga_toggle_html('empresa', $mostrar_empresa); ?></span></th>
            <td><input type="text" id="vaga_empresa" name="vaga_empresa" value="<?php echo esc_attr($empresa); ?>" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_salario">Salário:</label><?php echo sv_vaga_toggle_html('salario', $mostrar_salario); ?></span></th>
            <td><input type="text" id="vaga_salario" name="vaga_salario" value="<?php echo esc_attr($salario); ?>" placeholder="Ex: R$ 3.000 - R$ 5.000" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_localizacao">Localização:</label><?php echo sv_vaga_toggle_html('localizacao', $mostrar_localizacao); ?></span></th>
            <td><input type="text" id="vaga_localizacao" name="vaga_localizacao" value="<?php echo esc_attr($localizacao); ?>" placeholder="Ex: São Paulo, SP" style="width: 100%;" /></td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_modalidade">Modalidade:</label><?php echo sv_vaga_toggle_html('modalidade', $mostrar_modalidade); ?></span></th>
            <td>
                <select id="vaga_modalidade" name="vaga_modalidade" style="width: 100%;">
                    <option value="">Selecione...</option>
                    <option value="Presencial" <?php selected($modalidade, 'Presencial'); ?>>Presencial</option>
                    <option value="Remoto" <?php selected($modalidade, 'Remoto'); ?>>Remoto</option>
                    <option value="Híbrido" <?php selected($modalidade, 'Híbrido'); ?>>Híbrido</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_tipo_contrato">Tipo de Contrato:</label><?php echo sv_vaga_toggle_html('tipo_contrato', $mostrar_tipo_contrato); ?></span></th>
            <td>
                <select id="vaga_tipo_contrato" name="vaga_tipo_contrato" style="width: 100%;">
                    <option value="">Selecione...</option>
                    <option value="CLT" <?php selected($tipo_contrato, 'CLT'); ?>>CLT</option>
                    <option value="PJ" <?php selected($tipo_contrato, 'PJ'); ?>>PJ</option>
                    <option value="Freelancer" <?php selected($tipo_contrato, 'Freelancer'); ?>>Freelancer</option>
                    <option value="Estágio" <?php selected($tipo_contrato, 'Estágio'); ?>>Estágio</option>
                    <option value="Temporário" <?php selected($tipo_contrato, 'Temporário'); ?>>Temporário</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_nivel">Nível:</label><?php echo sv_vaga_toggle_html('nivel', $mostrar_nivel); ?></span></th>
            <td>
                <select id="vaga_nivel" name="vaga_nivel" style="width: 100%;">
                    <option value="">Selecione...</option>
                    <option value="Júnior" <?php selected($nivel, 'Júnior'); ?>>Júnior</option>
                    <option value="Pleno" <?php selected($nivel, 'Pleno'); ?>>Pleno</option>
                    <option value="Sênior" <?php selected($nivel, 'Sênior'); ?>>Sênior</option>
                    <option value="Especialista" <?php selected($nivel, 'Especialista'); ?>>Especialista</option>
                    <option value="Coordenador" <?php selected($nivel, 'Coordenador'); ?>>Coordenador</option>
                    <option value="Gerente" <?php selected($nivel, 'Gerente'); ?>>Gerente</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_requisitos">Requisitos:</label><?php echo sv_vaga_toggle_html('requisitos', $mostrar_requisitos); ?></span></th>
            <td><textarea id="vaga_requisitos" name="vaga_requisitos" rows="4" style="width: 100%;" placeholder="Ex: Graduação em área relacionada, experiência com..."><?php echo esc_textarea($requisitos); ?></textarea></td>
        </tr>
        <tr>
            <th><span class="sv-th-flex"><label for="vaga_beneficios">Benefícios:</label><?php echo sv_vaga_toggle_html('beneficios', $mostrar_beneficios); ?></span></th>
            <td><textarea id="vaga_beneficios" name="vaga_beneficios" rows="4" style="width: 100%;" placeholder="Ex: Vale alimentação, plano de saúde, home office..."><?php echo esc_textarea($beneficios); ?></textarea></td>
        </tr>
    </table>

    <script>
    (function () {
        // Reflete o estado do switch no input (apenas visual: não usamos "disabled"
        // para que o valor continue sendo enviado e salvo mesmo com o campo oculto).
        function aplicarEstado(toggle) {
            var alvo = document.getElementById(toggle.dataset.target);
            if (!alvo) return;
            if (toggle.checked) {
                alvo.classList.remove('sv-field-off');
                alvo.readOnly = false;
                alvo.style.pointerEvents = '';
            } else {
                alvo.classList.add('sv-field-off');
                if (alvo.tagName === 'SELECT') {
                    alvo.style.pointerEvents = 'none';
                } else {
                    alvo.readOnly = true;
                }
            }
        }
        document.querySelectorAll('.sv-toggle').forEach(function (toggle) {
            aplicarEstado(toggle);
            toggle.addEventListener('change', function () { aplicarEstado(toggle); });
        });
    })();
    </script>

    <?php
}

// Meta box para candidaturas relacionadas
function sv_meta_box_vaga_candidaturas($post) {
    $candidaturas = get_posts(array(
        'post_type' => 'candidatura',
        'meta_query' => array(
            array(
                'key' => '_candidatura_vaga_id',
                'value' => $post->ID,
                'compare' => '='
            )
        ),
        'posts_per_page' => 10,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    if ($candidaturas) {
        echo '<div style="max-height: 300px; overflow-y: auto;">';
        foreach ($candidaturas as $candidatura) {
            $email = get_post_meta($candidatura->ID, '_candidatura_email', true);
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
            
            echo '<div style="padding: 8px; border-bottom: 1px solid #ddd; margin-bottom: 8px;">';
            echo '<div style="font-weight: bold;"><a href="' . get_edit_post_link($candidatura->ID) . '">' . esc_html($candidatura->post_title) . '</a></div>';
            echo '<div style="font-size: 12px; color: #666;">' . esc_html($email) . '</div>';
            echo '<div style="font-size: 11px; color: ' . $color . '; font-weight: bold;">● ' . ucfirst(str_replace('_', ' ', $status)) . '</div>';
            echo '<div style="font-size: 11px; color: #999;">' . get_the_date('d/m/Y H:i', $candidatura->ID) . '</div>';
            echo '</div>';
        }
        echo '</div>';
        
        $total_candidaturas = wp_count_posts('candidatura');
        if (count($candidaturas) >= 10) {
            $url = admin_url('edit.php?post_type=candidatura&vaga_filter=' . $post->ID);
            echo '<div style="text-align: center; padding: 10px;"><a href="' . $url . '" class="button button-secondary">Ver todas as candidaturas</a></div>';
        }
    } else {
        echo '<p style="color: #666; font-style: italic; text-align: center; padding: 20px;">Nenhuma candidatura recebida ainda.</p>';
    }
}

// Salvar os dados da meta box
function sv_salvar_vaga_detalhes($post_id) {
    if (!isset($_POST['sv_vaga_detalhes_nonce']) || !wp_verify_nonce($_POST['sv_vaga_detalhes_nonce'], 'sv_salvar_vaga_detalhes')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $campos = array('vaga_empresa', 'vaga_salario', 'vaga_localizacao', 'vaga_modalidade', 'vaga_tipo_contrato', 'vaga_nivel', 'vaga_requisitos', 'vaga_beneficios');

    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            if (in_array($campo, array('vaga_requisitos', 'vaga_beneficios'))) {
                update_post_meta($post_id, '_' . $campo, sanitize_textarea_field($_POST[$campo]));
            } else {
                update_post_meta($post_id, '_' . $campo, sanitize_text_field($_POST[$campo]));
            }
        }
    }

    // Visibilidade de cada campo na página pública (switch liga/desliga).
    // Checkbox desmarcado não é enviado, então a ausência significa "ocultar" ('0').
    $toggles = array('empresa', 'salario', 'localizacao', 'modalidade', 'tipo_contrato', 'nivel', 'requisitos', 'beneficios');
    foreach ($toggles as $campo) {
        $visivel = isset($_POST['vaga_mostrar_' . $campo]) ? '1' : '0';
        update_post_meta($post_id, '_vaga_mostrar_' . $campo, $visivel);
    }
}
add_action('save_post', 'sv_salvar_vaga_detalhes');

// Função para exibir os detalhes da vaga no conteúdo da postagem com layout profissional
function sv_exibir_detalhes_vaga_no_conteudo($content) {
    if (is_singular('vaga') && in_the_loop() && is_main_query()) {
        $post_id = get_the_ID();
        $empresa = get_post_meta($post_id, '_vaga_empresa', true);
        $salario = get_post_meta($post_id, '_vaga_salario', true);
        $localizacao = get_post_meta($post_id, '_vaga_localizacao', true);
        $modalidade = get_post_meta($post_id, '_vaga_modalidade', true);
        $tipo_contrato = get_post_meta($post_id, '_vaga_tipo_contrato', true);
        $nivel = get_post_meta($post_id, '_vaga_nivel', true);
        $requisitos = get_post_meta($post_id, '_vaga_requisitos', true);
        $beneficios = get_post_meta($post_id, '_vaga_beneficios', true);

        $detalhes = '<div class="vaga-detalhes-container">';
        
        // Header da vaga com informações principais
        $detalhes .= '<div class="vaga-header">';
        if ($empresa && sv_vaga_campo_visivel($post_id, 'empresa')) {
            $detalhes .= '<div class="vaga-empresa">';
            $detalhes .= '<span class="vaga-icon">🏢</span>';
            $detalhes .= '<span class="vaga-empresa-nome">' . esc_html($empresa) . '</span>';
            $detalhes .= '</div>';
        }
        $detalhes .= '<div class="vaga-meta-info">';
        $detalhes .= '<span class="vaga-data">📅 Publicado em ' . get_the_date('d/m/Y') . '</span>';
        $detalhes .= '</div>';
        $detalhes .= '</div>';

        // Cards com informações principais
        $detalhes .= '<div class="vaga-info-cards">';
        
        if ($salario && sv_vaga_campo_visivel($post_id, 'salario')) {
            $detalhes .= '<div class="vaga-card vaga-card-salario">';
            $detalhes .= '<div class="vaga-card-icon">💰</div>';
            $detalhes .= '<div class="vaga-card-content">';
            $detalhes .= '<div class="vaga-card-label">Salário</div>';
            $detalhes .= '<div class="vaga-card-value">' . esc_html($salario) . '</div>';
            $detalhes .= '</div>';
            $detalhes .= '</div>';
        }
        
        if ($localizacao && sv_vaga_campo_visivel($post_id, 'localizacao')) {
            $detalhes .= '<div class="vaga-card vaga-card-localizacao">';
            $detalhes .= '<div class="vaga-card-icon">📍</div>';
            $detalhes .= '<div class="vaga-card-content">';
            $detalhes .= '<div class="vaga-card-label">Localização</div>';
            $detalhes .= '<div class="vaga-card-value">' . esc_html($localizacao) . '</div>';
            $detalhes .= '</div>';
            $detalhes .= '</div>';
        }
        
        if ($modalidade && sv_vaga_campo_visivel($post_id, 'modalidade')) {
            $modalidade_icon = $modalidade === 'Remoto' ? '🏠' : ($modalidade === 'Híbrido' ? '🔄' : '🏢');
            $detalhes .= '<div class="vaga-card vaga-card-modalidade">';
            $detalhes .= '<div class="vaga-card-icon">' . $modalidade_icon . '</div>';
            $detalhes .= '<div class="vaga-card-content">';
            $detalhes .= '<div class="vaga-card-label">Modalidade</div>';
            $detalhes .= '<div class="vaga-card-value">' . esc_html($modalidade) . '</div>';
            $detalhes .= '</div>';
            $detalhes .= '</div>';
        }
        
        if ($tipo_contrato && sv_vaga_campo_visivel($post_id, 'tipo_contrato')) {
            $detalhes .= '<div class="vaga-card vaga-card-contrato">';
            $detalhes .= '<div class="vaga-card-icon">📋</div>';
            $detalhes .= '<div class="vaga-card-content">';
            $detalhes .= '<div class="vaga-card-label">Tipo de Contrato</div>';
            $detalhes .= '<div class="vaga-card-value">' . esc_html($tipo_contrato) . '</div>';
            $detalhes .= '</div>';
            $detalhes .= '</div>';
        }
        
        if ($nivel && sv_vaga_campo_visivel($post_id, 'nivel')) {
            $detalhes .= '<div class="vaga-card vaga-card-nivel">';
            $detalhes .= '<div class="vaga-card-icon">⭐</div>';
            $detalhes .= '<div class="vaga-card-content">';
            $detalhes .= '<div class="vaga-card-label">Nível</div>';
            $detalhes .= '<div class="vaga-card-value">' . esc_html($nivel) . '</div>';
            $detalhes .= '</div>';
            $detalhes .= '</div>';
        }
        
        $detalhes .= '</div>'; // Fim dos cards

        // Seções de requisitos e benefícios
$mostra_requisitos = $requisitos && sv_vaga_campo_visivel($post_id, 'requisitos');
$mostra_beneficios = $beneficios && sv_vaga_campo_visivel($post_id, 'beneficios');
if ($mostra_requisitos || $mostra_beneficios) {
    $detalhes .= '<div class="vaga-sections">';

    if ($mostra_requisitos) {
        $detalhes .= '<div class="vaga-section vaga-requisitos">';
        $detalhes .= '<h3 class="vaga-section-title">📝 Requisitos</h3>';
        $detalhes .= '<div class="vaga-section-content">' . nl2br(esc_html($requisitos)) . '</div>';
        $detalhes .= '</div>';
    }

    if ($mostra_beneficios) {
        $detalhes .= '<div class="vaga-section vaga-beneficios">';
        $detalhes .= '<h3 class="vaga-section-title">🎁 Benefícios</h3>';
        $detalhes .= '<div class="vaga-section-content">' . nl2br(esc_html($beneficios)) . '</div>';
        $detalhes .= '</div>';
    }

    $detalhes .= '</div>'; // Fim das seções
}

        // Botão de candidatura
        $detalhes .= '<div class="vaga-candidatura-cta">';
        $detalhes .= '<a href="#formulario-candidatura" class="vaga-btn-candidatar">🚀 Candidatar-se a esta vaga</a>';
        $detalhes .= '</div>';
        
        $detalhes .= '</div>'; // Fim do container

        return $detalhes . $content;
    }
    return $content;
}
add_filter('the_content', 'sv_exibir_detalhes_vaga_no_conteudo');

// Flush rewrite rules quando o plugin for ativado
function sv_flush_rewrite_rules() {
    sv_criar_cpt_vagas();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sv_flush_rewrite_rules');

