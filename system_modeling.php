<?php
session_start();
require_once 'config.php'; // Include config for potential DB connection if needed in future

// Menggunakan data simulasi untuk keperluan pemodelan sistem (Bab 4)
// Data ini hardcoded agar perhitungan algoritmanya terlihat jelas dan variatif

// 1. Data Sampel (Matriks User-Item)
$users = [
    'U1' => 'User 1 (Firda)',
    'U2' => 'User 2 (Dina)',
    'U3' => 'User 3 (Azizah)',
    'U4' => 'User 4 (Choffee)',
    'U5' => 'User 5 (Ungky)',
    'U6' => 'User 6 (Chihiro)' 
];

$museums = [
    'M1' => 'Museum Sonobudoyo',
    'M2' => 'Museum Vredeburg',
    'M3' => 'Keraton Yogyakarta',
    'M4' => 'Museum Affandi'
];

// Matrix Rating: Baris = User, Kolom = Museum
// Nilai 0 artinya belum merating
$ratings = [
    'U1' => ['M1' => 5, 'M2' => 4, 'M3' => 0, 'M4' => 2],
    'U2' => ['M1' => 5, 'M2' => 5, 'M3' => 4, 'M4' => 0],
    'U3' => ['M1' => 2, 'M2' => 0, 'M3' => 5, 'M4' => 5],
    'U4' => ['M1' => 4, 'M2' => 3, 'M3' => 0, 'M4' => 4],
    'U5' => ['M1' => 5, 'M2' => 4, 'M3' => 5, 'M4' => 0],
    'U6' => ['M1' => 4, 'M2' => 5, 'M3' => 4, 'M4' => 0] 
];

// Fungsi Hitung Cosine Similarity
function calculateCosineSimilarity($userA_ratings, $userB_ratings) {
    $dot_product = 0;
    $normA = 0;
    $normB = 0;

    foreach ($userA_ratings as $item => $ratingA) {
        $ratingB = isset($userB_ratings[$item]) ? $userB_ratings[$item] : 0;
        
        $dot_product += $ratingA * $ratingB;
        $normA += pow($ratingA, 2);
    }
    
    foreach ($userB_ratings as $ratingB) {
        $normB += pow($ratingB, 2);
    }
    
    $normA = 0;
    foreach ($userA_ratings as $ratingA) {
        $normA += pow($ratingA, 2);
    }
    
    $normA = sqrt($normA);
    $normB = sqrt($normB);

    if ($normA * $normB == 0) return 0;

    return round($dot_product / ($normA * $normB), 2);
}

// Calculate Similarity for All Unique Pairs
$pair_similarities = [];
$user_keys = array_keys($users);
$count = count($user_keys);

for ($i = 0; $i < $count; $i++) {
    for ($j = $i + 1; $j < $count; $j++) {
        $uA = $user_keys[$i];
        $uB = $user_keys[$j];
        
        $sim = calculateCosineSimilarity($ratings[$uA], $ratings[$uB]);
        
        $pair_similarities[] = [
            'UserA' => $uA, 
            'UserB' => $uB, 
            'UserAName' => $users[$uA],
            'UserBName' => $users[$uB],
            'Similarity' => $sim
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Pemodelan Sistem - Artify</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Additional styling for this specific page */
        .modeling-section {
            padding: 2rem 0;
        }
        .table-custom th {
            text-align: center;
        }
        .table-custom td {
            text-align: center;
            vertical-align: middle;
        }
        .user-col {
            text-align: left !important;
            padding-left: 20% !important;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Content -->
    <div class="container modeling-section">
        
        <!-- Hero Section -->
        <div class="hero-section text-center">
            <h1 class="mb-3">Simulasi Pemodelan Sistem</h1>
            <p class="mb-0">Demonstrasi perhitungan algoritma Collaborative Filtering untuk rekomendasi museum.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                
                <!-- 1. Matriks User-Item -->
                <div class="card mb-5 slide-in">
                    <div class="card-header">
                        <h4><i class="fas fa-table me-2"></i>1. Matriks Rating Pengguna</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Tabel ini menunjukkan sampel data rating yang diberikan pengguna (User 1 - User 6) terhadap beberapa museum.</p>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <?php foreach ($museums as $m): ?>
                                            <th><?= $m ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ratings as $u => $r): ?>
                                    <tr>
                                        <td class="fw-bold text-start"><?= $users[$u] ?></td>
                                        <?php foreach ($r as $val): ?>
                                            <td>
                                                <?php if($val > 0): ?>
                                                    <span class="badge bg-primary rounded-pill"><?= $val ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. Tabel Perhitungan Kemiripan -->
                <div class="card mb-4 slide-in" style="animation-delay: 0.2s;">
                    <div class="card-header">
                        <h4><i class="fas fa-calculator me-2"></i>2. Hasil Perhitungan Nilai Kemiripan</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Tabel berikut menampilkan nilai <em>Cosine Similarity</em> antar pasangan pengguna.</p>
                        
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th width="35%">User A</th>
                                        <th width="35%">User B</th>
                                        <th width="30%" class="text-center">Nilai Kemiripan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pair_similarities as $pair): ?>
                                        <tr>
                                            <td class="user-col">
                                                <i class="fas fa-user me-2 text-primary"></i>
                                                <?= str_replace(['U','('], ['User ',' ('], $pair['UserA']) ?>
                                            </td>
                                            <td class="user-col">
                                                <i class="fas fa-user-friends me-2 text-secondary"></i>
                                                <?= str_replace(['U','('], ['User ',' ('], $pair['UserB']) ?>
                                            </td>
                                            <td class="text-center fw-bold text-dark">
                                                <?= number_format($pair['Similarity'], 2, ',', '.') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button onclick="window.print()" class="btn btn-primary me-2">
                        <i class="fas fa-print me-2"></i>Cetak Laporan
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
