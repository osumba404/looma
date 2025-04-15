# Looma

A dynamic web platform where users can register, verify their phone, pay an activation fee via M-Pesa, play games to earn points, and withdraw earnings. Built with PHP, MySQL, Bootstrap, and JavaScript, featuring a modern, responsive, and professional front-end design.

## Features
- **User Registration**: Sign up with full name, username, phone, email (optional), password, and unique referral code.
- **Phone Verification**: Receive and verify a 6-digit SMS code (Africa’s Talking API).
- **M-Pesa Payment**: Pay activation fee via M-Pesa STK Push (Daraja API).
- **Account Activation**: Activate account post-payment.
- **Login System**: Log in with username or phone.
- **Dashboard**: Displays logged-in user’s points, wallet balance, referrals, and recent activities.
- **Password Management**: Update password with strong validation (8+ characters, uppercase, number, special character).
- **Referrals**: Share referral link, track referred users, and view referral count.
- **Achievements**: View user achievements and leaderboard rankings (top 10 by points).
- **Modern UI**: Bootstrap 5, Font Awesome, Poppins font, gradient buttons, animations, and mobile-friendly sidebar/bottom nav.

## Tech Stack
- **Backend**: PHP, MySQL
- **Frontend**: HTML, CSS (Bootstrap 5), JavaScript
- **APIs**:
  - Africa’s Talking SMS (sandbox for testing)
  - M-Pesa Daraja (STK Push, sandbox)
- **Database**: MySQL (users, wallet, transactions, verification_codes; optional: points)

## Project Structure
```
looma/
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
├── index.php                 # User dashboard (points, balance, referrals, activities)
├── register.php              # Registration form
├── login.php                 # Login form
├── verify.php                # Phone verification form
├── payment.php               # Payment initiation
├── settings.php              # Password update
├── referrals.php             # Referral link and referred users
├── achievements.php          # Achievements and leaderboard
├── wallet.php                # Earnings dashboard (placeholder)
├── games.php                 # Games page (placeholder)
├── questions.php             # Quizes page (placeholder)
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
   cd looma
   ```

2. **Configure Database**:
   - Create a MySQL database (`looma`).
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
         referred_by VARCHAR(8),
         is_verified BOOLEAN DEFAULT FALSE,
         is_activated BOOLEAN DEFAULT FALSE,
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
         FOREIGN KEY (referred_by) REFERENCES users(referral_code)
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
         description VARCHAR(255),
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

     -- Optional: For points tracking
     CREATE TABLE points (
         point_id INT AUTO_INCREMENT PRIMARY KEY,
         user_id INT,
         points INT DEFAULT 0,
         FOREIGN KEY (user_id) REFERENCES users(user_id)
     );
     ```
   - Update `includes/db.php` with your credentials:
     ```php
     $host = 'localhost';
     $db = 'looma';
     $user = 'your_db_user';
     $pass = 'your_db_password';
     ```

3. **Configure APIs**:
   - **Africa’s Talking**:
     - Get sandbox credentials.
     - Update `api/register.php` with API key and username.
   - **M-Pesa Daraja**:
     - Get sandbox credentials (consumer key, secret, shortcode, passkey).
     - Update `includes/mpesa.php` with credentials and callback URL.

4. **Run the Server**:
   ```bash
   php -S localhost:8000
   ```

5. **Access the Site**:
   - Open `http://localhost:8000/index.php`.
   - Register, verify phone, pay, log in, or access dashboard.

## Usage
- **Dashboard** (`index.php`): View points (defaults to 0 if `points` table missing), wallet balance, referral count, and recent transactions (empty if `transactions` table missing).
- **Register** (`register.php`): Enter details → receive SMS code.
- **Verify** (`verify.php`): Enter code → proceed to payment.
- **Pay** (`payment.php`): Initiate M-Pesa STK Push → activate account.
- **Login** (`login.php`): Access dashboard.
- **Settings** (`settings.php`): Update password with strong validation.
- **Referrals** (`referrals.php`): Copy referral link, view referred users’ names, usernames, and join dates.
- **Achievements** (`achievements.php`): View earned achievements and top 10 leaderboard (requires `points` table).
- **Wallet** (`wallet.php`): Placeholder for earnings overview.
- **Games** (`games.php`): Placeholder for gameplay.
- **Quizes** (`questions.php`): Placeholder for quizzes.

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
  - Test dashboard (`index.php`) after login to confirm user details, stats, and no errors.
- **Sandbox**:
  - Test SMS with Africa’s Talking sandbox.
  - Simulate M-Pesa payments with Daraja sandbox.

## Known Issues
- **Missing Tables**:
  - `points`: Defaults to 0 points if missing. Create table for tracking.
  - `transactions`: Shows “No recent activity” if missing. Create for activity logs.
- Non-JSON responses may occur if:
  - `includes/db.php` credentials are incorrect.
  - API files are misplaced.
  - PHP isn’t executing (check server logs).
- `games.php` and `questions.php` are placeholders (no functionality).

## Future Enhancements
- Implement `games.php` for gameplay mechanics.
- Build `questions.php` for quiz functionality.
- Enhance `wallet.php` with balance, withdrawal UI, and transaction history.
- Add `withdraw.php` for M-Pesa withdrawals.
- Implement `logout.php` to clear sessions.
- Add CSRF tokens for form security.
- Style `verify.php` and `payment.php` to match `register.php` and `login.php`.
- Add achievements table for `achievements.php` full functionality.

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