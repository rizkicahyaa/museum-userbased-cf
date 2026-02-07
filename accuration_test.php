<?php
require_once 'config.php';

// Fungsi Cosine Similarity (Sama persis dengan di recommendations.php)
function cosineSimilarity($user1_ratings, $user2_ratings) {
    $dot_product = 0;
    $norm1 = 0;
    $norm2 = 0;
    
    $common_items = array_intersect_key($user1_ratings, $user2_ratings);
    
    if (count($common_items) == 0) {
        return 0;
    }
    
    foreach ($common_items as $item => $rating1) {
        $rating2 = $user2_ratings[$item];
        $dot_product += $rating1 * $rating2;
        $norm1 += $rating1 * $rating1;
        $norm2 += $rating2 * $rating2;
    }
    
    if ($norm1 == 0 || $norm2 == 0) {
        return 0;
    }
    
    return $dot_product / (sqrt($norm1) * sqrt($norm2));
}

// 1. Ambil data rating nyata dari database
$query = "SELECT id, user_name, museum_name, rating FROM museum_ratings";
$result = $conn->query($query);

$ratings_data = [];
$user_item_matrix = [];
$all_users = [];

while ($row = $result->fetch_assoc()) {
    $ratings_data[] = $row; // Simpan data mentah untuk iterasi pengujian
    
    // Bangun matrix untuk lookup saat perhitungan
    $u = $row['user_name'];
    $m = $row['museum_name'];
    $r = floatval($row['rating']);
    
    if (!isset($user_item_matrix[$u])) {
        $user_item_matrix[$u] = [];
        $all_users[] = $u;
    }
    $user_item_matrix[$u][$m] = $r;
}

// 2. Mulai Pengujian (K-Fold / Leave-One-Out sederhana)
$total_error = 0;
$total_squared_error = 0;
$count_predictions = 0;
$details = [];

// Loop setiap rating yang ada di database
foreach ($ratings_data as $test_case) {
    $target_user = $test_case['user_name'];
    $target_item = $test_case['museum_name'];
    $actual_rating = floatval($test_case['rating']);
    
    // Skenario: Kita "pura-pura" user ini belum merating museum ini.
    // Jadi kita hapus sementara rating ini dari memori matrix user target
    // agar tidak bocor ke perhitungan similarity alias 'cheat'.
    $current_user_ratings = $user_item_matrix[$target_user];
    unset($current_user_ratings[$target_item]); 
    
    // Jika user tidak punya rating lain selain ini, kita tidak bisa hitung similarity
    if (empty($current_user_ratings)) {
        continue; 
    }

    // --- LOGIKA REKOMENDASI DIMULAI DISINI ---
    
    // Hitung similarity dengan user lain
    $user_similarities = [];
    foreach ($all_users as $other_user) {
        if ($other_user != $target_user && isset($user_item_matrix[$other_user])) {
            // Rating user lain tetap utuh
            $similarity = cosineSimilarity($current_user_ratings, $user_item_matrix[$other_user]);
            if ($similarity > 0) {
                $user_similarities[$other_user] = $similarity;
            }
        }
    }
    
    // Urutkan similarity
    arsort($user_similarities);
    
    // Ambil Top-10 Neighbors
    $top_similar_users = array_slice($user_similarities, 0, 10, true);
    
    $weighted_sum = 0;
    $similarity_sum = 0;
    
    foreach ($top_similar_users as $similar_user => $similarity) {
        // Cek apakah neighbor ini merating item target
        if (isset($user_item_matrix[$similar_user][$target_item])) {
            $neighbor_rating = $user_item_matrix[$similar_user][$target_item];
            
            $weighted_sum += $similarity * $neighbor_rating;
            $similarity_sum += abs($similarity);
        }
    }
    
    if ($similarity_sum > 0) {
        $predicted_rating = $weighted_sum / $similarity_sum;
        
        // Simpan hasil
        $error = abs($actual_rating - $predicted_rating);
        $total_error += $error;
        $total_squared_error += pow($error, 2);
        $count_predictions++;
        
        $details[] = [
            'user' => $target_user,
            'item' => $target_item,
            'actual' => $actual_rating,
            'predicted' => round($predicted_rating, 2),
            'error' => round($error, 2)
        ];
    }
}

