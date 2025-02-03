<?php if (!defined('ABSPATH')) exit; ?>

<div class="ouvidoria-consulta-container">
    <h2>Consulta de Protocolo</h2>

    <form id="consulta-protocolo-form" class="ouvidoria-consulta-form">
        <?php wp_nonce_field('ouvidoria_public_nonce', 'consulta_nonce'); ?>

        <div class="form-group">
            <label for="protocolo">Número do Protocolo *</label>
            <input type="text" name="protocolo" id="protocolo" required>
        </div>

        <div class="form-submit">
            <button type="submit" class="submit-button">Consultar</button>
        </div>
    </form>

    <div id="resultado-consulta" class="resultado-consulta" style="display: none;">
        <h3>Resultado da Consulta</h3>
        <div class="dados-solicitacao">
            <!-- Preenchido via JavaScript -->
        </div>
    </div>
</div>