<?php
/**
 * Surprise! Store - Dynamic Sitemap Generator
 * Generates sitemap.xml for search engines
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/xml; charset=UTF-8');

// Get base URL
$baseUrl = 'https://surprise-iq.com';

// Get all products
$products = getProducts(['exclude_hidden' => true]);

// Get all categories
$categories = getCategories();

// Current date for lastmod
$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    
    <!-- Homepage - Highest Priority -->
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Products Page -->
    <url>
        <loc><?= $baseUrl ?>/products</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    
    <!-- Category Pages -->
<?php foreach ($categories as $key => $cat): ?>
    <url>
        <loc><?= $baseUrl ?>/products?category=<?= urlencode($key) ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
    
    <!-- Individual Product Pages -->
<?php foreach ($products as $product): ?>
    <url>
        <loc><?= $baseUrl ?>/product?id=<?= $product['id'] ?></loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
<?php if (!empty($product['images'])): ?>
        <image:image>
            <image:loc><?= $baseUrl ?>/images/<?= $product['images'][0] ?></image:loc>
            <image:title><?= htmlspecialchars($product['name']) ?></image:title>
            <image:caption><?= htmlspecialchars(substr($product['description'], 0, 100)) ?></image:caption>
        </image:image>
<?php endif; ?>
    </url>
<?php endforeach; ?>
    
    <!-- Static Pages -->
    <url>
        <loc><?= $baseUrl ?>/about</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    
    <url>
        <loc><?= $baseUrl ?>/track</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    
    <url>
        <loc><?= $baseUrl ?>/cart</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    
    <url>
        <loc><?= $baseUrl ?>/privacy</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    
    <url>
        <loc><?= $baseUrl ?>/terms</loc>
        <lastmod><?= $today ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.3</priority>
    </url>
    
</urlset>
