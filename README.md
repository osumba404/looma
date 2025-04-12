# Looma

A dynamic web platform where users can register, verify their phone, pay an activation fee via M-Pesa, play games to earn points, and withdraw earnings. Built with PHP, MySQL, Bootstrap, and JavaScript, featuring a modern and professional front-end design.

## Features
- **User Registration**: Sign up with full name, username, phone, email (optional), and password.
- **Phone Verification**: Receive and verify a 6-digit SMS code (Africa’s Talking API).
- **M-Pesa Payment**: Pay activation fee via M-Pesa STK Push (Daraja API).
- **Account Activation**: Activate account post-payment.
- **Login System**: Log in with username or phone.
- **Landing Page**: Enticing hero, features, how-it-works, and trust sections.
- **Modern UI**: Gradient buttons, Inter font, animations, and responsive design.

## Tech Stack
- **Backend**: PHP, MySQL
- **Frontend**: HTML, CSS (Bootstrap 5), JavaScript
- **APIs**:
  - Africa’s Talking SMS (sandbox for testing)
  - M-Pesa Daraja (STK Push, sandbox)
- **Database**: MySQL (users, wallet, transactions, verification_codes)

## Project Structure
```
promotional-website/
├── api/
│   ├── register.php           # User registration
│   ├── login.php             # User login
│   ├── verify_phone.php      # Phone verification
│   ├── initiate_payment.php  # M-Pesa STK Push
│   ├── mpesa_callback.php    # M-Pesa callback handler
│   ├── check_activation.php  # Check account activation
├── assets/
│   ├── css/
│   │   └── style.css         # Shared styles
│   ├── js/
│   │   └── main.js           # Frontend logic
├── includes/
│   ├── db.php                # Database connection
│   ├── mpesa.php             # M-Pesa API helper
├── index.php                 # Landing page
├── register.php              # Registration form
├── login.php                 # Login form
├── verify.php                # Phone verification form
├── payment.php               # Payment initiation
├── wallet.php                # Post-activation dashboard (placeholder)
├── README.md
```

## Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Web server (Apache, Nginx, or `php -S`)
- Africa’s Talking sandbox credentials
- M-Pesa Daraja sandbox credentials

## Setup Instructions
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/galaxie-dev/affiliate-mkt.git
   cd promotional-website
   ```

2. **Configure Database**:
   - Create a MySQL database (`promotional_website`).
   - Import the schema:
     ```sql
     CREATE TABLE users (
         user_id INT AUTO_INCREMENT PRIMARY KEY,
         full_name VARCHAR(100) NOT NULL,
         username VARCHAR(50) UNIQUE NOT NULL,
         phone VARCHAR(15) UNIQUE NOT NULL,
         email VARCHAR(100),
         password VARCHAR(255) NOT NULL,
         referral_code VARCHAR(8) UNIQUE,
         is_verified BOOLEAN DEFAULT FALSE,
         is_activated BOOLEAN DEFAULT FALSE,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     );

     CREATE TABLE wallet (
         wallet_id INT AUTO_INCREMENT PRIMARY KEY,
         user_id INT,
         balance DECIMAL(10,2) DEFAULT 0.00,
         FOREIGN KEY (user_id) REFERENCES users(user_id)
     );

     CREATE TABLE transactions (
         transaction_id INT AUTO_INCREMENT PRIMARY KEY,
         user_id INT,
         amount DECIMAL(10,2) NOT NULL,
         type ENUM('deposit', 'withdrawal') NOT NULL,
         status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
         mpesa_code VARCHAR(50),
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         FOREIGN KEY (user_id) REFERENCES users(user_id)
     );

     CREATE TABLE verification_codes (
         code_id INT AUTO_INCREMENT PRIMARY KEY,
         user_id INT,
         code VARCHAR(6) NOT NULL,
         status ENUM('pending', 'verified', 'expired') DEFAULT 'pending',
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         expires_at TIMESTAMP NOT NULL,
         FOREIGN KEY (user_id) REFERENCES users(user_id)
     );
     ```
   - Update `includes/db.php` with your database credentials:
     ```php
     $host = 'localhost';
     $db = 'promotional_website';
     $user = 'your_db_user';
     $pass = 'your_db_password';
     ```

3. **Configure APIs**:
   - **Africa’s Talking**:
     - Get sandbox credentials.
     - Update `api/register.php` with your API key and username.
   - **M-Pesa Daraja**:
     - Get sandbox credentials (consumer key, secret, shortcode, passkey).
     - Update `includes/mpesa.php` with credentials and callback URL.

4. **Run the Server**:
   ```bash
   php -S localhost:8000
   ```

5. **Access the Site**:
   - Open `http://localhost:8000/index.php`.
   - Register, verify phone, pay, or log in.

## Usage
- **Landing Page**: `index.php` welcomes users with a hero, features, and steps.
- **Register**: `register.php` → enter details → receive SMS code.
- **Verify**: `verify.php` → enter code → proceed to payment.
- **Pay**: `payment.php` → initiate M-Pesa STK Push → activate account.
- **Login**: `login.php` → access `wallet.php` (dashboard stub).
- **Wallet**: `wallet.php` (placeholder for future features like games).

## Testing
- **APIs**:
  ```bash
  curl -X POST http://localhost:8000/api/register.php \
  -H "Content-Type: application/json" \
  -d '{"full_name":"John Doe","username":"johndoe","phone":"+254712345678","email":"john@example.com","password":"secure123"}'
  ```
- **Front-End**:
  - Navigate to `http://localhost:8000/register.php`.
  - Use DevTools (Network tab) to verify JSON responses.
- **Sandbox**:
  - Test SMS with Africa’s Talking sandbox.
  - Simulate M-Pesa payments with Daraja sandbox.

## Known Issues
- Non-JSON responses (e.g., HTML errors) may occur if:
  - `includes/db.php` credentials are incorrect.
  - API files are misplaced.
  - PHP isn’t executing (check server logs).

## Future Enhancements
- Build `games/trivia.php` for gameplay.
- Enhance `wallet.php` with balance and withdrawal UI.
- Add CSRF tokens for security.
- Style `verify.php` and `payment.php` to match `register.php` and `login.php`.

## Contributing
1. Fork the repo.
2. Create a branch (`git checkout -b feature/your-feature`).
3. Commit changes (`git commit -m "Add your feature"`).
4. Push (`git push origin feature/your-feature`).
5. Open a pull request.

## License
MIT License. See [LICENSE](LICENSE) for details.

---

Built with 💻 by Collins & Evans.
