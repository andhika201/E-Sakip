<?php

namespace App\Libraries;

use TCPDF;

class PdfTcpdf extends TCPDF
{
    public function Header()
    {
        // KOSONG
        // Jangan taruh logo / judul di sini
    }

    public function Footer()
    {
        $this->SetY(-20);
        $this->SetFont('times', 'I', 9);

        $this->Cell(0, 5, '© Kabupaten Pringsewu', 0, 0, 'L');
        $this->Cell(0, 5, $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 1, 'R');

        $this->SetFont('times', 'I', 8);
        $this->Cell(
            0,
            5,
            strtoupper($this->opd ?? '') . ' / ' . date('Y') . ' – Print by Aksara',
            0,
            0,
            'L'
        );
    }

    // Optional helper (aman)
    public function setOpd(string $opd)
    {
        $this->opd = $opd;
    }
}
