<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
echo "<pre>SESSION ID: " . session_id() . "</pre>";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>THE RIVAL SOCIETY-homepage</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="cdnjs.cloudflare.com">
    <link rel="stylesheet" href="fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<body>

<header id="header">
  <div class="hero-media">
  <img src="assets/images/IMG-20251214-WA0010.jpg" alt="Hero image">
</div>

  <div class="header-inner">
    <center><h1 class="neon">THE RIVAL SOCIETY</h1>
    <div class="cont">
      <h4>Be your rival</h4>
    </div>
    </center>
    
  </div>
</header>
<div id="nav-wrapper">
<div class="bar">
  <div class="nav-item" data-target="home">
  Home
  </div>

  <div class="nav-item" data-target="store">
    Store
  </div>

  <div class="nav-item" data-target="about">
    About Us
  </div>
  <div class="nav-item" data-target="Join">
    Join The Society
  </div>
  <div class="nav-item" data-target="contact">
    Contact Us
  </div>
</div>
</div>
<main id="main">
  <!-- HOME SECTION -->
  <section id="home">
   <center><h2>Welcome to The Rival Society</h2>
    <p class="pra1">We were never meant to fit in.
We were built to stand apart.</p>
    <p class="pra1">

Rival Society is streetwear for the ones who move against the grain —</p>
    <p class="pra1">
the ones who turn pressure into power,</p>
    <p class="pra1">
silence into noise,</p>
    <p class="">
and struggle into style.
Opposition shaped us.
Resistance refined us.
    
Rivalry drives us.
    
Not against the world —
    
but against yourself.</p>
    
This is not fast fashion.
    
This is identity.
    
If you’re comfortable, this isn’t for you.
If you’re evolving,</p> 
<h3 class="hed3">Welcome Home</h3></center>
  </section>
<!-- ================= STORE SECTION ================= -->
<section id="store">
<h2 class="store-title">Store</h2>
<h3 class="series-title">Naruto Series</h3>

<div class="product-grid">

<?php
$sql = "SELECT * FROM products WHERE status='active' ORDER BY created_at DESC";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()):
?>

<div class="product-card">

<?php if ($row['is_new']): ?>
<span class="badge new">NEW</span>
<?php endif; ?>

<div class="image-box">
<img src="assets/images/<?= htmlspecialchars($row['image_front']) ?>" class="img-1">
<img src="assets/images/<?= htmlspecialchars($row['image_back']) ?>" class="img-2">
</div>

<h3><?= htmlspecialchars($row['name']) ?></h3>
<p class="price">₹<?= number_format($row['price'], 0) ?></p>

<p class="stock <?= $row['stock'] > 0 ? 'in' : 'out' ?>">
<?= $row['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
</p>

<center><a href="product.php?id=<?= $row['id'] ?>" class="buy-btn">
Buy Now
</a></center>


</div>

<?php endwhile; ?>

</div>
</section>
<!-- ================= END STORE ================= -->

  <!-- ABOUT SECTION -->
  <section id="about">
    <center><h2>About Us</h2>
    <p>Welcome to the ultimate spot for t-shirt fanatics! We're diving deep into the world of screen printing, DTG, and crafting the coolest wearable art. Here, you'll find new print designs, in-depth reviews of different printing techniques, and exclusive looks at our highly-anticipated limited editions. Don't just wear a shirt—wear a statement. 
      Never miss a drop!
      <h3 class="hed3">Ready to become your own rival?</h3></center></p>
  </section>
  <!-- JOIN SECTION -->
  <section id="Join">
    <h2>Join The Society</h2>
    <ul class="social-list">
  <li>
    <a href="https://www.instagram.com/official_rival_society?igsh=MThxdm12enJodW96Yg==" target="_blank">
      <i class="fab fa-instagram"></i>
      Instagram
    </a>
  </li>
  <li>
    <a href="https://whatsapp.com/channel/0029VbBHGFW6xCSSfKE05N0l" target="_blank">
      <i class="fab fa-whatsapp"></i>
      WhatsApp
    </a>
  </li>
  <li>
    <a href="https://x.com/TheRivalSociety" target="_blank">
      <i class="fab fa-x-twitter"></i>
      X (Twitter)
    </a>
  </li>
  <li>
    <a href="https://youtube.com/@rivalsociety.2025?si=7C7Te6mrSPm6mbiW" target="_blank">
      <i class="fab fa-youtube"></i>
      YouTube
    </a>
  </li>
  <li>
    <a href="https://www.threads.com/@official_rival_society" target="_blank">
      <i class="fab fa-threads"></i>
      Threads
    </a>
  </li>
</ul>
  </section>
<!-- CONTACT SECTION -->
  <section id="contact">
    <h2>Contact Us</h2>
  </section>
</main>
  
<!-- Floating Action Buttons -->
<div class="floating-icons">

    <div class="floating-actions">
    <a href="cart/view.php" class="float-btn">
        <i class="fas fa-shopping-cart"></i>
    </a>

    <?php if (isset($_SESSION['cust_user_id'])): ?>
    <a href="account/dashboard.php" class="float-btn">
        <i class="fas fa-user-circle"></i>
    </a>
<?php else: ?>
    <a href="account/login.php" class="float-btn">
        <i class="fas fa-user"></i>
    </a>
<?php endif; ?>

</div>

</div>


<script src="assets/script.js"></script>
</body>
</html>
