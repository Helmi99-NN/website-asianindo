<?php
/**
 * Midtrans Payment Gateway Configuration
 * CV Asianindo E-Commerce
 * 
 * Dokumentasi API Midtrans: https://docs.midtrans.com
 */

if (file_exists(__DIR__ . '/midtrans_custom_config.php')) {
    require_once __DIR__ . '/midtrans_custom_config.php';
}

// 1. Kredensial Akun Midtrans (Dapat diubah via Panel Admin atau file ini)
if (!defined('MIDTRANS_SERVER_KEY')) {
    // Default Sandbox Server Key untuk testing (Ganti dengan Server Key dari dashboard.midtrans.com saat Go Live)
    define('MIDTRANS_SERVER_KEY', getenv('MIDTRANS_SERVER_KEY') ?: 'SB-Mid-server-TEST_DUMMY_KEY_123456');
}

if (!defined('MIDTRANS_CLIENT_KEY')) {
    // Default Sandbox Client Key untuk testing (Ganti dengan Client Key dari dashboard.midtrans.com saat Go Live)
    define('MIDTRANS_CLIENT_KEY', getenv('MIDTRANS_CLIENT_KEY') ?: 'SB-Mid-client-TEST_DUMMY_KEY_123456');
}

// 2. Mode Lingkungan (sandbox / production)
if (!defined('MIDTRANS_ENVIRONMENT')) {
    define('MIDTRANS_ENVIRONMENT', getenv('MIDTRANS_ENV') ?: 'sandbox');
}

// 3. URL Endpoint Midtrans
if (MIDTRANS_ENVIRONMENT === 'production') {
    if (!defined('MIDTRANS_SNAP_URL')) define('MIDTRANS_SNAP_URL', 'https://app.midtrans.com/snap/v1/transactions');
    if (!defined('MIDTRANS_CORE_URL')) define('MIDTRANS_CORE_URL', 'https://api.midtrans.com/v2');
    if (!defined('MIDTRANS_SNAP_JS')) define('MIDTRANS_SNAP_JS', 'https://app.midtrans.com/snap/snap.js');
} else {
    if (!defined('MIDTRANS_SNAP_URL')) define('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions');
    if (!defined('MIDTRANS_CORE_URL')) define('MIDTRANS_CORE_URL', 'https://api.sandbox.midtrans.com/v2');
    if (!defined('MIDTRANS_SNAP_JS')) define('MIDTRANS_SNAP_JS', 'https://app.sandbox.midtrans.com/snap/snap.js');
}

// 4. Callback / Finish URL
$siteBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'asianindomachine.com');
if (!defined('MIDTRANS_FINISH_URL')) {
    define('MIDTRANS_FINISH_URL', $siteBaseUrl . '/pembayaran.html');
}
if (!defined('MIDTRANS_NOTIFICATION_URL')) {
    define('MIDTRANS_NOTIFICATION_URL', $siteBaseUrl . '/midtrans_callback.php');
}

/**
 * Daftar Metode Pembayaran Midtrans & Formula Biaya Layanan (Fee Platform)
 * Biaya fee ini dibebankan ke pembeli (ditambahkan ke total tagihan).
 */
