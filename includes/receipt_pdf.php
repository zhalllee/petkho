<?php
require_once __DIR__ . '/fpdf/fpdf.php';

// FPDF's basic fonts do not understand UTF-8, so letters like "n with tilde" must be converted first.
function pdfText($text): string
{
    return iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$text);
}

/**
 * @param array $o      The order row (orderinfo_id, status, shipping, date_placed, fname, lname, addressline, town, zipcode, phone)
 * @param array $lines  The order lines. Each: ['name' => ..., 'quantity' => ..., 'price' => ...]
 * @param array $label  Status names shown to the customer
 * @return string       The PDF file data
 */
function buildReceiptPdf(array $o, array $lines, array $label): string
{
    $orderNo = str_pad($o['orderinfo_id'], 6, '0', STR_PAD_LEFT);

    $pdf = new FPDF();
    $pdf->AddPage();

    // --- title ---
    $pdf->SetFont('Helvetica', 'B', 18);
    $pdf->Cell(0, 10, 'Petkho Pet Supplies', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, 'Official Receipt', 0, 1, 'C');
    $pdf->Ln(6);

    // --- order and customer details ---
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 5, 'Receipt #: ' . $orderNo, 0, 1);
    $pdf->Cell(0, 5, 'Date: ' . date('F j, Y g:i A', strtotime($o['date_placed'])), 0, 1);
    $pdf->Cell(0, 5, 'Status: ' . ($label[$o['status']] ?? $o['status']), 0, 1);
    $pdf->Cell(0, 5, 'Customer: ' . pdfText($o['fname'] . ' ' . $o['lname']), 0, 1);
    $pdf->Cell(0, 5, 'Address: ' . pdfText($o['addressline'] . ', ' . $o['town'] . ' ' . $o['zipcode']), 0, 1);
    $pdf->Cell(0, 5, 'Phone: ' . pdfText($o['phone']), 0, 1);
    $pdf->Ln(6);

    // --- table header ---
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(90, 8, 'Item', 1, 0, 'L', true);
    $pdf->Cell(20, 8, 'Qty', 1, 0, 'R', true);
    $pdf->Cell(35, 8, 'Price', 1, 0, 'R', true);
    $pdf->Cell(35, 8, 'Subtotal', 1, 1, 'R', true);

    // --- one row per product ---
    // The peso sign is not in FPDF's basic fonts, so we write "PHP" instead.
    $pdf->SetFont('Helvetica', '', 10);
    $itemsSubtotal = 0;
    foreach ($lines as $l) {
        $sub = $l['price'] * $l['quantity'];     // calculated, not stored
        $itemsSubtotal += $sub;
        $pdf->Cell(90, 7, pdfText($l['name']), 1);
        $pdf->Cell(20, 7, $l['quantity'], 1, 0, 'R');
        $pdf->Cell(35, 7, 'PHP ' . number_format($l['price'], 2), 1, 0, 'R');
        $pdf->Cell(35, 7, 'PHP ' . number_format($sub, 2), 1, 1, 'R');
    }

    // --- totals ---
    $pdf->Cell(145, 7, 'Items subtotal', 0, 0, 'R');
    $pdf->Cell(35, 7, 'PHP ' . number_format($itemsSubtotal, 2), 0, 1, 'R');
    $pdf->Cell(145, 7, 'Shipping', 0, 0, 'R');
    $pdf->Cell(35, 7, 'PHP ' . number_format($o['shipping'], 2), 0, 1, 'R');
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(145, 8, 'Grand total', 0, 0, 'R');
    $pdf->Cell(35, 8, 'PHP ' . number_format($itemsSubtotal + $o['shipping'], 2), 0, 1, 'R');

    $pdf->Ln(8);
    $pdf->SetFont('Helvetica', 'I', 10);
    $pdf->Cell(0, 6, 'Thank you for shopping at Petkho!', 0, 1, 'C');

    return $pdf->Output('S');   // 'S' = give the PDF back as a string
}