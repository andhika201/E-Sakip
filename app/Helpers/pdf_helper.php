<?php

use Knp\Snappy\Pdf;

function getPdfInstance()
{
    return new Pdf('D:/wkhtmltopdf/bin/wkhtmltopdf.exe');
}
