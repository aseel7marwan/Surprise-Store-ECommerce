<?php
/**
 * Surprise! Store - Search Suggestions API
 * Returns product suggestions for autocomplete
 * Supports Arabic text normalization
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../includes/config.php';
require_once '../includes/functions.php';

/**
 * Normalize Arabic text for better matching
 * - Removes diacritics (tashkeel)
 * - Normalizes alef variants (أ إ آ → ا)
 * - Normalizes yaa/alef maksura (ى → ي)
 * - Normalizes taa marbuta (ة → ه) - optional
 * - Removes "ال" prefix for matching
 */
function normalizeArabic($text) {
    if (empty($text)) return '';
    
    // Remove Arabic diacritics (Tashkeel)
    $diacritics = [
        'ً', 'ٌ', 'ٍ', 'َ', 'ُ', 'ِ', 'ّ', 'ْ', // Standard
        'ٓ', 'ٰ', 'ۡ', 'ۢ', 'ۣ', 'ۤ', 'ۥ', 'ۦ' // Extended
    ];
    $text = str_replace($diacritics, '', $text);
    
    // Normalize Alef variants to plain Alef
    $alefVariants = ['أ', 'إ', 'آ', 'ٱ', 'ٲ', 'ٳ', 'ٵ'];
    $text = str_replace($alefVariants, 'ا', $text);
    
    // Normalize Yaa with Alef Maksura
    $text = str_replace('ى', 'ي', $text);
    
    // Normalize Taa Marbuta to Haa (optional - helps with typos)
    $text = str_replace('ة', 'ه', $text);
    
    // Also normalize Waw Hamza and Yaa Hamza
    $text = str_replace(['ؤ', 'ئ'], ['و', 'ي'], $text);
    
    // Trim whitespace
    $text = trim($text);
    
    return $text;
}

/**
 * Remove "ال" prefix for matching (but keep original for display)
 */
function removeAlPrefix($text) {
    $normalized = $text;
    
    // Remove "ال" from beginning
    if (mb_substr($normalized, 0, 2, 'UTF-8') === 'ال') {
        $normalized = mb_substr($normalized, 2, null, 'UTF-8');
    }
    
    return $normalized;
}

/**
 * Calculate match score for sorting
 * Higher score = better match
 */
function calculateMatchScore($productName, $searchQuery) {
    $normalizedProduct = normalizeArabic($productName);
    $normalizedQuery = normalizeArabic($searchQuery);
    
    // Also try without "ال"
    $productNoAl = removeAlPrefix($normalizedProduct);
    $queryNoAl = removeAlPrefix($normalizedQuery);
    
    $score = 0;
    
    // Exact match (highest priority)
    if ($normalizedProduct === $normalizedQuery) {
        $score += 100;
    }
    
    // Starts with query (high priority)
    if (mb_strpos($normalizedProduct, $normalizedQuery, 0, 'UTF-8') === 0) {
        $score += 50;
    }
    
    // Starts with query (without ال)
    if (mb_strpos($productNoAl, $queryNoAl, 0, 'UTF-8') === 0) {
        $score += 45;
    }
    
    // Contains query
    if (mb_strpos($normalizedProduct, $normalizedQuery, 0, 'UTF-8') !== false) {
        $score += 20;
    }
    
    // Word starts with query
    $words = explode(' ', $normalizedProduct);
    foreach ($words as $word) {
        if (mb_strpos($word, $normalizedQuery, 0, 'UTF-8') === 0) {
            $score += 30;
            break;
        }
    }
    
    // Query length bonus (longer = more specific = higher score)
    $score += mb_strlen($normalizedQuery, 'UTF-8') * 0.5;
    
    return $score;
}

// Get query parameter
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 10) : 6;

// Debug mode - DISABLED in production
$debug = false;

// Return empty if query is too short
if (mb_strlen($query, 'UTF-8') < 1) {
    echo json_encode(['success' => true, 'suggestions' => [], 'products' => []]);
    exit;
}

try {
    // Get all non-hidden products (NOT 'active' - that status doesn't exist!)
    // Use 'exclude_hidden' instead which filters out status='hidden'
    $products = getProducts(['exclude_hidden' => true]);
    
    // Debug: Log product count
    if ($debug) {
        error_log("Search Suggestions: Query='$query', Products count=" . count($products));
    }
    
    $normalizedQuery = normalizeArabic($query);
    $queryNoAl = removeAlPrefix($normalizedQuery);
    
    $matches = [];
    $seenNames = []; // To avoid duplicate suggestions
    
    foreach ($products as $product) {
        $productName = $product['name'];
        $normalizedName = normalizeArabic($productName);
        $nameNoAl = removeAlPrefix($normalizedName);
        
        // Check if product matches
        $isMatch = false;
        
        // Match in normalized name
        if (mb_strpos($normalizedName, $normalizedQuery, 0, 'UTF-8') !== false) {
            $isMatch = true;
        }
        
        // Match without ال prefix
        if (mb_strpos($nameNoAl, $queryNoAl, 0, 'UTF-8') !== false) {
            $isMatch = true;
        }
        
        // Match in description (lower priority)
        if (!$isMatch && !empty($product['description'])) {
            $normalizedDesc = normalizeArabic($product['description']);
            if (mb_strpos($normalizedDesc, $normalizedQuery, 0, 'UTF-8') !== false) {
                $isMatch = true;
            }
        }
        
        if ($isMatch) {
            $score = calculateMatchScore($productName, $query);
            
            // Get first image
            $image = '';
            if (!empty($product['images']) && is_array($product['images']) && count($product['images']) > 0) {
                $image = $product['images'][0];
            }
            
            $matches[] = [
                'id' => $product['id'],
                'name' => $productName,
                'price' => (int)$product['price'],
                'old_price' => isset($product['old_price']) ? (int)$product['old_price'] : 0,
                'image' => $image,
                'category' => $product['category'] ?? '',
                'score' => $score
            ];
            
            // Track unique names for suggestions
            $seenNames[mb_strtolower($productName, 'UTF-8')] = $productName;
        }
    }
    
    // Sort by score (descending)
    usort($matches, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Limit results
    $matches = array_slice($matches, 0, $limit);
    
    // Generate text suggestions (unique product names + category hints)
    $suggestions = [];
    foreach ($matches as $match) {
        $suggestions[] = $match['name'];
    }
    $suggestions = array_unique($suggestions);
    $suggestions = array_slice($suggestions, 0, 5);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'suggestions' => array_values($suggestions),
        'products' => $matches
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'حدث خطأ في البحث',
        'suggestions' => [],
        'products' => []
    ], JSON_UNESCAPED_UNICODE);
}
