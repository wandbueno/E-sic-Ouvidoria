<?php if (!defined('ABSPATH')) exit; ?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .header h1 {
            color: #333;
            font-size: 16px;
            margin: 0 0 5px 0;
        }
        .header p {
            font-size: 12px;
            margin: 0;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h2 {
            color: #0073aa;
            font-size: 13px;
            margin: 0 0 10px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 5px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
            color: #333;
            width: 120px;
        }
        .mensagem-box {
            background: #f9f9f9;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #eee;
            font-size: 11px;
        }
        .resposta-item {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #eee;
        }
        .resposta-meta {
            font-size: 9px;
            color: #666;
            margin-bottom: 5px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ddd;
        }
        .resposta-content {
            font-size: 10px;
            line-height: 1.4;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .anexo-info {
            font-size: 9px;
            color: #666;
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Detalhes da Solicitação</h1>
        <p>Protocolo: <?php echo esc_html($solicitacao->protocolo); ?></p>
    </div>

    <div class="info-section">
        <h2>Informações Gerais</h2>
        <table>
            <tr>
                <th>Data</th>
				<td><?php echo wp_date('d/m/Y H:i', strtotime($solicitacao->data_criacao)); ?></td>
            </tr>
            <tr>
                <th>Status</th>
				<td><?php echo esc_html(ucfirst($solicitacao->status)); ?></td>
            </tr>
            <tr>
                <th>Tipo</th>
                <td><?php 
                    $tipos = array(
                        'reclamacao' => 'Reclamação',
                        'denuncia' => 'Denúncia',
                        'sugestao' => 'Sugestão',
                        'elogio' => 'Elogio',
                        'informacao' => 'Acesso à Informação'
                    );
                    echo isset($tipos[$solicitacao->tipo_manifestacao]) ? 
                        esc_html($tipos[$solicitacao->tipo_manifestacao]) : 
                        esc_html(ucfirst($solicitacao->tipo_manifestacao));
                ?></td>
            </tr>
            <tr>
                <th>Identificação</th>
                <td><?php echo $solicitacao->identificacao === 'identificado' ? 'Identificado' : 'Anônimo'; ?></td>
            </tr>
        </table>
    </div>

    <?php if ($solicitacao->identificacao === 'identificado'): ?>
    <div class="info-section">
        <h2>Dados do Solicitante</h2>
        <table>
            <tr>
                <th>Nome</th>
                <td><?php echo esc_html($solicitacao->nome); ?></td>
            </tr>
			<tr>
				<th>CPF/CNPJ</th>
				<td><?php echo esc_html($solicitacao->cpf_cnpj); ?></td>
			</tr>
            <?php if (!empty($solicitacao->email)): ?>
            <tr>
                <th>E-mail</th>
                <td><?php echo esc_html($solicitacao->email); ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($solicitacao->telefone)): ?>
            <tr>
                <th>Telefone</th>
                <td><?php echo esc_html($solicitacao->telefone); ?></td>
            </tr>
            <?php endif; ?>
			<tr>
				<th>Endereço</th>
				<td><?php echo nl2br(esc_html($solicitacao->endereco)); ?></td>
			</tr>
        </table>
    </div>
    <?php endif; ?>

    <div class="info-section">
        <h2>Mensagem</h2>
        <div class="mensagem-box">
            <?php echo nl2br(esc_html($solicitacao->mensagem)); ?>
            <?php if (!empty($solicitacao->arquivo)): ?>
                <div class="anexo-info">
                    <strong>Anexo:</strong> <?php echo esc_html(get_the_title($solicitacao->arquivo)); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php 
    $respostas = $this->database->get_respostas($solicitacao->id);
    if (!empty($respostas)): 
    ?>
    <div class="info-section">
        <h2>Respostas</h2>
        <?php foreach ($respostas as $resposta): ?>
            <div class="resposta-item">
                <div class="resposta-meta">
					<?php 
                    $user_info = get_userdata($resposta->respondido_por);
                    $nome_respondente = $user_info ? $user_info->display_name : 'Administrador';
                    ?>
                   <strong>Respondido por:</strong> <?php echo esc_html($nome_respondente); ?> |  
					<strong>Data:</strong> <?php echo wp_date('d/m/Y H:i', strtotime($resposta->data_resposta)); ?>
				</div>
                <div class="resposta-content">
                    <?php echo nl2br(wp_kses_post($resposta->resposta)); ?>
                    <?php if (!empty($resposta->arquivo)): ?>
                        <div class="anexo-info">
                            <strong>Anexo:</strong> <?php echo esc_html(get_the_title($resposta->arquivo)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Documento gerado em <?php echo wp_date('d/m/Y H:i:s'); ?> | <?php echo get_bloginfo('name'); ?> - Sistema de Ouvidoria / E-Sic</p>
    </div>
</body>
</html>