<?php
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permissões
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado');
}

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_estatistica'])) {
    check_admin_referer('ouvidoria_estatistica_historica');
    
    $dados = array(
        'ano' => intval($_POST['ano']),
        'mes' => intval($_POST['mes']),
        'total_manifestacoes' => intval($_POST['total_manifestacoes']),
        'reclamacoes' => intval($_POST['reclamacoes']),
        'denuncias' => intval($_POST['denuncias']),
        'sugestoes' => intval($_POST['sugestoes']),
        'elogios' => intval($_POST['elogios']),
        'informacoes' => intval($_POST['informacoes']),
        'identificadas' => intval($_POST['identificadas']),
        'anonimas' => intval($_POST['anonimas']),
        'pendentes' => intval($_POST['pendentes']),
        'em_analise' => intval($_POST['em_analise']),
        'respondidas' => intval($_POST['respondidas']),
        'encerradas' => intval($_POST['encerradas']),
        'indeferidas' => intval($_POST['indeferidas']),
        'resposta_sistema' => intval($_POST['resposta_sistema']),
        'resposta_presencial' => intval($_POST['resposta_presencial']),
        'observacoes' => sanitize_textarea_field($_POST['observacoes'])
    );

    $resultado = $this->database->inserir_estatistica_historica($dados);
    
    if ($resultado) {
        echo '<div class="notice notice-success"><p>Estatísticas históricas registradas com sucesso!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Erro ao registrar estatísticas. Verifique se já não existem dados para o mês/ano selecionado.</p></div>';
    }
}

// Buscar estatísticas existentes
$estatisticas = $this->database->get_estatisticas_historicas();
?>

<div class="wrap">
    <h1>Estatísticas Históricas</h1>
    
    <div class="estatisticas-form-container">
        <h2>Adicionar Novo Registro</h2>
        
        <form method="post" action="" class="estatisticas-form">
            <?php wp_nonce_field('ouvidoria_estatistica_historica'); ?>
            
            <!-- Período -->
            <div class="form-section">
                <h3>Período</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="ano">Ano</label>
                        <select name="ano" id="ano" required>
                            <?php
                            $ano_atual = date('Y');
                            for ($i = 2020; $i <= $ano_atual - 1; $i++) {
                                echo "<option value='{$i}'>{$i}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group col-4">
                        <label for="mes">Mês</label>
                        <select name="mes" id="mes" required>
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $mes = str_pad($i, 2, '0', STR_PAD_LEFT);
                                echo "<option value='{$i}'>" . date('F', mktime(0, 0, 0, $i, 1)) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Total e Tipos -->
            <div class="form-section">
                <h3>Totais por Tipo</h3>
                <div class="form-row">
                    <div class="form-group col-3">
                        <label for="total_manifestacoes">Total de Manifestações</label>
                        <input type="number" name="total_manifestacoes" id="total_manifestacoes" required min="0">
                    </div>
                    <div class="form-group col-3">
                        <label for="reclamacoes">Reclamações</label>
                        <input type="number" name="reclamacoes" id="reclamacoes" required min="0">
                    </div>
                    <div class="form-group col-3">
                        <label for="denuncias">Denúncias</label>
                        <input type="number" name="denuncias" id="denuncias" required min="0">
                    </div>
                    <div class="form-group col-3">
                        <label for="sugestoes">Sugestões</label>
                        <input type="number" name="sugestoes" id="sugestoes" required min="0">
                    </div>
					<div class="form-group col-3">
                        <label for="elogios">Elogios</label>
                        <input type="number" name="elogios" id="elogios" required min="0">
                    </div>
					<div class="form-group col-3">
                        <label for="informacoes">Informações</label>
                        <input type="number" name="informacoes" id="informacoes" required min="0">
                    </div>
                </div>
                
            </div>

            <!-- Status -->
            <div class="form-section">
                <h3>Status</h3>
                <div class="form-row">
                    <div class="form-group col-3">
                        <label for="pendentes">Pendentes</label>
                        <input type="number" name="pendentes" id="pendentes" required min="0">
                    </div>
                    <div class="form-group col-3">
                        <label for="em_analise">Em Análise</label>
                        <input type="number" name="em_analise" id="em_analise" required min="0">
                    </div>
                    <div class="form-group col-3">
                        <label for="respondidas">Respondidas</label>
                        <input type="number" name="respondidas" id="respondidas" required min="0">
                    </div>
                    <div class="form-group col-3">
                        <label for="encerradas">Encerradas</label>
                        <input type="number" name="encerradas" id="encerradas" required min="0">
                    </div>
					<div class="form-group col-3">
                        <label for="indeferidas">Indeferidas</label>
                        <input type="number" name="indeferidas" id="indeferidas" required min="0">
                    </div>
                </div>
                
            </div>
			
            <!-- Identificação -->
            <div class="form-section">
                <h3>Identificação</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="identificadas">Identificadas</label>
                        <input type="number" name="identificadas" id="identificadas" required min="0">
                    </div>
                    <div class="form-group col-4">
                        <label for="anonimas">Anônimas</label>
                        <input type="number" name="anonimas" id="anonimas" required min="0">
                    </div>
                </div>
            </div>

            <!-- Forma de Resposta -->
            <div class="form-section">
                <h3>Forma de Recebimento de Resposta</h3>
                <div class="form-row">
                    <div class="form-group col-4">
                        <label for="resposta_sistema">Pelo Sistema</label>
                        <input type="number" name="resposta_sistema" id="resposta_sistema" required min="0">
                    </div>
                    <div class="form-group col-4">
                        <label for="resposta_presencial">Presencial</label>
                        <input type="number" name="resposta_presencial" id="resposta_presencial" required min="0">
                    </div>
                </div>
            </div>

            <!-- Observações -->
            <div class="form-section">
                <h3>Observações</h3>
                <div class="form-group">
                    <textarea name="observacoes" id="observacoes" rows="4"></textarea>
                </div>
            </div>

            <div class="form-submit">
                <button type="submit" name="adicionar_estatistica" class="button button-primary">
                    Registrar Estatísticas
                </button>
            </div>
        </form>
    </div>

    <!-- Lista de Registros -->
    <div class="estatisticas-lista">
        <h2>Registros Existentes</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Total</th>
                    <th>Identificadas/Anônimas</th>
                    <th>Status</th>
                    <th>Forma de Resposta</th>
                    <th>Registrado em</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($estatisticas)): foreach ($estatisticas as $estat): ?>
                    <tr>
                        <td><?php echo date('F/Y', mktime(0, 0, 0, $estat->mes, 1, $estat->ano)); ?></td>
                        <td><?php echo $estat->total_manifestacoes; ?></td>
                        <td><?php echo $estat->identificadas . '/' . $estat->anonimas; ?></td>
                        <td>
                            P: <?php echo $estat->pendentes; ?> |
                            A: <?php echo $estat->em_analise; ?> |
                            R: <?php echo $estat->respondidas; ?> |
                            E: <?php echo $estat->encerradas; ?> |
                            I: <?php echo $estat->indeferidas; ?>
                        </td>
                        <td>
                            Sistema: <?php echo $estat->resposta_sistema; ?> |
                            Presencial: <?php echo $estat->resposta_presencial; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($estat->data_registro)); ?></td>
                        <td>
                            <?php 
                            $user = get_userdata($estat->registrado_por);
                            echo $user ? $user->display_name : 'N/A';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.wrap {
    /* Remover max-width fixo */
    margin: 20px 0; /* Ajustar margem */
    padding: 0 20px; /* Adicionar padding lateral */
}

