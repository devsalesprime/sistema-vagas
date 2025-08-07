<?php
// Carrega o CSS do formulário e dos detalhes da vaga
function sv_carregar_estilo_formulario_vagas() {
    global $post;
    // Verifica se estamos em uma página/post e se contém o shortcode
    if (is_singular() && is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'formulario_vagas')) {
        wp_enqueue_style(
            'formulario-vagas-style',
            SISTEMA_VAGAS_PLUGIN_URL . 'assets/css/formulario-vagas.css?v1',
            array(),
            '1.1'
        );
    }
    // Também verifica se estamos em uma página de arquivo de vagas ou single de vaga
    if (is_post_type_archive('vaga') || is_singular('vaga')) {
        wp_enqueue_style(
            'formulario-vagas-style',
            SISTEMA_VAGAS_PLUGIN_URL . 'assets/css/formulario-vagas.css?v1',
            array(),
            '1.1'
        );
        // Carrega o CSS específico para os detalhes da vaga
        wp_enqueue_style(
            'vaga-detalhes-style',
            SISTEMA_VAGAS_PLUGIN_URL . 'assets/css/vaga-detalhes.css?v4',
            array(),
            '1.1'
        );
    }
}
add_action('wp_enqueue_scripts', 'sv_carregar_estilo_formulario_vagas');

// Adiciona JavaScript para AJAX e melhor experiência do usuário
function sv_carregar_script_formulario_vagas() {
    global $post;
    if ((is_singular() && is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'formulario_vagas')) || is_singular('vaga')) {
        wp_enqueue_script('jquery');
        // Localiza o script para usar AJAX
        wp_localize_script('jquery', 'sv_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sv_candidatura_nonce')
        ));
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Função para envio via AJAX
                $("#formulario-candidatura").on("submit", function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var formData = new FormData(this);
                    var submitBtn = form.find("button[type=submit]");
                    var originalText = submitBtn.text();
                    // Validação básica
                    var nome = $("input[name=\'nome\']").val();
                    var email = $("input[name=\'email\']").val();
                    var vaga_id = $("select[name=\'vaga_id\']").val();
                    if (!nome || !email || !vaga_id) {
                        sv_mostrar_mensagem("Por favor, preencha os campos obrigatórios: Nome, Email e Vaga.", "erro");
                        return false;
                    }
                    // Adiciona ação AJAX
                    formData.append("action", "sv_processar_candidatura");
                    formData.append("nonce", sv_ajax.nonce);
                    // Mostra estado de carregamento
                    submitBtn.text("Enviando...").prop("disabled", true);
                    // Remove mensagens anteriores
                    $(".mensagem-sucesso, .mensagem-erro").remove();
                    // Envia via AJAX
                    $.ajax({
                        url: sv_ajax.ajax_url,
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                // Mostra mensagem de sucesso
                                sv_mostrar_mensagem_sucesso(response.data.message);
                                // Oculta o formulário com animação
                                form.fadeOut(500, function() {
                                    $(this).remove();
                                });
                            } else {
                                // Mostra mensagem de erro
                                sv_mostrar_mensagem(response.data.message, "erro");
                                submitBtn.text(originalText).prop("disabled", false);
                            }
                        },
                        error: function() {
                            sv_mostrar_mensagem("Erro de conexão. Tente novamente mais tarde.", "erro");
                            submitBtn.text(originalText).prop("disabled", false);
                        }
                    });
                });
                // Função para mostrar mensagens de erro
                function sv_mostrar_mensagem(mensagem, tipo) {
                    var classe = tipo === "erro" ? "mensagem-erro" : "mensagem-sucesso";
                    var icone = tipo === "erro" ? "❌" : "✅";
                    var html = "<div class=\"" + classe + "\">" + icone + " " + mensagem + "</div>";
                    $("#formulario-candidatura").before(html);
                    // Scroll para a mensagem
                    $("html, body").animate({
                        scrollTop: $("." + classe).offset().top - 20
                    }, 500);
                }
                // Função para mostrar mensagem de sucesso elaborada
                function sv_mostrar_mensagem_sucesso(mensagem) {
                    var html = `
                        <div class="candidatura-sucesso-container">
                            <div class="candidatura-sucesso-icon">
                                <div class="check-animation">
                                    <svg viewBox="0 0 52 52" class="check-svg">
                                        <circle class="check-circle" cx="26" cy="26" r="25" fill="none"/>
                                        <path class="check-path" fill="none" d="m14.1 27.2l7.1 7.2 16.7-16.8"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="candidatura-sucesso-content">
                                <h3>Candidatura Enviada com Sucesso!</h3>
                                <p>` + mensagem + `</p>
                                <div class="candidatura-sucesso-info">
                                    <p><strong>O que acontece agora?</strong></p>
                                    <ul>
                                        <li>✅ Sua candidatura foi registrada em nosso sistema</li>
                                        <li>📧 Você receberá um email de confirmação em breve</li>
                                        <li>👥 Nossa equipe de RH analisará seu perfil</li>
                                        <li>📞 Entraremos em contato se seu perfil for selecionado</li>
                                    </ul>
                                </div>
                                <div class="candidatura-sucesso-actions">
                                    <a href="/" class="btn-voltar-home">🏠 Voltar ao Início</a>
                                    <a href="/vagas" class="btn-ver-outras-vagas">💼 Ver Outras Vagas</a>
                                </div>
                            </div>
                        </div>
                    `;
                    $("#formulario-candidatura").before(html);
                    // Scroll para a mensagem
                    $("html, body").animate({
                        scrollTop: $(".candidatura-sucesso-container").offset().top - 20
                    }, 500);
                }
                // Smooth scroll para o formulário quando clicar no botão de candidatura
                $(".vaga-btn-candidatar").on("click", function(e) {
                    e.preventDefault();
                    var target = $($(this).attr("href"));
                    if (target.length) {
                        $("html, body").animate({
                            scrollTop: target.offset().top - 20
                        }, 800);
                    }
                });
                // Auto-selecionar vaga se estivermos na página de uma vaga específica
                if (typeof sv_vaga_atual !== "undefined" && sv_vaga_atual) {
                    $("#vaga_id").val(sv_vaga_atual);
                }
            });
        ');
        // Se estivermos em uma página de vaga específica, passa o ID da vaga para o JavaScript
        if (is_singular('vaga')) {
            wp_add_inline_script('jquery', 'var sv_vaga_atual = ' . get_the_ID() . ';', 'before');
        }
    }
}
add_action('wp_enqueue_scripts', 'sv_carregar_script_formulario_vagas');

