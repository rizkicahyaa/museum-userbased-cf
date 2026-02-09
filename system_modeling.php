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

// Store logical similarities for calculation
$full_similarities = [];

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

        // Store bidirectional similarity map
        $full_similarities[$uA][$uB] = $sim;
        $full_similarities[$uB][$uA] = $sim;
    }
}

// --- NEW SECTION: Prediction Logic ---
// Target: User 1 (U1)
// Unrated Item: Keraton Yogyakarta (M3)
$target_user_id = 'U1';
$target_item_id = 'M3';
$target_item_name = $museums[$target_item_id];

// Get Neighbors who rated M3
$neighbors = [];
$total_weighted_sum = 0;
$total_similarity = 0;

if (isset($full_similarities[$target_user_id])) {
    foreach ($full_similarities[$target_user_id] as $neighbor_id => $sim) {
        // Check if neighbor rated the target item
        if ($sim > 0 && isset($ratings[$neighbor_id][$target_item_id]) && $ratings[$neighbor_id][$target_item_id] > 0) {
            $neighbor_rating = $ratings[$neighbor_id][$target_item_id];
            
            $weighted_rating = $neighbor_rating * $sim;
            
            $neighbors[] = [
                'id' => $neighbor_id,
                'name' => $users[$neighbor_id],
                'rating' => $neighbor_rating,
                'similarity' => $sim,
                'weighted' => $weighted_rating
            ];
            
            $total_weighted_sum += $weighted_rating;
            $total_similarity += abs($sim);
        }
    }
}

// Sort neighbors by similarity descending
usort($neighbors, function($a, $b) {
    return $b['similarity'] <=> $a['similarity'];
});

// Calculate Prediction
$predicted_rating = 0;
if ($total_similarity > 0) {
    $predicted_rating = $total_weighted_sum / $total_similarity;
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
        .prediction-box {
            background: #f8f9fa;
            border-left: 5px solid var(--primary-color);
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
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
                                    <tr class="<?= $u == $target_user_id ? 'table-primary' : '' ?>">
                                        <td class="fw-bold text-start"><?= $users[$u] ?></td>
                                        <?php foreach ($r as $key => $val): ?>
                                            <td>
                                                <?php if($val > 0): ?>
                                                    <span class="badge bg-primary rounded-pill"><?= $val ?></span>
                                                <?php elseif($u == $target_user_id && $key == $target_item_id): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill">?</span>
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
                        <div class="mt-2 text-end">
                            <small class="text-muted"><span class="badge bg-warning text-dark rounded-pill">?</span> menunjukan item yang akan diprediksi ratingnya (<?= $target_item_name ?>).</small>
                        </div>
                    </div>
                </div>

                <!-- 2. Tabel Perhitungan Kemiripan -->
                <div class="card mb-5 slide-in" style="animation-delay: 0.2s;">
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

                <!-- 3. Hasil Prediksi Rating -->
                <div class="card mb-4 slide-in" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-line me-2"></i>3. Hasil Prediksi Rating</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Tabel ini menunjukkan proses prediksi rating untuk <strong><?= $target_item_name ?></strong> bagi <strong><?= $users[$target_user_id] ?></strong>.
                            Hanya pengguna yang memiliki nilai kemiripan positif dan telah memberikan rating pada museum tersebut yang digunakan dalam perhitungan.
                        </p>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-hover text-center align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>User Tetangga (Neighbor)</th>
                                        <th>Rating Tetangga (R)</th>
                                        <th>Nilai Kemiripan (S)</th>
                                        <th>Weighted Score (R x S)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($neighbors as $n): ?>
                                    <tr>
                                        <td class="text-start ps-4 fw-bold"><?= $n['name'] ?> (<?= $n['id'] ?>)</td>
                                        <td><span class="badge bg-success rounded-pill"><?= $n['rating'] ?></span></td>
                                        <td><?= number_format($n['similarity'], 2, ',', '.') ?></td>
                                        <td class="fw-bold text-primary"><?= number_format($n['weighted'], 2, ',', '.') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="table-active fw-bold">
                                        <td colspan="2" class="text-end">Total</td>
                                        <td><?= number_format($total_similarity, 2, ',', '.') ?> (Total S)</td>
                                        <td><?= number_format($total_weighted_sum, 2, ',', '.') ?> (Total W)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="prediction-box">
                            <h5 class="fw-bold mb-3"><i class="fas fa-equals me-2"></i>Perhitungan Akhir</h5>
                            <div class="d-flex align-items-center flex-wrap gap-3">
                                <div>
                                    <strong>Rumus:</strong><br>
                                    Prediksi = (Total Weighted Score) / (Total Similarity)
                                </div>
                                <div class="vr mx-2 d-none d-md-block"></div>
                                <div>
                                    <strong>Hitungan:</strong><br>
                                    <?= number_format($total_weighted_sum, 2, ',', '.') ?> / <?= number_format($total_similarity, 2, ',', '.') ?>
                                </div>
                                <div class="vr mx-2 d-none d-md-block"></div>
                                <div>
                                    <strong>Hasil Prediksi:</strong><br>
                                    <span class="badge bg-info text-dark fs-5 shadow-sm"><?= number_format($predicted_rating, 2, ',', '.') ?></span>
                                </div>
                            </div>
                            <div class="mt-3 text-success">
                                <small><i class="fas fa-check-circle me-1"></i> Nilai <strong><?= number_format($predicted_rating, 2, ',', '.') ?></strong> menunjukkan bahwa sistem memprediksi <strong>User 1</strong> akan menyukai <strong><?= $target_item_name ?></strong>.</small>
                            </div>
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
