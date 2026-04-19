<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kalkulator FPB & Relatif Prima</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container">
    <h2>Kalkulator FPB</h2>

    <form method="POST" action="">
      <div class="form-group">
        <label for="angka1">ANGKA 1</label>
        <input type="number" name="angka1" id="angka1" required
          value="<?= isset($_POST['angka1']) ? htmlspecialchars($_POST['angka1']) : '' ?>">
      </div>
      <div class="form-group">
        <label for="angka2">ANGKA 2</label>
        <input type="number" name="angka2" id="angka2" required
          value="<?= isset($_POST['angka2']) ? htmlspecialchars($_POST['angka2']) : '' ?>">
      </div>
      <button type="submit" name="hitung">Hitung FPB</button>
    </form>

    <?php
    function cari_fpb($a, $b) {
        $a = abs($a);
        $b = abs($b);
        while ($b != 0) {
            $sisa = $a % $b;
            $a = $b; 
            $b = $sisa; 
        }
        return $a;
      }

    if (isset($_POST['hitung'])) {
        $nilaiA = intval($_POST['angka1']);
        $nilaiB = intval($_POST['angka2']);
        if ($nilaiA == 0 || $nilaiB == 0) {
            echo "<div class='result tidak-prima'>Masukkan angka selain nol.</div>";
        } else {
            $hasilFPB = cari_fpb($nilaiA, $nilaiB);
            $status = ($hasilFPB == 1) ? "Kedua angka RELATIF PRIMA" : "TIDAK RELATIF PRIMA";
            $class = ($hasilFPB == 1) ? "prima" : "tidak-prima";

            echo "<div class='result $class'>";
            echo "<strong>Hasil FPB dari $nilaiA dan $nilaiB = $hasilFPB</strong><br>";
            echo "<small>$status</small>";
            echo "</div>";
        }
      }
    ?>
  </div>
</body>
</html>
