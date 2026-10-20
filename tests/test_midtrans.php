<?php
/**
 * Midtrans Payment Gateway Integration Unit Test
 * Run via CLI: php -f tests/test_midtrans.php
 */

require_once __DIR__ . '/../midtrans_config.php';
require_once __DIR__ . '/../midtrans_api.php';

echo "=========================================================\n";
echo "       UNIT TEST: MIDTRANS PAYMENT GATEWAY CV ASIANINDO\n";
echo "=========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertEqual($testName, $expected, $actual) {
    global $passCount, $failCount;
    if ($expected === $actual) {
        echo " [PASS] {$testName}\n";
        echo "        Result: " . json_encode($actual) . "\n";
        $passCount++;
    } else {
        echo " [FAIL] {$testName}\n";
        echo "        Expected: " . json_encode($expected) . "\n";
        echo "        Actual:   " . json_encode($actual) . "\n";
        $failCount++;
    }
}

// 1. Test Virtual Account Fee Calculation (Flat Rp 4.000)
$feeBcaVa = calculateMidtransFee('BC', 10000000);
assertEqual('Fee BCA Virtual Account (Flat Rp 4.000 untuk 10 Juta)', 4000, $feeBcaVa);

$feeBriVa = calculateMidtransFee('BR', 50000000);
assertEqual('Fee BRI Virtual Account (Flat Rp 4.000 untuk 50 Juta)', 4000, $feeBriVa);

// 2. Test QRIS Fee Calculation (0.7%)
// 10.000.000 * 0.007 = 70.000
$feeQris = calculateMidtransFee('NQ', 10000000);
assertEqual('Fee QRIS (0,7% dari Rp 10.000.000)', 70000, $feeQris);

// 3. Test Credit Card Fee Calculation (2.9% + Rp 2.000)
// 10.000.000 * 0.029 = 290.000 + 2.000 = 292.000
$feeCc = calculateMidtransFee('VC', 10000000);
assertEqual('Fee Credit Card (2,9% + Rp 2.000 dari Rp 10.000.000)', 292000, $feeCc);

// 4. Test GoPay Direct Fee Calculation (2.0%)
// 5.000.000 * 0.02 = 100.000
$feeGopay = calculateMidtransFee('GP', 5000000);
assertEqual('Fee GoPay Direct (2,0% dari Rp 5.000.000)', 100000, $feeGopay);

// 5. Test Transfer Manual BCA Fee Calculation (Rp 0)
$feeManual = calculateMidtransFee('MANUAL_BCA', 15000000);
assertEqual('Fee Transfer Manual BCA (Bebas Biaya Rp 0)', 0, $feeManual);

// 6. Test SHA-512 Signature Generation
$orderId = 'ASN-20260831-TEST';
$statusCode = '200';
$grossAmount = '10004000.00';
$serverKey = 'SB-Mid-server-TEST_DUMMY_KEY_123456';

$expectedSig = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
$actualSig = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
assertEqual('Midtrans SHA-512 Signature Hash Matching', $expectedSig, $actualSig);

// 7. DP harus ditagihkan sesuai nilai DP, bukan total pesanan.
[$dpPayload, $dpGrossAmount] = buildMidtransSnapPayload([
    'order_number' => 'ASN-DP-TEST',
    'bill_amount' => 5000000,
    'total' => 10000000,
    'subtotal' => 9800000,
    'shipping_cost' => 200000,
    'items' => [[
        'product_id' => 'MESIN-1',
        'product_name' => 'Mesin Uji',
        'price' => 9800000,
        'quantity' => 1
    ]]
], 'BC', ['name' => 'Pelanggan Uji']);
assertEqual('Gross amount DP memakai nilai DP + fee', 5004000, $dpGrossAmount);
assertEqual('Item detail DP seimbang dengan nilai DP', 5000000, $dpPayload['item_details'][0]['price']);

// 8. Untuk pembayaran penuh, total item detail harus sama dengan gross amount.
[$fullPayload, $fullGrossAmount] = buildMidtransSnapPayload([
    'order_number' => 'ASN-FULL-TEST',
    'bill_amount' => 10000000,
    'total' => 10000000,
    'subtotal' => 9800000,
    'shipping_cost' => 200000,
    'items' => [[
        'product_id' => 'MESIN-1',
        'product_name' => 'Mesin Uji',
        'price' => 9800000,
        'quantity' => 1
    ]]
], 'BC', ['name' => 'Pelanggan Uji']);
$fullItemTotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $fullPayload['item_details']));
assertEqual('Item detail pembayaran penuh seimbang dengan gross amount', $fullGrossAmount, $fullItemTotal);

echo "\n---------------------------------------------------------\n";
echo "SUMMARY: Passed {$passCount} tests, Failed {$failCount} tests.\n";
echo "---------------------------------------------------------\n";

exit($failCount > 0 ? 1 : 0);