function getMidtransPaymentChannels() {
    return [
        // ================= VIRTUAL ACCOUNT (FLAT FEE RP 4.000) =================
        'BC' => [
            'code' => 'BC',
            'midtrans_type' => 'bca_va',
            'name' => 'BCA Virtual Account',
            'category' => 'va',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg',
            'fee_type' => 'flat',
            'fee_value' => 4000,
            'description' => 'Verifikasi Otomatis 24 Jam via BCA Mobile / ATM BCA',
            'badge' => 'Otomatis'
        ],
        'M2' => [
            'code' => 'M2',
            'midtrans_type' => 'echannel', // Mandiri Bill / VA
            'name' => 'Mandiri Virtual Account (Bill)',
            'category' => 'va',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg',
            'fee_type' => 'flat',
            'fee_value' => 4000,
            'description' => 'Verifikasi Otomatis 24 Jam via Livin\' Mandiri / Mandiri ATM',
            'badge' => 'Otomatis'
        ],
        'BR' => [
            'code' => 'BR',
            'midtrans_type' => 'bri_va',
            'name' => 'BRI Virtual Account (BRIVA)',
            'category' => 'va',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 4000,
            'description' => 'Verifikasi Otomatis 24 Jam via BRImo / ATM BRI',
            'badge' => 'Otomatis'
        ],
        'B1' => [
            'code' => 'B1',
            'midtrans_type' => 'bni_va',
            'name' => 'BNI Virtual Account',
            'category' => 'va',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/f/f0/BNI_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 4000,
            'description' => 'Verifikasi Otomatis 24 Jam via BNI Mobile Banking / ATM BNI',
            'badge' => 'Otomatis'
        ],
        'BT' => [
            'code' => 'BT',
            'midtrans_type' => 'permata_va',
            'name' => 'Permata Virtual Account',
            'category' => 'va',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/7/7b/PermataBank_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 4000,
            'description' => 'Verifikasi Otomatis 24 Jam via PermataMobile / ATM Permata',
            'badge' => 'Otomatis'
        ],
        'C1' => [
            'code' => 'C1',
            'midtrans_type' => 'cimb_va',
            'name' => 'CIMB Niaga Virtual Account',
            'category' => 'va',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/1/15/CIMB_Niaga_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 4000,
            'description' => 'Verifikasi Otomatis 24 Jam via OCTO Mobile / ATM CIMB',
            'badge' => 'Otomatis'
        ],

        // ================= QRIS (0.7% PERCENTAGE) =================
        'NQ' => [
            'code' => 'NQ',
            'midtrans_type' => 'qris',
            'name' => 'QRIS (BCA Mobile, GoPay, OVO, DANA, ShopeePay)',
            'category' => 'qris',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg',
            'fee_type' => 'percent',
            'fee_value' => 0.007, // 0.7%
            'description' => 'Scan QR Code menggunakan aplikasi e-wallet / m-banking favorit Anda',
            'badge' => 'Instant Scan'
        ],

        // ================= KARTU KREDIT / DEBIT ONLINE (2.9% + RP 2.000) =================
        'VC' => [
            'code' => 'VC',
            'midtrans_type' => 'credit_card',
            'name' => 'Kartu Kredit / Debit Online (Visa, Mastercard, JCB)',
            'category' => 'cc',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/d/d6/Visa_2021.svg',
            'fee_type' => 'combo',
            'fee_value' => 0.029, // 2.9%
            'fee_flat' => 2000,   // + Rp 2.000
            'description' => 'Pembayaran aman 3D Secure dengan Kartu Kredit / Debit berlogo Visa/Mastercard/JCB',
            'badge' => 'Cicilan / Full'
        ],

        // ================= E-WALLET DIRECT =================
        'GP' => [
            'code' => 'GP',
            'midtrans_type' => 'gopay',
            'name' => 'GoPay / GoPay Later',
            'category' => 'ewallet',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/8/86/Gopay_logo.svg',
            'fee_type' => 'percent',
            'fee_value' => 0.020, // 2.0%
            'description' => 'Bayar langsung via aplikasi Gojek / GoPay',
            'badge' => 'E-Wallet'
        ],
        'SP' => [
            'code' => 'SP',
            'midtrans_type' => 'shopeepay',
            'name' => 'ShopeePay',
            'category' => 'ewallet',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/0/0e/Shopee_logo.svg',
            'fee_type' => 'percent',
            'fee_value' => 0.020, // 2.0%
            'description' => 'Bayar langsung via aplikasi Shopee',
            'badge' => 'E-Wallet'
        ],

        // ================= TRANSFER MANUAL REKENING BCA =================
        'MANUAL_BCA' => [
            'code' => 'MANUAL_BCA',
            'midtrans_type' => 'manual',
            'name' => 'Transfer Manual Bank BCA (Bebas Biaya)',
            'category' => 'manual',
            'icon' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg',
            'fee_type' => 'flat',
            'fee_value' => 0,
            'description' => 'Transfer manual ke Rekening Resmi BCA CV Asianindo (6670747997 a/n Iman Anjani Buchory)',
            'badge' => 'Bebas Fee'
        ]
    ];
}

/**
 * Menghitung Biaya Layanan Pembayaran (Platform Fee)
 * 
 * @param string $channelCode Kode channel (misal: 'BC', 'NQ', 'VC', 'MANUAL_BCA')
 * @param int|float $amount Jumlah nilai transaksi dasar (Produk + Ongkir)
 * @return int Nilai fee dalam Rupiah (dibulatkan)
 */
function calculateMidtransFee($channelCode, $amount) {
    $channels = getMidtransPaymentChannels();
    
    if (!isset($channels[$channelCode])) {
        return 0; // Default Rp 0 jika manual atau channel tidak dikenal
    }

    $ch = $channels[$channelCode];
    $fee = 0;

    if ($ch['fee_type'] === 'flat') {
        $fee = (int)$ch['fee_value'];
    } elseif ($ch['fee_type'] === 'percent') {
        $fee = (int)round($amount * (float)$ch['fee_value']);
    } elseif ($ch['fee_type'] === 'combo') {
        $percentFee = round($amount * (float)$ch['fee_value']);
        $fee = (int)($percentFee + ($ch['fee_flat'] ?? 0));
    }

    return max(0, $fee);
}
