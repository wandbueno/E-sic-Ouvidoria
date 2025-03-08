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
			 <div class="form-group col-6">
				 <label for="cpf_cnpj">CPF/CNPJ</label>
				 <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="cpf-cnpj-mask">
			</div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email">
            </div>

            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="tel" name="telefone" id="telefone" class="phone-mask">
            </div>
			<div class="form-group">
                <label for="endereco">Endereço Completo</label>
                <textarea name="endereco" id="endereco" rows="3" placeholder="Rua, número, bairro, cidade, estado e CEP"></textarea>
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
<style>
/* Estilos existentes... */

/* Adicionar estilo para o novo campo de endereço */
#endereco {
    resize: vertical;
    min-height: 60px;
}

/* Ajuste para o layout em grid dos novos campos */
.form-row {
    display: flex;
    margin: 0 -10px;
    flex-wrap: wrap;
}

.form-row > .form-group {
    padding: 0 10px;
    flex: 1;
}

.col-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

@media (max-width: 768px) {
    .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Código existente...

    // Adicionar máscaras para CPF/CNPJ
    $('#cpf_cnpj').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length <= 11) {
            // Máscara de CPF
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
        } else {
            // Máscara de CNPJ
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
        }
        $(this).val(value);
    });
});
</script>