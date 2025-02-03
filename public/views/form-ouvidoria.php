<?php if (!defined('ABSPATH')) exit; ?>

<div class="ouvidoria-form-container">
    <h2>Formulário de Ouvidoria</h2>

    <form id="ouvidoria-form" class="ouvidoria-form" enctype="multipart/form-data">
        <?php wp_nonce_field('ouvidoria_public_nonce', 'ouvidoria_nonce'); ?>

        <div class="form-group">
            <label for="identificacao">Identificação *</label>
            <select name="identificacao" id="identificacao" required>
                <option value="">Selecione...</option>
                <option value="identificado">Desejo me identificar</option>
                <option value="anonimo">Desejo Anonimato</option>
            </select>
        </div>

        <div id="campos-identificacao" style="display: none;">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" name="nome" id="nome">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email">
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" name="telefone" id="telefone" class="phone-mask">
            </div>
        </div>

        <div class="form-group">
            <label for="tipo_manifestacao">Tipo de Manifestação *</label>
            <select name="tipo_manifestacao" id="tipo_manifestacao" required>
                <option value="">Selecione...</option>
                <option value="reclamacao">Reclamação</option>
                <option value="denuncia">Denúncia</option>
                <option value="sugestao">Sugestão</option>
                <option value="elogio">Elogio</option>
            </select>
        </div>

        <div class="form-group">
            <label for="mensagem">Mensagem *</label>
            <textarea name="mensagem" id="mensagem" rows="6" required></textarea>
        </div>

        <div class="form-group">
            <label for="arquivo">Anexo</label>
            <input type="file" name="arquivo" id="arquivo">
            <p class="description">Arquivos permitidos: PDF, DOC, DOCX (máx. 5MB)</p>
        </div>

        <div class="form-submit">
            <button type="submit" class="submit-button">Enviar Solicitação</button>
        </div>

        <div id="ouvidoria-mensagem" class="mensagem" style="display: none;"></div>
    </form>
</div>