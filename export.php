<?php
    require 'vendor/autoload.php';

    $dotenv = new Symfony\Component\Dotenv\Dotenv();
    $dotenv->load(__DIR__.'/.env');

	// Web scraping.
    $baseHeaders = array(
        "X-Obat-Session-Id" => $_ENV['X_Obat_Session_Id'],
        "Cookie" => $_ENV['Cookie'],
        "X-Requested-With" => $_ENV['X_Requested_With']
    );

    function getClient($headers)
    {
        return new \GuzzleHttp\Client([
            'base_uri' => 'https://www.obat.fr',
            'headers' => $headers
        ]);
    }

    function loadDocuments($baseHeaders, $path, $rowNumber = 1, $status = null) {
        $client = getClient(array_merge($baseHeaders, ['Content-Type' => 'application/x-www-form-urlencoded']));
        $params = [
            'length' => $rowNumber,
            'order[0][dir]' => 'asc',
            'order[0][column]' => 3,
            'start' => 0
        ];
        if ($status != null) {
            $params = array_merge($params, ['filters[status]' => $status]);
        }
        return $client->request('POST', $path, [
            'form_params' => $params
        ])->getBody();
    }

    function downloadDocument($baseHeaders, $id, $format = 'xlsx') {
        $client = getClient(array_merge($baseHeaders, ['Content-Type' => 'application/json']));
        $path = sprintf('/app/documents/export/%s?download=1&exportType=%s', $id, $format);
        return $client->request('POST', $path, [])->getBody();
    }

    function executeExport($baseHeaders, $fileName, $rowsResult, $format = 'xlsx') {
        $rowsResultParsed = json_decode($rowsResult);
        $rows = $rowsResultParsed->data;
        $rowCount = count($rows);

        foreach ($rows as $key => $row) {
            $fileId = $row->status->attr->data->uuid;
            $rowReferenceFieldExploded = explode('<', $row->reference->data);
            $rowReference = $rowReferenceFieldExploded[0];
            $rowReferenceFormatted = str_replace('/', '_', $rowReference);
            $rowReferenceFormatted = $rowReferenceFormatted ? $rowReferenceFormatted : $fileId;
            $now = new \Datetime();
            $nowFormatted = $now->format('d-m-Y');
            $rowName = sprintf('Export_%s_%s_%s_OBAT.%s', $fileName, $rowReferenceFormatted, $nowFormatted, $format);
            $index = ($key + 1);

            echo sprintf('%s / %s %s n°%s loaded (export id => %s, file => %s) ! <br/><br/>', $index, $rowCount, $fileName, $rowReference, $fileId, $rowName);

            $documentRequest = downloadDocument($baseHeaders, $fileId, $format);
            $contentAsString = $documentRequest ? $documentRequest->getContents() : null;
            if($contentAsString) {
                file_put_contents(sprintf('./exports/%s', $rowName), $contentAsString);
            }
        }
    }

    function executeQuotationExport($baseHeaders, $format = 'xlsx') {
        $mainResults = loadDocuments($baseHeaders, '/app/quotes/load', $_ENV['MaxRecord'], null);
        $cancelledResults = loadDocuments($baseHeaders, '/app/quotes/load', $_ENV['MaxRecord'], 'cancelled');
        executeExport($baseHeaders, 'Devis', $mainResults, $format);
        executeExport($baseHeaders, 'Devis', $cancelledResults, $format);
    }

    function executeInvoiceExport($baseHeaders, $format = 'xlsx') {
        $mainResults = loadDocuments($baseHeaders, '/app/invoices/load', $_ENV['MaxRecord']);
        $cancelledResults = loadDocuments($baseHeaders, '/app/invoices/load', $_ENV['MaxRecord'], 'cancelled');
        executeExport($baseHeaders, 'Facture', $mainResults, $format);
        executeExport($baseHeaders, 'Facture', $cancelledResults, $format);
    }

    function executeCreditExport($baseHeaders, $format = 'xlsx') {
        $mainResults = loadDocuments($baseHeaders, '/app/credits/load', $_ENV['MaxRecord']);
        $cancelledResults = loadDocuments($baseHeaders, '/app/credits/load', $_ENV['MaxRecord'], 'cancelled');
        executeExport($baseHeaders, 'Avoir', $mainResults, $format);
        executeExport($baseHeaders, 'Avoir', $cancelledResults, $format);
    }

    executeCreditExport($baseHeaders, 'xlsx');
    executeInvoiceExport($baseHeaders, 'xlsx');
    executeQuotationExport($baseHeaders, 'xlsx');

    echo '<br/>All exports are done !';

?>