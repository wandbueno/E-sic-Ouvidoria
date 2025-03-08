<?php
function esic_formulario_shortcode($atts) {
    // Define os atributos padrão e mescla com os fornecidos
    $atts = shortcode_atts(array(
        'largura' => '200px',  // Largura padrão do botão
        'altura' => '80px',    // Altura padrão do botão
        'cor_fundo' => '#0073aa',  // Cor de fundo padrão
        'cor_hover' => '#005c88',  // Cor de hover padrão
        'cor_texto' => '#ffffff'   // Cor do texto padrão
    ), $atts);
    
    ob_start();
    ?>
    <!-- Botão para e-SIC -->
    <button id="abrir-esic" class="botao-ouvidoria" style="width: <?php echo esc_attr($atts['largura']); ?>; height: <?php echo esc_attr($atts['altura']); ?>;">
        <span class="icone-ouvidoria">
            <svg viewBox="0 0 24 24" width="32" height="32">
                <path fill="currentColor" d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z"/>
            </svg>
        </span>
        <span class="texto-botao">Solicitar Informação (e-SIC)</span>
    </button>

    <!-- Popup de e-SIC -->
    <div id="popup-esic" class="popup-ouvidoria" style="display: none;">
        <div class="popup-conteudo">
            <span class="fechar-popup">&times;</span>
            
            <h2 class="titulo-form-popup">Solicitação de Acesso à Informação (e-SIC)</h2>
            
            <form id="form-esic-public" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('ouvidoria_public_nonce', 'nonce'); ?>
                
                <div class="form-group">
                    <label>Tipo de Manifestação</label>
                    <div class="tipo-item selected" data-value="informacao">
                        <span class="emoji">ℹ️</span>
                        <span class="tipo-label">Acesso à Informação</span>
                    </div>
                    <input type="hidden" name="tipo_manifestacao" id="tipo_manifestacao" value="informacao" required>
                </div>

                <!-- Grid para campos lado a lado -->
                <div class="form-row">
                    <div class="form-group col-6">
                        <label for="tipo_resposta">Forma de Recebimento de Resposta*</label>
                        <select name="tipo_resposta" id="tipo_resposta" required>
                            <option value="">Selecione...</option>
                            <option value="sistema">Pelo Sistema (Protocolo)</option>
                            <option value="presencial">Buscar/Consultar Pessoalmente</option>
                        </select>
                    </div>

                    <div class="form-group col-6">
                        <label for="identificacao">Identificação*</label>
                        <select name="identificacao" id="identificacao" required>
                            <option value="">Selecione...</option>
                            <option value="identificado">Identificado</option>
                            <option value="anonimo">Anônimo</option>
                        </select>
                    </div>
                </div>

                <div class="campos-identificacao" style="display:none;">
                    <div class="form-group">
                        <label for="nome">Nome Completo*</label>
                        <input type="text" name="nome" id="nome">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="email">E-mail*</label>
                            <input type="email" name="email" id="email">
                        </div>

                        <div class="form-group col-6">
                            <label for="telefone">Telefone</label>
                            <input type="tel" name="telefone" id="telefone">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mensagem">Mensagem*</label>
                    <textarea name="mensagem" id="mensagem" rows="5" required></textarea>
                </div>

                <div class="form-group">
                    <label for="arquivo">Anexo (opcional)</label>
                    <input type="file" name="arquivo" id="arquivo">
                    <p class="description">Arquivos permitidos: PDF, DOC, DOCX, JPG, PNG (máx. 5MB)</p>
                </div>
                
                <button type="submit" class="submit-button">Enviar Solicitação</button>

                <div id="mensagem-erro" class="mensagem-erro" style="display: none;"></div>
                <div id="mensagem-sucesso" class="mensagem-sucesso" style="display: none;"></div>
            </form>
        </div>
    </div>

    <script>
    var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    
    jQuery(document).ready(function($) {
        // Abrir popup
        $('#abrir-esic').click(function() {
            $('#popup-esic').fadeIn();
        });

        // Fechar popup
        $('.fechar-popup').click(function() {
            $('#popup-esic').fadeOut();
        });

        // Fechar popup ao clicar fora
        $(window).click(function(e) {
            if ($(e.target).is('#popup-esic')) {
                $('#popup-esic').fadeOut();
            }
        });

        // Mostrar/esconder campos de identificação
        $('#form-esic-public #identificacao').change(function() {
            var identificacao = $(this).val();
            var camposIdentificacao = $('#form-esic-public .campos-identificacao');
            var camposObrigatorios = camposIdentificacao.find('input[type="text"], input[type="email"]');
            
            if (identificacao === 'identificado') {
                camposIdentificacao.slideDown();
                camposObrigatorios.prop('required', true);
            } else {
                camposIdentificacao.slideUp();
                camposObrigatorios.prop('required', false);
            }
        });

        // Máscara para telefone
        if ($.fn.mask) {
            $('#form-esic-public #telefone').mask('(00) 00000-0000');
        }

        // Submissão do formulário
        $('#form-esic-public').submit(function(e) {
            e.preventDefault();
            
            var form = $(this);
            var formData = new FormData(this);
            
            // Adiciona ação do WordPress
            formData.append('action', 'salvar_solicitacao');
            
            // Desabilita o botão de envio
            form.find('button[type="submit"]').prop('disabled', true);
            form.find('button[type="submit"]').html('<span class="dashicons dashicons-update-alt spin"></span> Enviando...');
            
            // Limpa mensagens anteriores
            $('#mensagem-erro, #mensagem-sucesso').hide();
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        alert('Solicitação enviada com sucesso!\nSeu protocolo é: ' + response.data.protocolo);
                        
                        if (response.data.comprovante_url) {
                            window.open(response.data.comprovante_url, '_blank');
                        }
                        
                        form[0].reset();
                        $('.campos-identificacao').hide();
                        $('#popup-esic').fadeOut();
                        $('body').css('overflow', 'auto');
                    } else {
                        $('#mensagem-erro').html(response.data.message).fadeIn();
                    }
                },
                error: function() {
                    $('#mensagem-erro').html('Erro ao enviar a solicitação. Tente novamente.').fadeIn();
                },
                complete: function() {
                    form.find('button[type="submit"]').prop('disabled', false);
                    form.find('button[type="submit"]').html('Enviar Solicitação');
                }
            });
        });
    });
    </script>

    <style>
    .botao-ouvidoria {
        background-color: <?php echo esc_attr($atts['cor_fundo']); ?>;
        color: <?php echo esc_attr($atts['cor_texto']); ?>;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: background-color 0.3s;
        padding: 10px;
    }

    .botao-ouvidoria:hover {
        background-color: <?php echo esc_attr($atts['cor_hover']); ?>;
    }

    .tipo-item {
        display: flex;
        align-items: center;
        padding: 10px;
        margin: 5px 0;
        border: 2px solid #ddd;
        border-radius: 4px;
        cursor: default;
        background-color: #f5f5f5;
    }

    .tipo-item.selected {
        border-color: #0073aa;
        background-color: #f0f7fc;
		width: max-content;
    }

    .tipo-item .emoji {
        font-size: 24px;
        margin-right: 10px;
    }

    .tipo-item .tipo-label {
        font-size: 16px;
    }

    /* Restante dos estilos herdados do shortcode original */
    .form-row {
        display: flex;
        margin-left: -10px;
        margin-right: -10px;
        flex-wrap: wrap;
    }

    .form-row > .form-group {
        padding-left: 10px;
        padding-right: 10px;
        margin-bottom: 20px;
    }

    .col-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }

    /* Ajustes responsivos */
    @media (max-width: 768px) {
        .col-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    .popup-ouvidoria {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        backdrop-filter: blur(4px);
    }

    .popup-conteudo {
        position: relative;
        background: white;
        width: 90%;
        max-width: 1000px;
        margin: 5% auto;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        max-height: 90vh;
        overflow-y: auto;
    }

    .fechar-popup {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .mensagem-erro {
        color: #dc3545;
        margin-top: 10px;
        padding: 10px;
        border-radius: 4px;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
    }
    </style>
    <?php
    return ob_get_clean();
}

add_shortcode('esic_formulario', 'esic_formulario_shortcode');
