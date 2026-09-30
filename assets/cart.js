function addToCart(id) {
  fetch("/rivalsociety/cart/add.php", {
    method: "POST",
    credentials: "same-origin", // ✅ KEEP SESSION
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `product_id=${encodeURIComponent(id)}`
  })
  .then(res => res.text())
  .then(() => {
    alert("Added to cart");
  })
  .catch(err => {
    console.error("Add to cart failed", err);
    alert("Something went wrong");
  });
}

function buyNow(id) {
  fetch("/rivalsociety/cart/add.php", {
    method: "POST",
    credentials: "same-origin", // ✅ KEEP SESSION
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: `product_id=${encodeURIComponent(id)}&buy=1`
  })
  .then(res => res.text())
  .then(() => {
    window.location.href = "/rivalsociety/cart/view.php";
  })
  .catch(err => {
    console.error("Buy now failed", err);
    alert("Something went wrong");
  });
}

/* ❌ REMOVE FAKE BUTTON HANDLERS COMPLETELY */
/* THEY BREAK YOUR FLOW */
