document.addEventListener("DOMContentLoaded", () => {
  const header   = document.getElementById("header");
  const bar      = document.querySelector(".bar");
  const sections = document.querySelectorAll("section");
  const navItems = document.querySelectorAll(".nav-item");

  if (!header || !bar) return;

  let lastScroll = window.scrollY;
  let stickyOffset = bar.offsetTop;

  // Recalculate sticky offset on window resize
  window.addEventListener("resize", () => {
    if (!bar.classList.contains("fixed")) {
      stickyOffset = bar.offsetTop;
    }
  }, { passive: true });

  window.addEventListener("scroll", () => {
    const currentScroll = window.scrollY;

    /* =========================
       HEADER COLLAPSE / EXPAND
    ========================= */
    if (currentScroll > lastScroll && currentScroll > 60) {
      header.classList.add("collapsed");
    } else if (currentScroll < lastScroll && currentScroll < 200) {
      header.classList.remove("collapsed");
    }

    lastScroll = currentScroll;

    /* =========================
       STICKY NAVBAR
    ========================= */
    if (currentScroll >= stickyOffset) {
      bar.classList.add("fixed");
    } else {
      bar.classList.remove("fixed");
    }

    /* =========================
       ACTIVE NAV ITEM
    ========================= */
    let currentSection = "";

    sections.forEach(section => {
      const sectionTop = section.offsetTop - 140;
      if (currentScroll >= sectionTop) {
        currentSection = section.id;
      }
    });

    navItems.forEach(item => {
      item.classList.toggle(
        "active",
        item.dataset.target === currentSection
      );
    });
  }, { passive: true });

  /* =========================
     NAV CLICK & KEYBOARD SMOOTH SCROLL
  ========================= */
  const scrollToSection = (item) => {
    const id = item.dataset.target;
    const target = document.getElementById(id);
    if (!target) return;

    if (header) {
      header.classList.remove("expanded");
    }

    const navHeight = bar ? bar.offsetHeight : 0;
    const y = target.getBoundingClientRect().top + window.pageYOffset - navHeight - 16;

    window.scrollTo({
      top: y,
      behavior: "smooth"
    });
  };

  navItems.forEach(item => {
    // Make focusable for keyboard accessibility
    if (!item.hasAttribute("tabindex")) {
      item.setAttribute("tabindex", "0");
      item.setAttribute("role", "button");
    }

    item.addEventListener("click", e => {
      e.preventDefault();
      scrollToSection(item);
    });

    item.addEventListener("keydown", e => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        scrollToSection(item);
      }
    });
  });

  /* =========================
     MOBILE TOUCH IMAGE TOGGLE
  ========================= */
  const imageBoxes = document.querySelectorAll(".image-box");
  imageBoxes.forEach(box => {
    box.addEventListener("click", () => {
      const img1 = box.querySelector(".img-1");
      const img2 = box.querySelector(".img-2");
      if (img1 && img2) {
        const isFlipped = img1.style.opacity === "0";
        img1.style.opacity = isFlipped ? "1" : "0";
        img2.style.opacity = isFlipped ? "0" : "1";
      }
    });
  });
});
