<?php
function ouvidoria_consulta_shortcode() {
    ob_start();
    ?>
    <div class="ouvidoria-consulta-container">
        <h2>Consulta de Protocolo</h2>
        
        <form id="ouvidoria-consulta-form" class="ouvidoria-form">
            <?php wp_nonce_field('ouvidoria_public_nonce', 'consulta_nonce'); ?>
            
            <div class="form-group">
                <label for="protocolo">Número do Protocolo</label>
                <input type="text" 
                       id="protocolo" 
                       name="protocolo" 
                       required 
                       placeholder="Digite o número do protocolo"
                       class="ouvidoria-input">
            </div>

            <button type="submit" class="ouvidoria-button">Consultar</button>
            
            <div id="ouvidoria-mensagem" class="ouvidoria-mensagem" style="display: none;"></div>
        </form>

        <div id="ouvidoria-resultado" class="ouvidoria-resultado" style="display: none;">
            <h3>Resultado da Consulta</h3>
            <div class="ouvidoria-detalhes"></div>
        </div>
    </div>

    <style>
    .ouvidoria-consulta-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .ouvidoria-form {
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .ouvidoria-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 16px;
    }

    .ouvidoria-button {
        background: #0073aa;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }

    .ouvidoria-button:hover {
        background: #005177;
    }

    .ouvidoria-mensagem {
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
    }

    .ouvidoria-mensagem.erro {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .ouvidoria-resultado {
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 4px;
    }

    .ouvidoria-detalhes p {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#ouvidoria-consulta-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('button[type="submit"]');
            var $mensagem = $('#ouvidoria-mensagem');
            var $resultado = $('#ouvidoria-resultado');
            var $detalhes = $('.ouvidoria-detalhes');
            
            var protocolo = $('#protocolo').val();
            
            if (!protocolo) {
                $mensagem.addClass('erro').html('Por favor, informe o número do protocolo').show();
                return;
            }

            $submit.prop('disabled', true).text('Consultando...');
            $mensagem.hide();
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: {
                    action: 'consultar_protocolo',
                    nonce: '<?php echo wp_create_nonce("ouvidoria_public_nonce"); ?>',
                    protocolo: protocolo
                },
                success: function(response) {
                    console.log('Resposta:', response);
                    
                    if (response.success) {
                        var html = '<p><strong>Protocolo:</strong> ' + response.data.protocolo + '</p>';
                        html += '<p><strong>Status:</strong> ' + response.data.status + '</p>';
                        html += '<p><strong>Data:</strong> ' + response.data.data + '</p>';
                        html += '<p><strong>Tipo:</strong> ' + response.data.tipo + '</p>';

                        if (response.data.respostas && response.data.respostas.length > 0) {
                            html += '<div class="ouvidoria-respostas">';
                            html += '<h4>Respostas:</h4>';
                            response.data.respostas.forEach(function(resp) {
                                html += '<div class="ouvidoria-resposta">';
                                html += '<p><strong>Data:</strong> ' + resp.data + '</p>';
                                html += '<p>' + resp.texto + '</p>';
                                html += '</div>';
                            });
                            html += '</div>';
                        }

                        $detalhes.html(html);
                        $resultado.slideDown();
                    } else {
                        $mensagem.addClass('erro').html(response.data).show();
                        $resultado.hide();
                    }
                },
                error: function() {
                    $mensagem.addClass('erro').html('Erro ao processar a consulta. Tente novamente.').show();
                    $resultado.hide();
                },
                complete: function() {
                    $submit.prop('disabled', false).text('Consultar');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('ouvidoria_consulta', 'ouvidoria_consulta_shortcode');