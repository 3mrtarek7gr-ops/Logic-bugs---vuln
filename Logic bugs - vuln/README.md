# Lab: Excessive Trust in Client-Side Controls

A small, self-contained "online store" built for **local security training**.
It reproduces a classic business-logic vulnerability: the backend trusts a
**client-supplied `price` parameter** when adding an item to the cart,
instead of re-fetching the authoritative price from the database.

> ⚠️ This application is **intentionally vulnerable**. Run it only on a local
> XAMPP instance, never expose it to the internet.

---

## 1. Stack

- PHP 8+
- MySQL / MariaDB (via XAMPP)
- HTML / CSS / Bootstrap 5 (loaded from a CDN link purely for styling; the
  app's logic has no internet dependency — if you're fully offline the pages
  still work, just unstyled)
- No frameworks, no JavaScript price logic, no external services

---

## 2. Project structure

```
lab2-client-control/
├── index.php      Product listing (requires login)
├── login.php       Login form + handler
├── logout.php      Destroys session
├── product.php      Product detail + "Add to cart" form
├── cart.php         POST /cart endpoint (VULNERABLE) + cart view
├── checkout.php      Checkout / order placement + "LAB SOLVED" banner
├── config.php       DB connection + helpers
├── style.css         Styling
├── setup.sql          Schema + seed data
└── README.md          This file
```

---

## 3. XAMPP setup

1. Install/start **XAMPP** and start the **Apache** and **MySQL** modules
   from the XAMPP Control Panel.
2. Copy the entire `lab2-client-control/` folder into your XAMPP web root:
   - Windows default: `C:\xampp\htdocs\lab2-client-control\`
3. Confirm PHP 8+ is bundled with your XAMPP install (Control Panel → Apache
   → Config, or run `C:\xampp\php\php.exe -v`).

---

## 4. Database setup

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Click **Import** (or use the **SQL** tab and paste the file contents).
3. Select `setup.sql` from this project folder and run it.
   - This creates the `lab2_client_control` database, all tables, and seeds:
     - User `wiener` / password `peter`, with **$50.00** store credit
     - Product **"Lightweight l33t leather jacket"** at the real price of
       **$1337.00**
     - Two harmless filler products
4. `config.php` uses the default XAMPP MySQL credentials
   (`root` / empty password, `localhost`). Edit `config.php` if your MySQL
   root user has a password set.

---

## 5. Running the lab

Visit:

```
http://localhost/lab2-client-control/index.php
```

You'll be redirected to `login.php` if you're not authenticated.

> **Note on the endpoint path:** the lab brief describes the vulnerable
> request as `POST /cart`. Since this project is plain PHP (no front
> controller / URL rewriting) served directly by Apache, the physical file
> and therefore the URL is `cart.php`. Functionally it is the exact same
> endpoint — `POST /lab2-client-control/cart.php` — and the vulnerable logic
> is identical to what the brief specifies. Everywhere below, "POST /cart"
> means this endpoint.

---

## 6. Burp Suite configuration

1. Open **Burp Suite** → make sure the embedded/system browser (or your
   configured browser with the Burp CA cert + proxy settings) is routed
   through Burp's proxy (default `127.0.0.1:8080`).
2. In Burp, go to **Proxy → Intercept** and make sure interception is **on**
   (or use **HTTP history** to find the request afterwards — either works).
3. Because this app is plain HTTP on `localhost`, no extra TLS/CA setup is
   required beyond the normal Burp proxy configuration.

---

## 7. Exploitation walkthrough

### Step 1 — Log in
Go to `http://localhost/lab2-client-control/login.php` and log in as:
```
username: wiener
password: peter
```
You'll see a green **Credit: $50.00** badge in the navbar.

### Step 2 — Try the "honest" purchase (confirms it fails)
1. Click into **Lightweight l33t leather jacket** (real price **$1337.00**).
2. Click **Add to cart**, then go to **Cart**, then **Proceed to checkout**,
   then **Confirm & pay**.
3. You'll see:
   > Insufficient store credit. Order total is $1337.00 but your balance is
   > only $50.00.

   This confirms the normal purchase path correctly fails.
4. Go back to the cart and click **Remove** to clear it before continuing.

### Step 3 — Intercept the add-to-cart request
1. Turn Burp Proxy interception **on**.
2. Go to the product page for the jacket again and click **Add to cart**.
3. Burp will intercept a request like:
   ```
   POST /lab2-client-control/cart.php HTTP/1.1
   Host: localhost
   Content-Type: application/x-www-form-urlencoded

   productId=1&quantity=1&price=1337.00
   ```
4. Right-click the intercepted request → **Send to Repeater** (then you can
   forward or drop the intercepted copy — the point is to edit and resend
   from Repeater).