// Hitung MAE & RMSE
$mae = 0;
$rmse = 0;
if ($count_predictions > 0) {
    $mae = $total_error / $count_predictions;
    $rmse = sqrt($total_squared_error / $count_predictions);
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Artify - Pengujian Akurasi (MAE)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="images/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .metric-card { transition: transform 0.2s; }
        .metric-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Hasil Pengujian Akurasi</h3>
                <small>Metode: Leave-One-Out Cross Validation</small>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <!-- MAE CARD -->
                    <div class="col-md-6 mb-3">
                        <div class="card metric-card h-100 border-primary">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-2">Mean Absolute Error (MAE)</h6>
                                <h1 class="display-4 fw-bold text-primary"><?php echo number_format($mae, 4); ?></h1>
                                <p class="small text-muted mb-0">Rata-rata kesalahan prediksi (absolut).</p>
                                <div class="badge bg-primary mt-2">Semakin Kecil Semakin Baik</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- RMSE CARD -->
                    <div class="col-md-6 mb-3">
                        <div class="card metric-card h-100 border-danger">
                            <div class="card-body">
                                <h6 class="text-muted text-uppercase mb-2">Root Mean Square Error (RMSE)</h6>
                                <h1 class="display-4 fw-bold text-danger"><?php echo number_format($rmse, 4); ?></h1>
                                <p class="small text-muted mb-0">Hukuman lebih berat untuk kesalahan besar.</p>
                                <div class="badge bg-danger mt-2">Indikator Stabilitas Error</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-3">
                    <strong>Interpretasi:</strong>
                    <ul class="mb-0 mt-1">
                        <li><strong>MAE (<?php echo number_format($mae, 2); ?>):</strong> Rata-rata prediksi meleset sekitar <?php echo number_format($mae, 2); ?> poin.</li>
                        <li><strong>RMSE (<?php echo number_format($rmse, 2); ?>):</strong> 
                            <?php if ($rmse > $mae + 0.3): ?>
                                Nilai RMSE jauh lebih besar dari MAE, menandakan ada beberapa prediksi yang <strong>sangat meleset (outlier)</strong>.
                            <?php else: ?>
                                Nilai RMSE mendekati MAE, menandakan error sistem cukup <strong>stabil</strong>.
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
                
                <div class="mt-2 text-center">
                     <span class="badge bg-secondary p-2">Total Data: <?php echo count($ratings_data); ?></span>
                     <span class="badge bg-success p-2">Berhasil Diprediksi: <?php echo $count_predictions; ?></span>
                </div>
            </div>
        </div>

        <div class="card mt-4 shadow-sm">
            <div class="card-header text-white">
                Detail Pengujian per Data
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>No</th>
                                <th>User</th>
                                <th>Museum</th>
                                <th>Rating Asli</th>
                                <th>Prediksi</th>
                                <th>Error (|Asli - Prediksi|)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($details as $row): 
                                // Highlight error tinggi
                                $class = ($row['error'] > 1.5) ? 'text-danger fw-bold' : '';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['user']); ?></td>
                                <td><?php echo htmlspecialchars($row['item']); ?></td>
                                <td><?php echo $row['actual']; ?></td>
                                <td><?php echo $row['predicted']; ?></td>
                                <td class="<?php echo $class; ?>"><?php echo $row['error']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (count($details) == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    Tidak ada data prediksi yang bisa dihitung. 
                                    <br>
                                    <small class="text-muted">Pastikan ada cukup data rating dan irisan antar user (similarity).</small>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">Kembali ke Aplikasi</a>
        </div>
    </div>
</body>
</html>
