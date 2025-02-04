<?php
function ouvidoria_formulario_shortcode($atts) {
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
    <!-- Botão para manifestação -->
    <button id="abrir-ouvidoria" class="botao-ouvidoria" style="width: <?php echo esc_attr($atts['largura']); ?>; height: <?php echo esc_attr($atts['altura']); ?>;">
        <span class="icone-ouvidoria">
            <svg viewBox="0 0 24 24" width="32" height="32">
                <path fill="currentColor" d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z"/>
            </svg>
        </span>
        <span class="texto-botao">Cadastrar Manifestação</span>
    </button>

    <!-- Popup de manifestação -->
    <div id="popup-ouvidoria" class="popup-ouvidoria" style="display: none;">
        <div class="popup-conteudo">
            <span class="fechar-popup">&times;</span>
            
            <h2 class="titulo-form-popup">Formulário de Manifestação e Solicitação</h2>
            
            <form id="form-ouvidoria-public" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('ouvidoria_public_nonce', 'nonce'); ?>
                
                <div class="form-group">
                    <label>Tipo de Manifestação*</label>
                    <div class="tipos-manifestacao">
                        <div class="tipo-item" data-value="reclamacao">
                            <span class="emoji">😠</span>
                            <span class="tipo-label">Reclamação</span>
                        </div>
                        <div class="tipo-item" data-value="denuncia">
                            <span class="emoji">🚨</span>
                            <span class="tipo-label">Denúncia</span>
                        </div>
                        <div class="tipo-item" data-value="sugestao">
                            <span class="emoji">💡</span>
                            <span class="tipo-label">Sugestão</span>
                        </div>
                        <div class="tipo-item" data-value="elogio">
                            <span class="emoji">👏</span>
                            <span class="tipo-label">Elogio</span>
                        </div>
                        <div class="tipo-item" data-value="informacao">
                            <span class="emoji">ℹ️</span>
                            <span class="tipo-label">Acesso à Informação</span>
                        </div>
                    </div>
                    <input type="hidden" name="tipo_manifestacao" id="tipo_manifestacao" required>
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
            </form>
        </div>
    </div>

    <style>
    /* Estilos existentes... */

    /* Novo grid system */
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

    /* Restante dos estilos existentes... */
    .botao-ouvidoria {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        padding: 15px 25px;
        border: none;
        border-radius: 12px;
        color: <?php echo esc_attr($atts['cor_texto']); ?>;
        cursor: pointer;
        transition: all 0.3s ease;
         background: <?php echo esc_attr($atts['cor_fundo']); ?>;
        box-shadow: 0 4px 15px rgba(0, 115, 170, 0.2);
    }

    .botao-ouvidoria:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 115, 170, 0.3);
        background: <?php echo esc_attr($atts['cor_hover']); ?>;
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
        color: #666;
        transition: color 0.3s ease;
    }

    .fechar-popup:hover {
        color: #333;
    }
		.titulo-form-popup{
			text-align: center;
		}

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
    }

    .form-group select,
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="tel"],
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }

    .form-group select:focus,
    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #0073aa;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.1);
    }

    .form-group textarea {
        min-height: 120px;
        resize: vertical;
    }

    .submit-button {
        width: 100%;
        padding: 12px;
        background: #0073aa;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .submit-button:hover {
        background: #005c88;
        transform: translateY(-1px);
    }

    .submit-button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .description {
        font-size: 12px;
        color: #666;
        margin-top: 5px;
    }

    .mensagem-erro {
        margin-top: 15px;
        padding: 12px 15px;
        border-radius: 4px;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
    }

    /* Styles for manifestation types */
    .tipos-manifestacao {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 15px;
        margin: 10px 0;
    }

    .tipo-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tipo-item:hover {
        border-color: #0073aa;
        background: #f0f9ff;
        transform: translateY(-2px);
    }

    .tipo-item.selected {
        border-color: #0073aa;
        background: #e6f3fa;
        box-shadow: 0 2px 8px rgba(0,115,170,0.2);
    }

    .tipo-item .emoji {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .tipo-item .tipo-label {
        font-size: 13px;
        text-align: center;
        color: #333;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#abrir-ouvidoria').on('click', function() {
            $('#popup-ouvidoria').fadeIn();
            $('body').css('overflow', 'hidden');
        });

        $('.fechar-popup').on('click', function() {
            $('#popup-ouvidoria').fadeOut();
            $('body').css('overflow', 'auto');
        });

        $(window).on('click', function(e) {
            if ($(e.target).is('#popup-ouvidoria')) {
                $('#popup-ouvidoria').fadeOut();
                $('body').css('overflow', 'auto');
            }
        });

        // Handle manifestation type selection
        $('.tipo-item').on('click', function() {
            $('.tipo-item').removeClass('selected');
            $(this).addClass('selected');
            $('#tipo_manifestacao').val($(this).data('value'));
        });

        $('#identificacao').on('change', function() {
            if ($(this).val() === 'identificado') {
                $('.campos-identificacao').slideDown();
                $('#nome, #email').prop('required', true);
            } else {
                $('.campos-identificacao').slideUp();
                $('#nome, #email').prop('required', false);
            }
        });

        $('#form-ouvidoria-public').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var $mensagemErro = $('#mensagem-erro');
            
            $submitButton.prop('disabled', true);
            $submitButton.html('<span class="dashicons dashicons-update-alt spin"></span> Enviando...');
            
            $mensagemErro.hide();
            
            var formData = new FormData(this);
            formData.append('action', 'salvar_solicitacao');
            
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
                        
                        $form[0].reset();
                        $('.campos-identificacao').hide();
                        $('#popup-ouvidoria').fadeOut();
                        $('body').css('overflow', 'auto');
                    } else {
                        $mensagemErro
                            .html('Erro ao enviar solicitação: ' + (response.data || 'Erro desconhecido'))
                            .slideDown();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro na requisição:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    
                    $mensagemErro
                        .html('Erro ao processar a solicitação. Por favor, tente novamente.')
                        .slideDown();
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                    $submitButton.html('Enviar Solicitação');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('ouvidoria_formulario', 'ouvidoria_formulario_shortcode');