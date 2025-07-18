<?php

namespace App\Libraries;

use Knp\Snappy\Pdf;

class PdfGenerator
{
    protected $pdf;

    public function __construct()
    {
       $this->pdf = new Pdf('D:\wkhtmltopdf\bin\wkhtmltopdf.exe'); // sesuaikan path
    }

    public function generate($html, $filename = 'file.pdf')
    {
        return $this->pdf->getOutputFromHtml($html);
    }
}
