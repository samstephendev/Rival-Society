<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>THE RIVAL SOCIETY - Streetwear E-Commerce</title>
  <?php rs_critical_css(); ?>
  <link rel="stylesheet" href="<?= asset_url('/assets/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('/assets/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>

<header id="header">
  <div class="hero-media">
    <img src="<?= url('/assets/images/IMG-20251214-WA0010.jpg') ?>" alt="Rival Society Streetwear Brand Hero Banner" width="1440" height="500">
  </div>

  <div class="header-inner">
    <h1 class="neon">THE RIVAL SOCIETY</h1>
    <div class="cont">
      <h4>Be your rival</h4>
    </div>
  </div>
</header>

<div id="nav-wrapper">
  <nav class="bar" aria-label="Main Navigation">
    <div class="nav-item" data-target="home">Home</div>
    <div class="nav-item" data-target="store">Store</div>
    <div class="nav-item" data-target="about">About Us</div>
    <div class="nav-item" data-target="Join">Join The Society</div>
    <div class="nav-item" data-target="contact">Contact Us</div>
  </nav>
</div>

<main id="main">
  <!-- HOME SECTION -->
  <section id="home">
    <div class="content-wrap">
      <h2>Welcome to The Rival Society</h2>
      <p class="pra1">We were never meant to fit in.<br>We were built to stand apart.</p>
      <p class="pra1">Rival Society is streetwear for the ones who move against the grain —</p>
      <p class="pra1">the ones who turn pressure into power,</p>
      <p class="pra1">silence into noise,</p>
      <p>and struggle into style.<br>Opposition shaped us.<br>Resistance refined us.<br><br>Rivalry drives us.<br><br>Not against the world —<br><br>but against yourself.</p>
      <p>This is not fast fashion.<br><br>This is identity.<br><br>If you’re comfortable, this isn’t for you.<br>If you’re evolving,</p> 
      <h3 class="hed3">Welcome Home</h3>
    </div>
  </section>

  <!-- ================= STORE SECTION ================= -->
  <section id="store">
    <h2 class="store-title">Store</h2>
    <h3 class="series-title">Naruto Series</h3>

    <div class="product-grid">
    <?php
    try {
        $sql = "SELECT id, name, price, image_front, image_back, stock, status, is_new FROM products WHERE status='active' ORDER BY created_at DESC";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()):
    ?>
      <article class="product-card">
        <?php if (!empty($row['is_new'])): ?>
          <span class="badge new">NEW</span>
        <?php endif; ?>

        <a href="<?= url('/product.php?id=' . (int)$row['id']) ?>" class="product-image-link" aria-label="View <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?> details">
          <div class="image-box">
            <img src="<?= url('/assets/images/' . htmlspecialchars($row['image_front'], ENT_QUOTES, 'UTF-8')) ?>" class="img-1" alt="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?> - Front View" loading="lazy" width="400" height="400">
            <img src="<?= url('/assets/images/' . htmlspecialchars($row['image_back'], ENT_QUOTES, 'UTF-8')) ?>" class="img-2" alt="<?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?> - Back View" loading="lazy" width="400" height="400">
          </div>
        </a>

        <h3>
          <a href="<?= url('/product.php?id=' . (int)$row['id']) ?>" class="product-title-link">
            <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </h3>
        <p class="price">₹<?= number_format((float)$row['price'], 2) ?></p>

        <p class="stock <?= (int)$row['stock'] > 0 ? 'in' : 'out' ?>">
          <?= (int)$row['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
        </p>

        <a href="<?= url('/product.php?id=' . (int)$row['id']) ?>" class="buy-btn">
          Buy Now
        </a>
      </article>
    <?php
        endwhile;
    } catch (mysqli_sql_exception $e) {
        echo "<p style='color:#aaa;'>Unable to load products right now.</p>";
        error_log("Store query error: " . $e->getMessage());
    }
    ?>
    </div>
  </section>
  <!-- ================= END STORE ================= -->

  <!-- ABOUT SECTION -->
  <section id="about">
    <div class="content-wrap">
      <h2>About Us</h2>
       <h3 class="hed3">Ready to become your own rival?</h3>
    </div>
  </section>

  <!-- JOIN SECTION -->
  <section id="Join">
    <div class="content-wrap">
      <h2>Join The Society</h2>
      <ul class="social-list">
        <li>
          <a href="https://www.instagram.com/official_rival_society?igsh=MThxdm12enJodW96Yg==" target="_blank" rel="noopener">
            <i class="fab fa-instagram"></i> Instagram
          </a>
        </li>
        <li>
          <a href="https://whatsapp.com/channel/0029VbBHGFW6xCSSfKE05N0l" target="_blank" rel="noopener">
            <i class="fab fa-whatsapp"></i> WhatsApp
          </a>
        </li>
        <li>
          <a href="https://x.com/TheRivalSociety" target="_blank" rel="noopener">
            <i class="fab fa-x-twitter"></i> X (Twitter)
          </a>
        </li>
        <li>
          <a href="https://youtube.com/@rivalsociety.2025?si=7C7Te6mrSPm6mbiW" target="_blank" rel="noopener">
            <i class="fab fa-youtube"></i> YouTube
          </a>
        </li>
        <li>
          <a href="https://www.threads.com/@official_rival_society" target="_blank" rel="noopener">
            <i class="fab fa-threads"></i> Threads
          </a>
        </li>
      </ul>
    </div>
  </section>

  <!-- CONTACT SECTION -->
  <section id="contact">
    <div class="content-wrap">
      <h2>Contact Us</h2>
      <p>Reach us on our official social channels or get in touch with our team for customer support and order inquiries.</p>
      <div class="contact-cards">
        <div class="contact-card-item">
          <i class="fas fa-envelope"></i>
          <h3>Customer Support</h3>
          <p>support@rivalsociety.com</p>
        </div>
        <div class="contact-card-item">
          <i class="fas fa-truck-fast"></i>
          <h3>Shipping & Orders</h3>
          <p>11111-11111</p>
        </div>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> THE RIVAL SOCIETY. All rights reserved.</p>
  <p>Streetwear Crafted for the Unconventional.</p>
</footer>
  
<!-- Floating Action Buttons -->
<div class="floating-icons">
  <div class="floating-actions">
    <a href="<?= url('/cart/view.php') ?>" class="float-btn" title="View Cart" aria-label="View Shopping Cart">
      <i class="fas fa-shopping-cart"></i>
    </a>

    <?php if (isset($_SESSION['cust_user_id'])): ?>
      <a href="<?= url('/account/dashboard.php') ?>" class="float-btn" title="My Account" aria-label="Customer Account Dashboard">
        <i class="fas fa-user-circle"></i>
      </a>
    <?php else: ?>
      <a href="<?= url('/account/login.php') ?>" class="float-btn" title="Member Login" aria-label="Customer Account Login">
        <i class="fas fa-user"></i>
      </a>
    <?php endif; ?>
  </div>
</div>

<script src="<?= asset_url('/assets/script.js') ?>"></script>
</body>
</html>
