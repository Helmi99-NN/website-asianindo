<?php
/**
 * Duitku Payment Gateway Configuration
 * CV Asianindo E-Commerce
 * 
 * Dokumentasi API Duitku: https://docs.duitku.com
 */

// Cek jika ada custom config di level server / hosting agar tidak tertimpa
if (file_exists(__DIR__ . '/duitku_custom_config.php')) {
    require_once __DIR__ . '/duitku_custom_config.php';
}

// 1. Kredensial Akun Duitku (Dapat diubah via Panel Admin atau file ini)
if (!defined('DUITKU_MERCHANT_CODE')) {
    // Default Sandbox Merchant Code untuk testing (Ganti dengan Merchant Code asli dari merchant.duitku.com saat Go Live)
    define('DUITKU_MERCHANT_CODE', getenv('DUITKU_MERCHANT_CODE') ?: 'D12345');
}

if (!defined('DUITKU_API_KEY')) {
    // Default Sandbox API Key untuk testing (Ganti dengan API Key asli dari merchant.duitku.com saat Go Live)
    define('DUITKU_API_KEY', getenv('DUITKU_API_KEY') ?: 'd8a8e5200c6d7a485fa7c85892558661');
}

// 2. Mode Lingkungan (sandbox / production)
if (!defined('DUITKU_ENVIRONMENT')) {
    define('DUITKU_ENVIRONMENT', getenv('DUITKU_ENV') ?: 'sandbox');
}

// 3. URL Endpoint Duitku
if (DUITKU_ENVIRONMENT === 'production') {
    if (!defined('DUITKU_BASE_URL')) define('DUITKU_BASE_URL', 'https://passport.duitku.com/webapi');
    if (!defined('DUITKU_POP_JS')) define('DUITKU_POP_JS', 'https://app-prod.duitku.com/lib/js/duitku.js');
} else {
    if (!defined('DUITKU_BASE_URL')) define('DUITKU_BASE_URL', 'https://sandbox.duitku.com/webapi');
    if (!defined('DUITKU_POP_JS')) define('DUITKU_POP_JS', 'https://app-sandbox.duitku.com/lib/js/duitku.js');
}

// 4. Callback & Return URL
$siteBaseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'asianindomachine.com');
if (!defined('DUITKU_CALLBACK_URL')) {
    define('DUITKU_CALLBACK_URL', $siteBaseUrl . '/duitku_callback.php');
}
if (!defined('DUITKU_RETURN_URL')) {
    define('DUITKU_RETURN_URL', $siteBaseUrl . '/pembayaran.html');
}

// 5. Waktu Kedaluwarsa Pembayaran (Expiry Time dalam menit, default 1440 menit = 24 jam)
if (!defined('DUITKU_EXPIRY_PERIOD')) {
    define('DUITKU_EXPIRY_PERIOD', 1440);
}

/**
 * Daftar Metode Pembayaran Duitku & Formula Biaya Layanan (Fee)
 * Fee ini ditambahkan ke tagihan pembeli.
 */
