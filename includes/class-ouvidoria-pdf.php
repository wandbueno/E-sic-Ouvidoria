<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_PDF {
    private $database;

    public function __construct($database = null) {
        $this->database = $database;
    }

    public function gerar_pdf($solicitacao, $download = false) {
        if (!$solicitacao) {
            throw new Exception('Solicitação não encontrada');
        }

        // Carregar DomPDF
        require_once OUVIDORIA_PLUGIN_DIR . 'vendor/autoload.php';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
		
		// Buscar respostas
        $respostas = $this->database->get_respostas($solicitacao->id);

        // Gerar HTML do PDF usando o template compartilhado
        ob_start();
        include OUVIDORIA_PLUGIN_DIR . 'public/views/pdf-template.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'solicitacao-' . $solicitacao->protocolo . '.pdf';
        
        // Configurar headers antes de qualquer saída
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Cache-Control: max-age=0');
        
        $dompdf->stream($filename, array('Attachment' => true));
        exit;
    }
    
    /**
     * Gera um PDF com as estatísticas da Ouvidoria
     * 
     * @param array $dados Dados a serem exibidos no PDF
     * @param string $filename Nome do arquivo a ser baixado
     * @return void
     */
    public function gerar_estatisticas_pdf($dados, $filename) {
        // Carregar DomPDF
        if (!defined('OUVIDORIA_PLUGIN_DIR')) {
            define('OUVIDORIA_PLUGIN_DIR', plugin_dir_path(dirname(__FILE__, 2)));
        }
        
        require_once OUVIDORIA_PLUGIN_DIR . 'vendor/autoload.php';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        
        // Obter nome do órgão das opções do sistema
        $config = get_option('ouvidoria_options', array());
        $nome_orgao = isset($config['nome_orgao']) ? $config['nome_orgao'] : 'Prefeitura Municipal';
        
        // Começar a construir o HTML
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 12px;
                    line-height: 1.5;
                }
                h1 {
                    font-size: 16px;
                    text-align: center;
                    margin-bottom: 5px;
                }
                h2 {
                    font-size: 14px;
                    text-align: center;
                    margin-top: 5px;
                    margin-bottom: 20px;
                }
                .data-geracao {
                    text-align: center;
                    margin-bottom: 20px;
                    font-size: 11px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                table, th, td {
                    border: 1px solid #ddd;
                }
                th {
                    background-color: #f2f2f2;
                    padding: 8px;
                    text-align: left;
                }
                td {
                    padding: 8px;
                }
                .section-title {
                    font-weight: bold;
                    margin-top: 15px;
                    margin-bottom: 10px;
                    font-size: 13px;
                }
                .page-break {
                    page-break-after: always;
                }
            </style>
        </head>
        <body>
            <h1>' . esc_html($nome_orgao) . '</h1>
            <h2>' . esc_html($dados['Título']) . '</h2>
            <div class="data-geracao">Data de geração: ' . esc_html($dados['Data de Geração']) . '</div>
            
            <h2>Resumo Geral</h2>
            <table>
                <tr>
                    <th>Indicador</th>
                    <th>Quantidade</th>
                </tr>';
        
        foreach ($dados['Resumo'] as $label => $valor) {
            $html .= '<tr>
                <td>' . esc_html($label) . '</td>
                <td>' . esc_html($valor) . '</td>
            </tr>';
        }
        
        $html .= '</table>
            
            <h2>Por Tipo de Manifestação</h2>
            <table>
                <tr>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                </tr>';
        
        foreach ($dados['Por Tipo'] as $tipo => $valor) {
            $html .= '<tr>
                <td>' . esc_html($tipo) . '</td>
                <td>' . esc_html($valor) . '</td>
            </tr>';
        }
        
        $html .= '</table>
            
            <h2>Por Tipo de Identificação</h2>
            <table>
                <tr>
                    <th>Identificação</th>
                    <th>Quantidade</th>
                </tr>';
        
        foreach ($dados['Por Identificação'] as $tipo => $valor) {
            $html .= '<tr>
                <td>' . esc_html($tipo) . '</td>
                <td>' . esc_html($valor) . '</td>
            </tr>';
        }
        
        $html .= '</table>
            
            <h2>Por Forma de Recebimento</h2>
            <table>
                <tr>
                    <th>Forma</th>
                    <th>Quantidade</th>
                </tr>';
        
        foreach ($dados['Por Forma de Recebimento'] as $tipo => $valor) {
            $html .= '<tr>
                <td>' . esc_html($tipo) . '</td>
                <td>' . esc_html($valor) . '</td>
            </tr>';
        }
        
        $html .= '</table>
            
            <h2>Por Mês</h2>
            <table>
                <tr>
                    <th>Mês</th>
                    <th>Quantidade</th>
                </tr>';
        
        foreach ($dados['Por Mês'] as $mes => $valor) {
            $html .= '<tr>
                <td>' . esc_html($mes) . '</td>
                <td>' . esc_html($valor) . '</td>
            </tr>';
        }
        
        $html .= '</table>
            
            <div class="footer">
                Relatório gerado automaticamente pelo Sistema de Ouvidoria / E-Sic
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Configurar headers antes de qualquer saída
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        header('Cache-Control: max-age=0');
        
        $dompdf->stream($filename . '.pdf', array('Attachment' => true));
    }
}