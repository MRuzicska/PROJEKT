<?php
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';

// Slider képek
$sliderImages = [
    "images/slider1.jpg",
    "images/slider2.webp",
    "images/slider3.webp",
    "images/slider4.webp",
    "images/slider5.webp",
];

// Termékek
$products = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<title>Parfum p'Dm</title>
<link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<header class="navbar">
    <div class="logo">
        <img src="images/logo-placeholder.png" alt="Parfum p'Dm Logo">
        <span>Parfum p'Dm</span>
        <button class="hamburger" onclick="toggleMenu()">☰</button>
    </div>
    <nav class="nav-links">
        <a href="products.php">Összes parfüm</a>
        <a href="products.php?gender=ferfi">Csak férfi</a>
        <a href="products.php?gender=noi">Csak női</a>
        <a href="products.php?gender=unisex">Csak unisex</a>
        <input type="text" placeholder="Keresés..." class="search-input">
    </nav>
    <div class="nav-actions">
        <div class="cart-wrapper">
            <button onclick="toggleCart()">🛒 Kosár</button>
            <div class="dropdown cart-dropdown">
                <p>Kosár tartalma...</p>
                <a href="cart.php" class="btn-small">Kosár megnyitása</a>
            </div>
        </div>
        <div class="profile-wrapper">
            <button onclick="toggleProfile()">👤 Profil</button>
            <div class="dropdown profile-dropdown">
                <?php if(is_logged_in()): ?>
                    <p>Üdvözlünk, <?=h($_SESSION['username'] ?? 'Felhasználó')?>!</p>
                    <?php if(is_admin()): ?>
                        <a href="admin.php" class="btn-small">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-small">Kijelentkezés</a>
                <?php else: ?>
                    <a href="login.php" class="btn-small">Bejelentkezés</a>
                    <a href="register.php" class="btn-small">Regisztráció</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- ===== SLIDER ===== -->
<div class="slider">
    <a class="prev" href="javascript:void(0)" onclick="prevSlide()">&#10094;</a>
    <div class="slide-image">
        <img id="slider-img" src="<?= $sliderImages[0] ?>" alt="Slider">
    </div>
    <a class="next" href="javascript:void(0)" onclick="nextSlide()">&#10095;</a>
</div>

<div class="info-text">
    <p>
        Fedezd fel a parfümök világát! 💧 Kínálatunkban a klasszikus aromáktól a modern, trendi illatokig mindent megtalálsz. 
        Ne hagyd, hogy az unalmas napok eluralkodjanak – egy igazán jó illat mindig feldobja a hangulatod!  
        Kattints, szagolj, élvezd – a parfüm nem csak egy illat, hanem élmény is.
    </p>
</div>

<!-- ===== TERMÉKKÁRTYÁK ===== -->
<div class="product-grid" id="product-grid">
<?php foreach ($products as $p): ?>
  <a href="product.php?id=<?= (int)$p['id'] ?>" class="product-card">
    <div class="product-image">
      <?php if (!empty($p['image_url'])): ?>
        <img src="<?=h($p['image_url'])?>" alt="<?=h($p['name'])?>">
      <?php endif; ?>
    </div>
    <div class="product-name"><?=h($p['name'])?></div>
    <div class="product-brand"><?=h($p['brand'])?></div>
    <div class="product-category">(<?=h($p['category_name'])?>)</div>
    <div class="product-price">Ár: <?= (int)$p['price'] ?> Ft</div>
    <div class="product-stock">Készlet: <?= (int)$p['stock'] ?></div>
  </a>
<?php endforeach; ?>
</div>

<!-- Termék lapozás -->
<div class="slider-nav">
    <button onclick="prevProducts()">← Előző</button>
    <button onclick="nextProducts()">Következő →</button>
    <a href="products.php" class="all-products">Összes parfüm</a>
</div>

<script>
/* ===== SLIDER JS ===== */
const sliderImages = <?= json_encode($sliderImages) ?>;
let slideIndex = 0;
const sliderImgEl = document.getElementById('slider-img');
function showSlide(i){slideIndex=(i+sliderImages.length)%sliderImages.length;sliderImgEl.src=sliderImages[slideIndex];}
function nextSlide(){showSlide(slideIndex+1);}
function prevSlide(){showSlide(slideIndex-1);}
setInterval(nextSlide,5000);

/* ===== TERMÉKLAPOZÁS JS ===== */
const productsPerPage=4;
let productPage=0;
const productGrid=document.getElementById('product-grid');
const productCards=Array.from(productGrid.children);
function showProductPage(page){
    productPage=page;
    const start=page*productsPerPage,end=start+productsPerPage;
    productCards.forEach((card,i)=>{card.style.display=(i>=start&&i<end)?'flex':'none';});
}
function nextProducts(){const maxPage=Math.ceil(productCards.length/productsPerPage)-1;showProductPage(productPage<maxPage?productPage+1:0);}
function prevProducts(){const maxPage=Math.ceil(productCards.length/productsPerPage)-1;showProductPage(productPage>0?productPage-1:maxPage);}
showSlide(0);showProductPage(0);

/* ===== NAVBAR DROPDOWN JS ===== */
function toggleMenu(){document.querySelector('.nav-links').classList.toggle('show');}
function toggleCart(){document.querySelector('.cart-dropdown').classList.toggle('show');}
function toggleProfile(){document.querySelector('.profile-dropdown').classList.toggle('show');}
</script>
</body>
</html>