.estatisticas-form-container {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
    width: 100%; /* Garantir que ocupe toda a largura */
}

.form-row {
    display: flex;
    margin: 0 -10px;
    flex-wrap: wrap;
}

.form-group {
    margin-bottom: 15px;
    padding: 0 10px;
}

.col-3 {
    flex: 0 0 25%;
    max-width: 25%;
}

.col-4 {
    flex: 0 0 33.333333%;
    max-width: 33.333333%;
}

.col-6 {
    flex: 0 0 50%;
    max-width: 50%;
}

.form-section {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section h3 {
    margin-bottom: 15px;
    color: #23282d;
    font-size: 14px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    font-size: 13px;
}

.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

.form-submit {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.estatisticas-lista {
    margin-top: 30px;
}
	

@media (max-width: 768px) {
    .col-3, .col-4, .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    function validarTotais() {
        var total = parseInt($('#total_manifestacoes').val()) || 0;
        var soma = 0;
        
        // Soma por tipo
        soma = parseInt($('#reclamacoes').val() || 0) +
               parseInt($('#denuncias').val() || 0) +
               parseInt($('#sugestoes').val() || 0) +
               parseInt($('#elogios').val() || 0) +
               parseInt($('#informacoes').val() || 0);
               
        if (soma !== total) {
            alert('A soma dos tipos de manifestação deve ser igual ao total de manifestações!');
            return false;
        }
        
        // Soma por identificação
        soma = parseInt($('#identificadas').val() || 0) +
               parseInt($('#anonimas').val() || 0);
               
        if (soma !== total) {
            alert('A soma de manifestações identificadas e anônimas deve ser igual ao total!');
            return false;
        }
        
        // Soma por status
        soma = parseInt($('#pendentes').val() || 0) +
               parseInt($('#em_analise').val() || 0) +
               parseInt($('#respondidas').val() || 0) +
               parseInt($('#encerradas').val() || 0) +
               parseInt($('#indeferidas').val() || 0);
               
        if (soma !== total) {
            alert('A soma dos status deve ser igual ao total de manifestações!');
            return false;
        }
        
        // Soma por tipo de resposta
        soma = parseInt($('#resposta_sistema').val() || 0) +
               parseInt($('#resposta_presencial').val() || 0);
               
        if (soma !== total) {
            alert('A soma dos tipos de resposta deve ser igual ao total de manifestações!');
            return false;
        }
        
        return true;
    }

    $('.estatisticas-form').on('submit', function(e) {
        if (!validarTotais()) {
            e.preventDefault();
        }
    });
});
</script>