// Função para enviar email de confirmação para o candidato
function sv_enviar_email_candidato($dados_candidatura, $vaga) {
    $assunto = "Confirmação de Candidatura: " . $vaga->post_title;
    $mensagem = "Olá " . $dados_candidatura['nome'] . ",\n\n";
    $mensagem .= "Agradecemos por se candidatar à vaga \"" . $vaga->post_title . "\" na " . get_bloginfo('name') . ".\n\n";
    $mensagem .= "=== DETALHES DA SUA CANDIDATURA ===\n";
    $mensagem .= "Vaga: " . $vaga->post_title . "\n";
    $mensagem .= "Empresa: " . get_post_meta($vaga->ID, '_vaga_empresa', true) . "\n";
    $mensagem .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n\n";
    $mensagem .= "=== PRÓXIMOS PASSOS ===\n";
    $mensagem .= "Nossa equipe de RH analisará seu perfil e entrará em contato caso você seja selecionado para as próximas etapas do processo.\n";
    $mensagem .= "Caso precise entrar em contato conosco, responda a este email.\n\n";
    $mensagem .= "Atenciosamente,\n";
    $mensagem .= "Equipe de Recrutamento\n";
    $mensagem .= get_bloginfo('name') . "\n";
    $mensagem .= get_bloginfo('url') . "\n";

    $headers = array(
        "From: " . get_bloginfo('name') . " <rh@salesprime.com.br>",
        "Reply-To: rh@salesprime.com.br",
        "Content-Type: text/plain; charset=UTF-8"
    );

    return wp_mail($dados_candidatura['email'], $assunto, $mensagem, $headers);
}

