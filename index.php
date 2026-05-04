<?php

// Ngecek Openssl... yeah i want the easy one
if (!extension_loaded('openssl')) {
    die("Ekstensi OpenSSL tidak ditemukan.");
}

// Inisialisasi variabel
$pesan_asli = "";
$public_key_alice = "";
$private_key_alice = "";
$ciphertext_base64 = "";
$pesan_terdekripsi = "";
$error_message = "";
$max_message_length = 190; // RSA-2048

$keys_file = sys_get_temp_dir() . '/rsa_demo_keys.json';

// Fungsi: Generate atau load kunci RSA
function getOrGenerateKeys($keys_file) {
    if (file_exists($keys_file)) {
        $keys_data = json_decode(file_get_contents($keys_file), true);
        return $keys_data;
    }
    
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    
    $res = openssl_pkey_new($config);
    if ($res === false) {
        throw new Exception("Gagal membuat kunci RSA");
    }
    
    openssl_pkey_export($res, $private_key);
    $pubKey = openssl_pkey_get_details($res);
    
    if ($pubKey === false) {
        throw new Exception("Gagal mengekstrak detail public key");
    }
    
    $keys_data = array(
        'public_key' => $pubKey['key'],
        'private_key' => $private_key,
        'generated_at' => date('Y-m-d H:i:s')
    );
    
    file_put_contents($keys_file, json_encode($keys_data), LOCK_EX);
    chmod($keys_file, 0600); // Hanya readable oleh owner 
    // easy explaination by me
    // 0 - no akses
    // 6 - akses untuk pemilik file
    // 0 - no akses untuk root
    // 0 - no akses untuk orang lain (user lain)
    
    return $keys_data;
}

// Fungsi: Validasi dan enkripsi pesan
function encryptMessage($plaintext, $public_key, $max_length) {
    if (strlen($plaintext) > $max_length) {
        return array('success' => false, 'error' => "Pesan terlalu panjang (max: $max_length bytes)");
    }
    
    if (!openssl_public_encrypt($plaintext, $ciphertext, $public_key)) {
        return array('success' => false, 'error' => 'Enkripsi gagal: ' . openssl_error_string());
    }
    
    return array(
        'success' => true,
        'ciphertext_base64' => base64_encode($ciphertext),
        'ciphertext_length' => strlen($ciphertext)
    );
}

// Fungsi: Dekripsi pesan
function decryptMessage($ciphertext_base64, $private_key) {
    $ciphertext = base64_decode($ciphertext_base64, true);
    
    if ($ciphertext === false) {
        return array('success' => false, 'error' => 'Decode base64 gagal');
    }
    
    if (!openssl_private_decrypt($ciphertext, $plaintext, $private_key)) {
        return array('success' => false, 'error' => 'Dekripsi gagal: ' . openssl_error_string());
    }
    
    return array('success' => true, 'plaintext' => $plaintext);
}

// Load atau generate kunci
try {
    $keys_data = getOrGenerateKeys($keys_file);
    $public_key_alice = $keys_data['public_key'];
    $private_key_alice = $keys_data['private_key'];
} catch (Exception $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}

// Proses jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['pesan'])) {
    $pesan_asli = $_POST['pesan'];
    
    // Validasi input
    if (strlen($pesan_asli) > $max_message_length) {
        $error_message = "Pesan terlalu panjang. Maksimal " . $max_message_length . " karakter.";
    } else {
        $encrypt_result = encryptMessage($pesan_asli, $public_key_alice, $max_message_length);
        
        if (!$encrypt_result['success']) {
            $error_message = $encrypt_result['error'];
        } else {
            $ciphertext_base64 = $encrypt_result['ciphertext_base64'];
            
            $decrypt_result = decryptMessage($ciphertext_base64, $private_key_alice);
            
            if ($decrypt_result['success']) {
                $pesan_terdekripsi = $decrypt_result['plaintext'];
            } else {
                $error_message = $decrypt_result['error'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Simulasi RSA: Alice & Bob</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; max-width: 800px; margin: 20px auto; padding: 0 20px; background-color: #f4f4f9; }
        .box { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; }
        h2 { color: #333; border-bottom: 2px solid #333; padding-bottom: 5px; }
        textarea { width: 100%; font-family: monospace; background: #eee; }
        input[type="text"] { width: 80%; padding: 8px; margin-bottom: 10px; }
        input[type="submit"] { padding: 8px 20px; cursor: pointer; background: #5cb85c; color: white; border: none; border-radius: 3px; }
        .error { color: #d9534f; background: #f2dede; padding: 10px; border-radius: 3px; margin-bottom: 10px; border: 1px solid #ebcccc; }
    </style>
</head>
<body>

    <h1>Tugas Simulasi Surat RSA</h1>

    <div class="box">
        <h2>1. Setup Alice (Penerima)</h2>
        <p>Alice telah membuat sepasangan kunci. Berikut adalah <strong>Public Key</strong> Alice yang tersedia untuk publik:</p>
        <textarea rows="6" readonly><?php echo htmlspecialchars($public_key_alice); ?></textarea>
    </div>

    <div class="box">
        <h2>2. Bob Mengirim Pesan (Pengirim)</h2>
        <?php if ($error_message): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <label>Masukkan Pesan Bob (Teks Asli, max <?php echo $max_message_length; ?> karakter):</label><br>
            <input type="text" name="pesan" value="SAYA AKAN LAWAN!" placeholder="Ketik pesan di sini...">
            <input type="submit" value="Kirim Pesan">
        </form>
        <?php if ($ciphertext_base64): ?>
            <p><span>Hasil Enkripsi Bob (Ciphertext - Base64):</span></p>
            <textarea rows="4" readonly><?php echo htmlspecialchars($ciphertext_base64); ?></textarea>
            <p class="info">Panjang ciphertext: <?php echo strlen($ciphertext_base64); ?> bytes (Base64)</p>
        <?php endif; ?>
    </div>

    <?php if ($pesan_terdekripsi): ?>
    <div class="box" style="border-color: #5bc0de;">
        <h2>3. Alice Membaca Pesan</h2>
        <div style="font-size: 1.5em; font-weight: bold; color: #2e6da4; padding: 10px; background: #e0f2f1;">
            <?php echo htmlspecialchars($pesan_terdekripsi); ?>
        </div>
    </div>
    <?php endif; 
?>

</body>
</html>