### Step 4 — Tamper with the price
1. In **Repeater**, find the body parameter:
   ```
   price=1337.00
   ```
2. Change it to:
   ```
   price=1
   ```
3. Click **Send**.
4. The server responds with a redirect (`302 Found` to `product.php...`) —
   there is no error. The backend accepted `price=1` without question and
   inserted a `cart_items` row with `price = 1.00`.

### Step 5 — Verify the cart shows the tampered price
Open (or refresh) the cart:
```
http://localhost/lab2-client-control/cart.php
```
You'll see the jacket listed with:
- **Real price:** $1337.00
- **Cart price:** **$1.00** (flagged with a red **"tampered"** badge)
- **Cart total:** $1.00

### Step 6 — Complete checkout
1. Click **Proceed to checkout**. The order total shown is **$1.00**.
2. Click **Confirm & pay**.
3. Because `$1.00 < $50.00` store credit, the purchase **succeeds**, and the
   page displays a big green banner:

   ```
   ✅ LAB SOLVED
   ```

   along with the order confirmation, the price actually charged ($1.00),
   and your remaining store credit ($49.00).

---

## 8. What "Excessive Trust in Client-Side Controls" means

This vulnerability class occurs when a server treats a value that the
**client fully controls** — a hidden form field, a query parameter, a POST
body field, a cookie, etc. — as if it were **authoritative, trusted data**.

In this lab, `product.php` renders a hidden `<input name="price">` field
containing the real price, purely for display/prefill convenience. But
because HTTP requests are just text that any client (a browser, curl, or
Burp Repeater) can freely edit before it reaches the server, that hidden
field provides **zero actual security**. It's a UI convenience, not a
security boundary.

The server has no way to distinguish "the browser sent this value because
my HTML told it to" from "an attacker manually crafted this value." The
*only* thing the server can trust is data it looks up itself, from its own
database, using an identifier (like `productId`) that it then validates.

---

## 9. Why trusting price from the client is insecure

- **Any state used to make a financial or authorization decision must be
  re-derived or re-validated server-side.** Price, discount percentage,
  account balance, user role, item ownership — all of these are examples of
  values that must never be taken at face value from client input.
- In this lab, `cart.php`'s `POST` handler reads `$_POST['price']` and
  inserts it **directly** into `cart_items.price`, without ever comparing it
  to `products.price`. The real price ($1337.00) sits right there in the
  database, unused, while the attacker-supplied price ($1.00) becomes the
  price the customer is actually charged at checkout.
- This is exactly the kind of bug that automated scanners often miss (the
  request "succeeds" and returns a normal-looking response) but that a
  manual tester with Burp Repeater finds immediately by tampering with
  parameters that "shouldn't" be attacker-controlled but functionally are.
- The business impact is direct financial loss: an attacker can purchase
  arbitrary, arbitrarily expensive goods for a fraction of a cent (or free,
  by sending `price=0`).

---

## 10. How a secure application should validate the price server-side

The fix is to **never let the client dictate price**. The server already
knows the price — it's in the `products` table. `cart.php` should:

1. Accept only an *identifier* from the client (`productId` and `quantity`).
2. Look up the product server-side:
   ```php
   $stmt = $pdo->prepare('SELECT id, price FROM products WHERE id = ?');
   $stmt->execute([$productId]);
   $product = $stmt->fetch();
   ```
3. **Discard** any `price` field sent by the client entirely, and use the
   database value instead:
   ```php
   $insert = $pdo->prepare(
       'INSERT INTO cart_items (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)'
   );
   $insert->execute([$user['id'], $product['id'], $quantity, $product['price']]);
   //                                                          ^^^^^^^^^^^^^^^^
   //                                            always the server-known price
   ```
4. Apply the same principle at **checkout time** too, as defense in depth:
   re-verify each cart line's price against the current `products.price`
   immediately before charging, in case prices changed or a row was ever
   inserted incorrectly.
5. More generally: treat every piece of client input as **hostile by
   default**. Only identifiers (IDs, SKUs) should flow from client to
   server for anything that affects money, permissions, or ownership. Any
   value the server needs for a security- or billing-relevant decision
   should be **looked up**, not **received**.

This exact fix is intentionally *commented out* (but not applied) inside
`cart.php` in this lab, right next to the vulnerable line, so you can
compare the vulnerable and secure implementations side by side.

---

## 11. Resetting the lab

To reset store credit / clear orders and carts, just re-run `setup.sql`
(it drops and recreates all tables), or run:

```sql
USE lab2_client_control;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM cart_items;
UPDATE users SET store_credit = 50.00 WHERE username = 'wiener';
```
