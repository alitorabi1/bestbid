<?php
    require ("fpdf/fpdf.php");
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont("Arial", "B", "20");
    $pdf->Cell(0, 10, "Best bid reports", 1, 1, "c");
    $pdf->SetFont("", "", "20");
    $pdf->Write(5, " hello this is your finance repor in PDF format.");
    $pdf->Output();
