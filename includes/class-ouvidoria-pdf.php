<?php
if (!defined('ABSPATH')) {
    exit;
}

class Ouvidoria_PDF {
    private $database;

    public function __construct($database) {
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

        // Gerar HTML do PDF usando o template compartilhado
        ob_start();
        include OUVIDORIA_PLUGIN_DIR . 'public/views/pdf-template.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'solicitacao-' . $solicitacao->protocolo . '.pdf';
        $dompdf->stream($filename, array('Attachment' => $download));
        exit;
    }
}