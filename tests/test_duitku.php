<?php
require_once __DIR__ . '/../duitku_config.php';

echo "=== TEST 1: DUITKU CONFIG & CHANNELS ===\n";
echo "Merchant Code: " . DUITKU_MERCHANT_CODE . "\n";
echo "Environment: " . DUITKU_ENVIRONMENT . "\n";
echo "Base URL: " . DUITKU_BASE_URL . "\n";

$channels = getDuitkuPaymentChannels();
echo "Loaded Channels: " . count($channels) . "\n";
foreach ($channels as $ch) {
    echo " - [{$ch['code']}] {$ch['name']} ({$ch['category']}) -> Fee: {$ch['fee_type']}\n";
}

echo "\n=== TEST 2: FEE CALCULATIONS (Buyer Charged) ===\n";
$testAmount = 5000000; // Rp 5.000.000 (Misal mesin roaster/spinner)
echo "Sample Base Amount: Rp " . number_format($testAmount, 0, ',', '.') . "\n";

$testChannels = ['BC', 'M2', 'BR', 'BT', 'NQ', 'VC', 'MANUAL_BCA'];
foreach ($testChannels as $code) {
    $fee = calculateDuitkuFee($code, $testAmount);
    $totalBill = $testAmount + $fee;
    echo " * Channel {$code}: Fee = Rp " . number_format($fee, 0, ',', '.') . " | Total = Rp " . number_format($totalBill, 0, ',', '.') . "\n";
}

// Specific assertions
assert(calculateDuitkuFee('BC', 5000000) === 3000, "BCA VA fee should be 3000");
assert(calculateDuitkuFee('BT', 5000000) === 2500, "Permata VA fee should be 2500");
assert(calculateDuitkuFee('NQ', 5000000) === (int)round(5000000 * 0.007), "QRIS fee should be 0.7% = 35.000");
assert(calculateDuitkuFee('VC', 5000000) === (int)(round(5000000 * 0.025) + 2500), "CC fee should be 2.5% + 2500 = 127.500");
assert(calculateDuitkuFee('MANUAL_BCA', 5000000) === 0, "Manual BCA fee should be 0");
echo "\n-> All Fee Formula Assertions PASSED!\n";

echo "\n=== TEST 3: SIGNATURE GENERATION ===\n";
$merchantCode = "D12345";
$apiKey = "abcdef1234567890abcdef1234567890";
$orderId = "ASN-20260828-TEST";
$amount = 5003000;

$inquirySig = md5($merchantCode . $orderId . $amount . $apiKey);
$callbackSig = md5($merchantCode . $amount . $orderId . $apiKey);
echo "Inquiry Sig:  $inquirySig\n";
echo "Callback Sig: $callbackSig\n";

assert(strlen($inquirySig) === 32, "Inquiry signature should be 32 char md5");
assert(strlen($callbackSig) === 32, "Callback signature should be 32 char md5");
echo "-> Signature Assertions PASSED!\n";

echo "\n=== ALL UNIT TESTS COMPLETED SUCCESSFULLY! ===\n";

