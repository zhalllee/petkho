<?php
// Builds the HTML body of the "order status updated" email.
// It only creates text. Sending is done by sendMail() in mailer.php.

/**
 * @param array $o      The order row (needs orderinfo_id, status, shipping, total, fname, lname, date_placed)
 * @param array $lines  The order lines. Each: ['name' => ..., 'quantity' => ..., 'price' => ...]
 * @param array $label  Status names shown to the customer, e.g. ['Processing' => 'To Ship', ...]
 */
function buildOrderEmail(array $o, array $lines, array $label): string
{
    $orderNo = str_pad($o['orderinfo_id'], 6, '0', STR_PAD_LEFT);
    $status  = $label[$o['status']] ?? $o['status'];

    // one table row per product
    $rows = '';
    $itemsSubtotal = 0;
    foreach ($lines as $l) {
        $sub = $l['price'] * $l['quantity'];     // subtotal is calculated here, not stored
        $itemsSubtotal += $sub;
        $rows .= '<tr>'
            . '<td style="padding:6px;border:1px solid #ddd">' . htmlspecialchars($l['name']) . '</td>'
            . '<td style="padding:6px;border:1px solid #ddd;text-align:right">' . $l['quantity'] . '</td>'
            . '<td style="padding:6px;border:1px solid #ddd;text-align:right">&#8369;' . number_format($l['price'], 2) . '</td>'
            . '<td style="padding:6px;border:1px solid #ddd;text-align:right">&#8369;' . number_format($sub, 2) . '</td>'
            . '</tr>';
    }

    $grandTotal = $itemsSubtotal + $o['shipping'];

    return '
    <div style="font-family:Arial,sans-serif;max-width:600px">
      <h2>Petkho Pet Supplies</h2>
      <p>Hi ' . htmlspecialchars($o['fname']) . ',</p>
      <p>The status of your order <strong>#' . $orderNo . '</strong> is now
         <strong>' . htmlspecialchars($status) . '</strong>.</p>

      <table style="border-collapse:collapse;width:100%">
        <tr style="background:#f3f3f3">
          <th style="padding:6px;border:1px solid #ddd;text-align:left">Product</th>
          <th style="padding:6px;border:1px solid #ddd;text-align:right">Qty</th>
          <th style="padding:6px;border:1px solid #ddd;text-align:right">Price</th>
          <th style="padding:6px;border:1px solid #ddd;text-align:right">Subtotal</th>
        </tr>
        ' . $rows . '
        <tr><td colspan="3" style="padding:6px;text-align:right">Items subtotal</td>
            <td style="padding:6px;text-align:right">&#8369;' . number_format($itemsSubtotal, 2) . '</td></tr>
        <tr><td colspan="3" style="padding:6px;text-align:right">Shipping</td>
            <td style="padding:6px;text-align:right">&#8369;' . number_format($o['shipping'], 2) . '</td></tr>
        <tr><td colspan="3" style="padding:6px;text-align:right"><strong>Grand total</strong></td>
            <td style="padding:6px;text-align:right"><strong>&#8369;' . number_format($grandTotal, 2) . '</strong></td></tr>
      </table>

      <p>Thank you for shopping at Petkho!</p>
    </div>';
}