// Handler AJAX para processar candidatura
function sv_processar_candidatura_ajax() {
    // Verifica nonce
    if (!wp_verify_nonce($_POST['nonce'], 'sv_candidatura_nonce')) {
        wp_send_json_error(array('message' => 'Erro de segurança. Recarregue a página e tente novamente.'));
    }

    // Sanitiza e valida os dados
    $nome      = sanitize_text_field($_POST['nome']);
    $email     = sanitize_email($_POST['email']);
    $telefone  = sanitize_text_field($_POST['telefone']);
    $linkedin  = esc_url_raw($_POST['linkedin']);
    $pretensao = sanitize_text_field($_POST['pretensao']);
    $vaga_id   = intval($_POST['vaga_id']);
    $mensagem  = sanitize_textarea_field($_POST['mensagem']);

    // Validação dos campos obrigatórios
    if (empty($nome) || empty($email) || empty($vaga_id)) {
        wp_send_json_error(array('message' => 'Por favor, preencha todos os campos obrigatórios.'));
    }

    // Valida se a vaga existe
    $vaga = get_post($vaga_id);
    if (!$vaga || $vaga->post_type !== 'vaga' || $vaga->post_status !== 'publish') {
        wp_send_json_error(array('message' => 'Vaga inválida ou não disponível.'));
    }

    // Verifica se já existe candidatura para esta vaga com este email
    $candidatura_existente = get_posts(array(
        'post_type' => 'candidatura',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => '_candidatura_vaga_id',
                'value' => $vaga_id,
                'compare' => '='
            ),
            array(
                'key' => '_candidatura_email',
                'value' => $email,
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));

    if ($candidatura_existente) {
        wp_send_json_error(array('message' => 'Você já se candidatou a esta vaga anteriormente.'));
    }

    // Processa o upload do currículo
    $curriculo_url = '';
    if (!empty($_FILES['curriculo']['tmp_name'])) {
        // Verifica o tamanho do arquivo (5MB máximo)
        if ($_FILES['curriculo']['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array('message' => 'Arquivo muito grande. O currículo deve ter no máximo 5MB.'));
        }
        // Verifica se é um PDF
        $file_type = wp_check_filetype($_FILES['curriculo']['name']);
        if ($file_type['ext'] !== 'pdf') {
            wp_send_json_error(array('message' => 'Formato de arquivo inválido. Apenas arquivos PDF são aceitos.'));
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $uploadedfile = $_FILES['curriculo'];
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $curriculo_url = $movefile['url'];
        } else {
            wp_send_json_error(array('message' => 'Erro ao fazer upload do currículo: ' . $movefile['error']));
        }
    }

    // Cria a candidatura no WordPress
    $dados_candidatura = array(
        'nome' => $nome,
        'email' => $email,
        'telefone' => $telefone,
        'linkedin' => $linkedin,
        'pretensao' => $pretensao,
        'vaga_id' => $vaga_id,
        'mensagem' => $mensagem,
        'curriculo_url' => $curriculo_url,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    );

    $candidatura_id = sv_criar_candidatura($dados_candidatura);

    if (!$candidatura_id) {
        wp_send_json_error(array('message' => 'Erro ao salvar candidatura no sistema. Tente novamente.'));
    }

    // Monta a mensagem do email para o RH
    $mensagem_email = "Nova candidatura recebida através do site:\n\n";
    $mensagem_email .= "=== DADOS DO CANDIDATO ===\n";
    $mensagem_email .= "Nome: $nome\n";
    $mensagem_email .= "Email: $email\n";
    $mensagem_email .= "Telefone: " . ($telefone ? $telefone : 'Não informado') . "\n";
    $mensagem_email .= "LinkedIn/Portfólio: " . ($linkedin ? $linkedin : 'Não informado') . "\n";
    $mensagem_email .= "Pretensão Salarial: " . ($pretensao ? $pretensao : 'Não informada') . "\n\n";
    $mensagem_email .= "=== VAGA ===\n";
    $mensagem_email .= "Vaga: {$vaga->post_title}\n";
    $mensagem_email .= "Link da vaga: " . get_permalink($vaga_id) . "\n\n";
    $mensagem_email .= "=== MENSAGEM DO CANDIDATO ===\n";
    $mensagem_email .= ($mensagem ? $mensagem : 'Nenhuma mensagem adicional.') . "\n\n";

    if ($curriculo_url) {
        $mensagem_email .= "=== CURRÍCULO ===\n";
        $mensagem_email .= "Link para download: $curriculo_url\n\n";
    }

    $mensagem_email .= "=== GERENCIAR CANDIDATURA ===\n";
    $mensagem_email .= "Visualizar no admin: " . admin_url('post.php?post=' . $candidatura_id . '&action=edit') . "\n\n";
    $mensagem_email .= "=== INFORMAÇÕES TÉCNICAS ===\n";
    $mensagem_email .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
    $mensagem_email .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $mensagem_email .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";

    // Configurações do email para o RH
    $destino = 'rh@salesprime.com.br';
    $assunto = "[" . get_bloginfo('name') . "] Nova candidatura: {$vaga->post_title}";
    $headers = array(
        "From: " . get_bloginfo('name') . " <rh@salesprime.com.br>",
        "Reply-To: $nome <$email>",
        "Content-Type: text/plain; charset=UTF-8"
    );

    // Envia o email para o RH
    $email_enviado_rh = wp_mail($destino, $assunto, $mensagem_email, $headers);

    // Envia o email de confirmação para o candidato
    $email_enviado_candidato = sv_enviar_email_candidato($dados_candidatura, $vaga);

    // Resposta AJAX sempre de sucesso se a candidatura foi salva
    wp_send_json_success(array(
        'message' => 'Obrigado, ' . $nome . '! Sua candidatura para a vaga "' . $vaga->post_title . '" foi registrada com sucesso. Você receberá um email de confirmação em breve.',
        'candidatura_id' => $candidatura_id
    ));
}
add_action('wp_ajax_sv_processar_candidatura', 'sv_processar_candidatura_ajax');
add_action('wp_ajax_nopriv_sv_processar_candidatura', 'sv_processar_candidatura_ajax');

// Exibir formulário com shortcode
function sv_exibir_formulario_vagas($atts = array()) {
    $atts = shortcode_atts(array(
        'vaga_id' => 0,
        'titulo' => 'Candidate-se a uma vaga'
    ), $atts);

    $vaga_especifica = intval($atts['vaga_id']);

    if ($vaga_especifica) {
        // Se uma vaga específica foi definida, busca apenas ela
        $vaga = get_post($vaga_especifica);
        if ($vaga && $vaga->post_type === 'vaga' && $vaga->post_status === 'publish') {
            $vagas = array($vaga);
        } else {
            $vagas = array();
        }
    } else {
        // Busca todas as vagas disponíveis
        $args = array(
            'post_type' => 'vaga',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        $vagas = get_posts($args);
    }

    // Verifica se há mensagens para exibir (fallback para usuários sem JavaScript)
    $mensagem = '';
    if (isset($_GET['mensagem'])) {
        switch ($_GET['mensagem']) {
            case 'sucesso':
                $mensagem = '<div class="mensagem-sucesso">✅ Sua candidatura foi enviada com sucesso! Você receberá um email de confirmação em breve.</div>';
                break;
            case 'erro':
                $mensagem = '<div class="mensagem-erro">❌ Erro ao enviar candidatura. Tente novamente mais tarde.</div>';
                break;
            case 'erro_upload':
                $mensagem = '<div class="mensagem-erro">❌ Erro ao fazer upload do currículo. Verifique se o arquivo é um PDF válido.</div>';
                break;
            case 'campos_obrigatorios':
                $mensagem = '<div class="mensagem-erro">❌ Por favor, preencha todos os campos obrigatórios.</div>';
                break;
            case 'ja_candidatou':
                $mensagem = '<div class="mensagem-erro">❌ Você já se candidatou a esta vaga anteriormente.</div>';
                break;
        }
    }

    ob_start(); ?>
    <?php echo $mensagem; ?>
    <div id="formulario-candidatura-container">
        <h3><?php echo esc_html($atts['titulo']); ?></h3>
        <form id="formulario-candidatura" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('enviar_candidatura_action', 'enviar_candidatura_nonce'); ?>
            <div class="campo-obrigatorio">
                <label for="nome">Seu Nome: <span class="asterisco">*</span></label>
                <input type="text" name="nome" id="nome" required value="<?php echo isset($_POST['nome']) ? esc_attr($_POST['nome']) : ''; ?>">
            </div>
            <div class="campo-obrigatorio">
                <label for="email">Seu Email: <span class="asterisco">*</span></label>
                <input type="email" name="email" id="email" required value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
            </div>
            <div class="campo-obrigatorio">
                <label for="telefone">Telefone (WhatsApp):<span class="asterisco">*</span></label>
                <input type="text" name="telefone" id="telefone" required value="<?php echo isset($_POST['telefone']) ? esc_attr($_POST['telefone']) : ''; ?>" placeholder="(11) 99999-9999">
            </div>
            <div class="campo-obrigatorio">
                <label for="linkedin">LinkedIn / Portfólio:<span class="asterisco">*</span></label>
                <input type="url" name="linkedin" id="linkedin" required value="<?php echo isset($_POST['linkedin']) ? esc_attr($_POST['linkedin']) : ''; ?>" placeholder="https://linkedin.com/in/seuperfil">
            </div>
            <!--
            <div class="campo-opcional">
                <label for="pretensao">Pretensão Salarial:</label>
                <input type="text" name="pretensao" id="pretensao" value="<?php echo isset($_POST['pretensao']) ? esc_attr($_POST['pretensao']) : ''; ?>" placeholder="Ex: R$ 5.000 ou A combinar">
            </div>
            -->
            <div class="campo-obrigatorio">
                <label for="vaga_id">Escolha uma vaga: <span class="asterisco">*</span></label>
                <select name="vaga_id" id="vaga_id" required <?php echo (!empty($vagas) ? '' : 'disabled'); ?>>
                    <?php if (!empty($vagas)) : ?>
                        <?php if (!$vaga_especifica): ?>
                            <option value="">-- Selecione uma vaga --</option>
                        <?php endif; ?>
                        <?php foreach ($vagas as $vaga) : ?>
                            <option value="<?php echo $vaga->ID; ?>" <?php selected(isset($_POST['vaga_id']) ? $_POST['vaga_id'] : ($vaga_especifica ? $vaga_especifica : ''), $vaga->ID); ?>>
                                <?php echo esc_html($vaga->post_title); ?>
                                <?php 
                                $empresa = get_post_meta($vaga->ID, '_vaga_empresa', true);
                                if ($empresa) {
                                    echo ' - ' . esc_html($empresa);
                                }
                                ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <option value="">Nenhuma vaga disponível no momento</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="campo-obrigatorio">
                <label for="mensagem">Mensagem / Apresentação:<span class="asterisco">*</span></label>
                <textarea name="mensagem" id="mensagem" rows="5" required placeholder="Conte um pouco sobre você e por que se interessa por esta vaga..."><?php echo isset($_POST['mensagem']) ? esc_textarea($_POST['mensagem']) : ''; ?></textarea>
            </div>
            <div class="campo-obrigatorio">
                <label for="curriculo">Anexar Currículo (PDF - máx. 5MB):<span class="asterisco">*</span></label>
                <input type="file" name="curriculo" required id="curriculo" accept=".pdf">
                <small>Formatos aceitos: PDF. Tamanho máximo: 5MB</small>
            </div>
            <div class="campo-envio">
                <button type="submit" name="enviar_candidatura" <?php echo (!empty($vagas) ? '' : 'disabled'); ?>>
                    <?php echo (!empty($vagas) ? '🚀 Enviar Candidatura' : 'Nenhuma vaga disponível'); ?>
                </button>
            </div>
            <p class="campos-obrigatorios-info">
                <span class="asterisco">*</span> Campos obrigatórios
            </p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('formulario_vagas', 'sv_exibir_formulario_vagas');

// Mantém o processamento tradicional como fallback para usuários sem JavaScript
function sv_processar_envio_formulario_vagas() {
    if (isset($_POST['enviar_candidatura'])) {
        // Verifica o nonce para segurança
        if (!wp_verify_nonce($_POST['enviar_candidatura_nonce'], 'enviar_candidatura_action')) {
            wp_redirect(add_query_arg('mensagem', 'erro', wp_get_referer()));
            exit;
        }

        // Sanitiza e valida os dados
        $nome      = sanitize_text_field($_POST['nome']);
        $email     = sanitize_email($_POST['email']);
        $telefone  = sanitize_text_field($_POST['telefone']);
        $linkedin  = esc_url_raw($_POST['linkedin']);
        $pretensao = sanitize_text_field($_POST['pretensao']);
        $vaga_id   = intval($_POST['vaga_id']);
        $mensagem  = sanitize_textarea_field($_POST['mensagem']);

        // Validação dos campos obrigatórios
        if (empty($nome) || empty($email) || empty($vaga_id)) {
            wp_redirect(add_query_arg('mensagem', 'campos_obrigatorios', wp_get_referer()));
            exit;
        }

        // Valida se a vaga existe
        $vaga = get_post($vaga_id);
        if (!$vaga || $vaga->post_type !== 'vaga' || $vaga->post_status !== 'publish') {
            wp_redirect(add_query_arg('mensagem', 'erro', wp_get_referer()));
            exit;
        }

        // Verifica se já existe candidatura para esta vaga com este email
        $candidatura_existente = get_posts(array(
            'post_type' => 'candidatura',
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_candidatura_vaga_id',
                    'value' => $vaga_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_candidatura_email',
                    'value' => $email,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));

        if ($candidatura_existente) {
            wp_redirect(add_query_arg('mensagem', 'ja_candidatou', wp_get_referer()));
            exit;
        }

        // Processa o upload do currículo
        $curriculo_url = '';
        if (!empty($_FILES['curriculo']['tmp_name'])) {
            // Verifica o tamanho do arquivo (5MB máximo)
            if ($_FILES['curriculo']['size'] > 5 * 1024 * 1024) {
                wp_redirect(add_query_arg('mensagem', 'erro_upload', wp_get_referer()));
                exit;
            }
            // Verifica se é um PDF
            $file_type = wp_check_filetype($_FILES['curriculo']['name']);
            if ($file_type['ext'] !== 'pdf') {
                wp_redirect(add_query_arg('mensagem', 'erro_upload', wp_get_referer()));
                exit;
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $uploadedfile = $_FILES['curriculo'];
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                $curriculo_url = $movefile['url'];
            } else {
                wp_redirect(add_query_arg('mensagem', 'erro_upload', wp_get_referer()));
                exit;
            }
        }

        // Cria a candidatura no WordPress
        $dados_candidatura = array(
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'linkedin' => $linkedin,
            'pretensao' => $pretensao,
            'vaga_id' => $vaga_id,
            'mensagem' => $mensagem,
            'curriculo_url' => $curriculo_url,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        );

        $candidatura_id = sv_criar_candidatura($dados_candidatura);

        if (!$candidatura_id) {
            wp_redirect(add_query_arg('mensagem', 'erro', wp_get_referer()));
            exit;
        }

        // Monta a mensagem do email para o RH
        $mensagem_email = "Nova candidatura recebida através do site:\n\n";
        $mensagem_email .= "=== DADOS DO CANDIDATO ===\n";
        $mensagem_email .= "Nome: $nome\n";
        $mensagem_email .= "Email: $email\n";
        $mensagem_email .= "Telefone: " . ($telefone ? $telefone : 'Não informado') . "\n";
        $mensagem_email .= "LinkedIn/Portfólio: " . ($linkedin ? $linkedin : 'Não informado') . "\n";
        $mensagem_email .= "Pretensão Salarial: " . ($pretensao ? $pretensao : 'Não informada') . "\n\n";
        $mensagem_email .= "=== VAGA ===\n";
        $mensagem_email .= "Vaga: {$vaga->post_title}\n";
        $mensagem_email .= "Link da vaga: " . get_permalink($vaga_id) . "\n\n";
        $mensagem_email .= "=== MENSAGEM DO CANDIDATO ===\n";
        $mensagem_email .= ($mensagem ? $mensagem : 'Nenhuma mensagem adicional.') . "\n\n";

        if ($curriculo_url) {
            $mensagem_email .= "=== CURRÍCULO ===\n";
            $mensagem_email .= "Link para download: $curriculo_url\n\n";
        }

        $mensagem_email .= "=== GERENCIAR CANDIDATURA ===\n";
        $mensagem_email .= "Visualizar no admin: " . admin_url('post.php?post=' . $candidatura_id . '&action=edit') . "\n\n";
        $mensagem_email .= "=== INFORMAÇÕES TÉCNICAS ===\n";
        $mensagem_email .= "Data/Hora: " . current_time('d/m/Y H:i:s') . "\n";
        $mensagem_email .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        $mensagem_email .= "User Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\n";

        // Configurações do email para o RH
        $destino = 'rh@salesprime.com.br';
        $assunto = "[" . get_bloginfo('name') . "] Nova candidatura: {$vaga->post_title}";
        $headers = array(
            "From: " . get_bloginfo('name') . " <rh@salesprime.com.br>",
            "Reply-To: $nome <$email>",
            "Content-Type: text/plain; charset=UTF-8"
        );

        // Envia o email para o RH
        $email_enviado_rh = wp_mail($destino, $assunto, $mensagem_email, $headers);

        // Envia o email de confirmação para o candidato
        $email_enviado_candidato = sv_enviar_email_candidato($dados_candidatura, $vaga);

        // Redireciona com mensagem de sucesso
        wp_redirect(add_query_arg('mensagem', 'sucesso', wp_get_referer()));
        exit;
    }
}
add_action('init', 'sv_processar_envio_formulario_vagas');