function getDuitkuPaymentChannels() {
    return [
        // ================= VIRTUAL ACCOUNT (FLAT FEE) =================
        'BC' => [
            'code' => 'BC',
            'name' => 'BCA Virtual Account',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg',
            'fee_type' => 'flat',
            'fee_value' => 3000,
            'fee_label' => 'Rp 3.000',
            'description' => 'Verifikasi instan otomatis 24 jam',
            'is_active' => true
        ],
        'M2' => [
            'code' => 'M2',
            'name' => 'Mandiri Virtual Account',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg',
            'fee_type' => 'flat',
            'fee_value' => 3000,
            'fee_label' => 'Rp 3.000',
            'description' => 'Verifikasi instan otomatis 24 jam (Livin / ATM)',
            'is_active' => true
        ],
        'BR' => [
            'code' => 'BR',
            'name' => 'BRI Virtual Account (BRIVA)',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 3000,
            'fee_label' => 'Rp 3.000',
            'description' => 'Verifikasi instan otomatis (BRImo / ATM)',
            'is_active' => true
        ],
        'B1' => [
            'code' => 'B1',
            'name' => 'BNI Virtual Account',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/f/f0/BNI_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 3000,
            'fee_label' => 'Rp 3.000',
            'description' => 'Verifikasi instan otomatis (BNI Mobile / ATM)',
            'is_active' => true
        ],
        'BT' => [
            'code' => 'BT',
            'name' => 'Permata Virtual Account',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/7/7b/PermataBank_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 2500,
            'fee_label' => 'Rp 2.500',
            'description' => 'Verifikasi instan otomatis dari seluruh bank via ALTO/ATM Bersama',
            'is_active' => true
        ],
        'BSI' => [
            'code' => 'BSI',
            'name' => 'BSI Virtual Account',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a0/Bank_Syariah_Indonesia.svg',
            'fee_type' => 'flat',
            'fee_value' => 3000,
            'fee_label' => 'Rp 3.000',
            'description' => 'Bank Syariah Indonesia (BSI Mobile / ATM)',
            'is_active' => true
        ],
        'C1' => [
            'code' => 'C1',
            'name' => 'CIMB Niaga Virtual Account',
            'category' => 'va',
            'category_name' => 'Virtual Account Bank',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/15/CIMB_Niaga_logo.svg',
            'fee_type' => 'flat',
            'fee_value' => 3000,
            'fee_label' => 'Rp 3.000',
            'description' => 'OCTO Mobile / CIMB Clicks / ATM',
            'is_active' => true
        ],

        // ================= QRIS (PERCENTAGE FEE) =================
        'NQ' => [
            'code' => 'NQ',
            'name' => 'QRIS (Semua E-Wallet & M-Banking)',
            'category' => 'qris',
            'category_name' => 'Instant QR Code',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/a/a2/Logo_QRIS.svg',
            'fee_type' => 'percent',
            'fee_percent' => 0.7, // 0.7%
            'fee_fixed' => 0,
            'fee_label' => '0,7%',
            'description' => 'Scan langsung via BCA Mobile, GoPay, OVO, Dana, ShopeePay (Max Rp 10 Juta)',
            'is_active' => true
        ],

        // ================= KARTU KREDIT =================
        'VC' => [
            'code' => 'VC',
            'name' => 'Kartu Kredit / Debit Online',
            'category' => 'cc',
            'category_name' => 'Kartu Kredit (Visa / Mastercard / JCB)',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/d/d6/Visa_2021.svg',
            'fee_type' => 'combo',
            'fee_percent' => 2.5, // 2.5%
            'fee_fixed' => 2500, // + Rp 2.500
            'fee_label' => '2,5% + Rp 2.500',
            'description' => 'Visa, Mastercard, JCB dengan 3D Secure OTP',
            'is_active' => true
        ],

        // ================= E-WALLET =================
        'OV' => [
            'code' => 'OV',
            'name' => 'OVO',
            'category' => 'ewallet',
            'category_name' => 'E-Wallet',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/e/eb/Logo_ovo_purple.svg',
            'fee_type' => 'percent',
            'fee_percent' => 1.8,
            'fee_fixed' => 0,
            'fee_label' => '1,8%',
            'description' => 'Pembayaran instan langsung dari aplikasi OVO',
            'is_active' => true
        ],
        'DA' => [
            'code' => 'DA',
            'name' => 'DANA',
            'category' => 'ewallet',
            'category_name' => 'E-Wallet',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/7/72/Logo_dana_blue.svg',
            'fee_type' => 'percent',
            'fee_percent' => 1.8,
            'fee_fixed' => 0,
            'fee_label' => '1,8%',
            'description' => 'Pembayaran instan langsung dari saldo DANA',
            'is_active' => true
        ],
        'SP' => [
            'code' => 'SP',
            'name' => 'ShopeePay',
            'category' => 'ewallet',
            'category_name' => 'E-Wallet',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/0e/Shopee_logo.svg',
            'fee_type' => 'percent',
            'fee_percent' => 1.8,
            'fee_fixed' => 0,
            'fee_label' => '1,8%',
            'description' => 'Pembayaran instan langsung dari ShopeePay',
            'is_active' => true
        ],

        // ================= TRANSFER MANUAL (BEBAS BIAYA) =================
        'MANUAL_BCA' => [
            'code' => 'MANUAL_BCA',
            'name' => 'Transfer Manual Bank BCA (CV Asianindo)',
            'category' => 'manual',
            'category_name' => 'Transfer Manual Bebas Biaya',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg',
            'fee_type' => 'flat',
            'fee_value' => 0,
            'fee_label' => 'Bebas Biaya (Rp 0)',
            'description' => 'No. Rekening BCA: 6670747997 a/n Iman Anjani Buchory',
            'is_active' => true
        ]
    ];
}

/**
 * Menghitung Biaya Layanan Platform berdasarkan Saluran Pembayaran & Nominal Transaksi
 */
function calculateDuitkuFee($channelCode, $amount) {
    $amount = max(0, (int)$amount);
    $channels = getDuitkuPaymentChannels();

    if (!isset($channels[$channelCode])) {
        return 3000;
    }

    $ch = $channels[$channelCode];

    if ($ch['fee_type'] === 'flat') {
        return (int)($ch['fee_value'] ?? 0);
    }

    if ($ch['fee_type'] === 'percent') {
        $percent = (float)($ch['fee_percent'] ?? 0);
        return (int)round(($amount * $percent) / 100);
    }

    if ($ch['fee_type'] === 'combo') {
        $percent = (float)($ch['fee_percent'] ?? 0);
        $fixed = (int)($ch['fee_fixed'] ?? 0);
        return (int)(round(($amount * $percent) / 100) + $fixed);
    }

    return 0;
}
