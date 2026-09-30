<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

// Log in as demo user automatically for this debug runner
$userRes = $conn->query("SELECT id, name FROM cust_user WHERE email = 'demo@gmail.com' LIMIT 1");
if ($user = $userRes->fetch_assoc()) {
    $_SESSION['cust_user_id'] = (int)$user['id'];
    $_SESSION['cust_user_name'] = $user['name'];
}

// Ensure cart has an item
if (empty($_SESSION['cart'])) {
    $p = $conn->query("SELECT id, name, price, image_front, stock FROM products WHERE status = 'active' LIMIT 1")->fetch_assoc();
    if ($p) {
        $_SESSION['cart']['p_' . $p['id'] . '_M'] = [
            'id' => (int)$p['id'],
            'name' => $p['name'],
            'price' => (float)$p['price'],
            'image' => $p['image_front'],
            'size' => 'M',
            'quantity' => 1,
            'stock' => (int)$p['stock']
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Checkout Reproduction Diagnostic</title>
</head>
<body>
<h1>Running Checkout Reproduction Diagnostic...</h1>
<iframe id="checkout-frame" src="../cart/checkout.php" style="width: 1000px; height: 800px; border: 1px solid #ccc;"></iframe>
<pre id="output" style="background: #111; color: #0f0; padding: 20px; font-size: 14px; max-height: 500px; overflow: auto;"></pre>

<script>
const output = document.getElementById('output');
function log(msg) {
    output.textContent += msg + "\n";
    console.log(msg);
}

const frame = document.getElementById('checkout-frame');
frame.onload = function() {
    log("=== CHECKOUT IFRAME LOADED ===");
    try {
        const doc = frame.contentDocument || frame.contentWindow.document;
        const win = frame.contentWindow;

        // 1. Find form and button
        const forms = doc.querySelectorAll('form');
        log("Total forms found on page: " + forms.length);
        forms.forEach((f, idx) => {
            log(`Form #${idx}: action="${f.getAttribute('action')}", method="${f.getAttribute('method')}", class="${f.className}"`);
        });

        const form = doc.querySelector('form.checkout-form') || doc.querySelector('form');
        if (!form) {
            log("ERROR: No form found!");
            return;
        }

        // 2. Inspect Button
        const btn = doc.querySelector('.checkout-btn') || form.querySelector('button');
        if (btn) {
            const rect = btn.getBoundingClientRect();
            const cs = win.getComputedStyle(btn);
            log("\n--- BUTTON INSPECTION ---");
            log("Tag: " + btn.tagName);
            log("Type: " + btn.type);
            log("Text: " + btn.innerText.trim());
            log("Inside Form: " + form.contains(btn));
            log("form attribute: " + btn.getAttribute('form'));
            log("onclick: " + btn.getAttribute('onclick'));
            log("disabled: " + btn.disabled);
            log("Dimensions: width=" + rect.width + ", height=" + rect.height + ", top=" + rect.top + ", left=" + rect.left);
            log("Computed Style: display=" + cs.display + ", visibility=" + cs.visibility + ", pointer-events=" + cs.pointerEvents + ", z-index=" + cs.zIndex);

            // Check what element is at the center of the button (overlay detection)
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const elemAtPoint = doc.elementFromPoint(cx, cy);
            log("Element at button center point (" + cx + ", " + cy + "): " + (elemAtPoint ? (elemAtPoint.tagName + '.' + elemAtPoint.className + '#' + elemAtPoint.id) : 'NONE'));
            if (elemAtPoint && !btn.contains(elemAtPoint) && elemAtPoint !== btn) {
                log("WARNING: Element overlaying button: " + elemAtPoint.outerHTML.substring(0, 100));
            } else {
                log("Overlay check: Button is direct target (no overlay blocking).");
            }
        } else {
            log("ERROR: Checkout button not found!");
        }

        // 3. Form Elements and Initial Validity
        log("\n--- FORM ELEMENTS INITIAL STATE ---");
        const elements = [...form.elements];
        log("Total form elements: " + elements.length);
        elements.forEach(e => {
            const r = e.getBoundingClientRect();
            const cs = win.getComputedStyle(e);
            const isVisible = (cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0);
            log(`- Name: "${e.name}", Tag: ${e.tagName}, Type: ${e.type}, Required: ${e.required}, Pattern: "${e.pattern || ''}", Value: "${e.value}", Visible: ${isVisible} (${Math.round(r.width)}x${Math.round(r.height)}), Valid: ${e.checkValidity()} (${e.validationMessage || 'OK'})`);
        });

        log("\n--- FORM INITIAL VALIDITY CHECK ---");
        log("form.checkValidity(): " + form.checkValidity());
        const initialInvalid = elements.filter(e => !e.checkValidity()).map(e => e.name + ': ' + e.validationMessage);
        log("Invalid elements count: " + initialInvalid.length);
        initialInvalid.forEach(i => log("  -> " + i));

        // 4. Fill form with valid data
        log("\n--- FILLING FORM WITH VALID DATA ---");
        if (doc.getElementById('full_name')) doc.getElementById('full_name').value = "John Doe";
        if (doc.getElementById('email')) doc.getElementById('email').value = "demo@gmail.com";
        if (doc.getElementById('phone')) doc.getElementById('phone').value = "9876543210";
        if (doc.getElementById('address')) doc.getElementById('address').value = "123 Street Road, Apartment 4B";
        if (doc.getElementById('city')) doc.getElementById('city').value = "Bengaluru";
        if (doc.getElementById('state')) doc.getElementById('state').value = "Karnataka";
        if (doc.getElementById('pincode')) doc.getElementById('pincode').value = "560001";

        log("After filling:");
        log("form.checkValidity(): " + form.checkValidity());
        const filledInvalid = elements.filter(e => !e.checkValidity()).map(e => e.name + ': ' + e.validationMessage);
        log("Invalid elements after filling: " + JSON.stringify(filledInvalid));

        // 5. Test reportValidity
        log("form.reportValidity(): " + form.reportValidity());

        // 6. Test Click event
        log("\n--- SIMULATING BUTTON CLICK ---");
        let submitFired = false;
        form.addEventListener('submit', (evt) => {
            submitFired = true;
            log("Form 'submit' event FIRED successfully!");
        });

        // Record scroll position before click
        const scrollBefore = win.scrollY;
        log("Scroll position before click: " + scrollBefore);

        btn.click();

        setTimeout(() => {
            const scrollAfter = win.scrollY;
            log("Scroll position after click: " + scrollAfter);
            log("Did form submit event fire? " + submitFired);
            log("Current frame URL: " + win.location.href);
            log("\n=== DIAGNOSTIC COMPLETE ===");
            document.title = "DIAGNOSTIC_FINISHED";
        }, 1500);

    } catch (err) {
        log("EXCEPTION: " + err.message + "\n" + err.stack);
    }
};
</script>
</body>
</html>
