<?php 
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>Nova Solicitação</h1>
    
    <form method="post" action="" id="form-nova-solicitacao" enctype="multipart/form-data">
        <?php wp_nonce_field('ouvidoria_admin_nonce', 'nova_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th><label for="tipo_manifestacao">Tipo de Manifestação *</label></th>
                <td>
                    <select name="tipo_manifestacao" id="tipo_manifestacao" required>
                        <option value="">Selecione...</option>
                        <option value="reclamacao">Reclamação</option>
                        <option value="denuncia">Denúncia</option>
                        <option value="sugestao">Sugestão</option>
                        <option value="elogio">Elogio</option>
                        <option value="informacao">Acesso à Informação</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="tipo_resposta">Forma de Recebimento de Resposta *</label></th>
                <td>
                    <select name="tipo_resposta" id="tipo_resposta" required>
                        <option value="">Selecione...</option>
                        <option value="sistema">Pelo Sistema (Protocolo)</option>
                        <option value="presencial">Buscar/Consultar Pessoalmente</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><label for="identificacao">Identificação *</label></th>
                <td>
                    <select name="identificacao" id="identificacao" required>
                        <option value="">Selecione...</option>
                        <option value="identificado">Identificado</option>
                        <option value="anonimo">Anônimo</option>
                    </select>
                </td>
            </tr>

            <tr class="campo-identificacao" style="display: none;">
                <th><label for="nome">Nome *</label></th>
                <td>
                    <input type="text" name="nome" id="nome" class="regular-text">
                </td>
            </tr>

            <tr class="campo-identificacao" style="display: none;">
                <th><label for="email">E-mail *</label></th>
                <td>
                    <input type="email" name="email" id="email" class="regular-text">
                </td>
            </tr>

            <tr class="campo-identificacao" style="display: none;">
                <th><label for="telefone">Telefone</label></th>
                <td>
                    <input type="tel" name="telefone" id="telefone" class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="mensagem">Mensagem *</label></th>
                <td>
                    <textarea name="mensagem" id="mensagem" rows="5" class="large-text" required></textarea>
                </td>
            </tr>

            <tr>
                <th><label for="arquivo">Anexo</label></th>
                <td>
                    <input type="file" name="arquivo" id="arquivo">
                    <p class="description">Arquivos permitidos: PDF, DOC, DOCX, JPG, PNG (máx. 5MB)</p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary">Criar Solicitação</button>
        </p>

        <div id="mensagem-resposta" class="notice" style="display: none;"></div>
    </form>
</div>