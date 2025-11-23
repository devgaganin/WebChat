
# WebChat – Anonymous Chat Module System  
Lightweight • Fast • Secure • PHP + MySQL  
Created by **devgagan**

---

## 📌 Introduction

**WebChat** is a simple and efficient **anonymous chat system** built using plain **PHP**, **MySQL**, and **AJAX**.  
The goal is to offer a fast, lightweight chat experience without requiring heavy frameworks or complex server setups.

This project is ideal for:

- Anonymous chat rooms  
- Temporary or private group chats  
- Learning PHP-based real-time communication  
- Embedding chat inside existing websites  
- Lightweight hosting (works perfectly even on shared hosting)

---

## 🔐 Default Admin Credentials

After installation, login to the admin panel using:

```

Username: admin
Password: password

````

⚠ **Important:** Change the admin password immediately for security.

---

## ⭐ Features

- ✔ Anonymous chatting  
- ✔ Admin panel included  
- ✔ Simple, clean UI  
- ✔ Secure file upload system  
- ✔ Realtime messaging via AJAX polling  
- ✔ Auto-cleanup system (via cron)  
- ✔ Works on shared hosting & VPS  
- ✔ Minimal setup — no frameworks required  

---

# 📂 File & Folder Structure

| Path               | Description |
|--------------------|-------------|
| `assets/`          | JavaScript, CSS, images |
| `index.php`        | Home / login redirect |
| `chat.php`         | Main chat interface |
| `auth.php`         | Authentication logic |
| `api.php`          | Backend for sending/receiving messages |
| `config.php`       | Database configuration |
| `register.php`     | New user registration |
| `login.php`        | User login |
| `logout.php`       | Logout controller |
| `files.php`        | Secure file upload logic |
| `serve.php`        | Serve uploaded files safely |
| `cron_cleanup.php` | Cleans old chats & unused uploads |
| `spychat.sql`      | SQL schema required for installation |

---

# 🧰 Installation Guide (Local / Any Server)

## 1. Clone the Project

```bash
git clone https://github.com/devgaganin/WebChat.git
cd WebChat
````

---

## 2. Create Your Database

Login to MySQL:

```bash
mysql -u root -p
```

Create database:

```sql
CREATE DATABASE webchat_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 3. Import the Chat Tables

```bash
mysql -u root -p webchat_db < spychat.sql
```

---

## 4. Configure Database Login

Open `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'webchat_db');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

Save the file.

---

## 5. Run the Application

Open your browser:

```
http://localhost/WebChat/
```

Login using:

```
admin / password
```

🎉 WebChat is now ready!

---

# 🌐 Deployment on Shared Hosting (Hostinger)

This project is fully compatible with shared hosting like **Hostinger, Bluehost, GoDaddy**, etc.

---

## ✔ Step 1: Upload Files

1. Open **hPanel → Files → File Manager**
2. Enter the `public_html` folder
3. Upload the entire WebChat project
4. Extract the ZIP (if uploaded as one)

---

## ✔ Step 2: Create a MySQL Database

Navigate:

**hPanel → Databases → MySQL Databases**

Create:

* Database
* Username
* Password

Hostinger will give you info like:

```
DB Host: mysql.hostinger.com  
DB Name: u00000000_chat  
DB User: u00000000_user  
DB Pass: ******
```

---

## ✔ Step 3: Import spychat.sql

1. Open phpMyAdmin
2. Select your new database
3. Click **Import**
4. Upload `spychat.sql`
5. Click **Go**

---

## ✔ Step 4: Update config.php

Modify:

```php
define('DB_HOST', 'mysql.hostinger.com');
define('DB_NAME', 'u00000000_chat');
define('DB_USER', 'u00000000_user');
define('DB_PASS', 'yourpassword');
```

---

## ✔ Step 5: Visit Your Domain

```
https://your-domain.com/
```

Login → `admin / password`
Start chatting! 🎉

---

# 💎 VPS Deployment Guide (Ubuntu 20/22)

Works on:

* Hostinger VPS
* DigitalOcean Droplet
* Linode
* Contabo
* AWS EC2

---

## 🖥 Step 1: Update System

```bash
sudo apt update && sudo apt upgrade -y
```

---

## 🖥 Step 2: Install Apache, PHP & MySQL

```bash
sudo apt install apache2 php php-mysqli php-json php-gd php-curl php-zip unzip mysql-server -y
```

Enable Apache:

```bash
sudo systemctl enable apache2
sudo systemctl start apache2
```

---

## 🛢 Step 3: Create Database

```bash
sudo mysql
```

Inside:

```sql
CREATE DATABASE webchat_db;
CREATE USER 'webchat_user'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT ALL PRIVILEGES ON webchat_db.* TO 'webchat_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 📂 Step 4: Upload Project to Server

Option 1: Upload via SFTP
Option 2: Upload via SSH:

```bash
cd /var/www/html/
sudo rm -rf *
sudo wget https://your-download-link/WebChat.zip
sudo unzip WebChat.zip
sudo chown -R www-data:www-data /var/www/html/
```

---

## 🗄 Step 5: Import SQL

```bash
mysql -u webchat_user -p webchat_db < spychat.sql
```

---

## 🔧 Step 6: Configure config.php

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'webchat_db');
define('DB_USER', 'webchat_user');
define('DB_PASS', 'StrongPassword123!');
```

---

## ⏱ Step 7: Setup Cron Job (Recommended)

```bash
crontab -e
```

Add:

```
0 * * * * /usr/bin/php /var/www/html/cron_cleanup.php
```

This removes old chat logs automatically.

---

# 🔒 Security Recommendations

* Change default admin password immediately
* Use HTTPS (SSL)
* Disable directory listing
* Limit file upload types (configured in `files.php`)
* Keep database credentials private
* Run cleanup cron frequently
* Avoid using weak MySQL passwords

---

# 🧩 Customization Ideas

You can extend WebChat with:

* Multi-room chat
* Private messaging
* WebSocket real-time chat
* Admin analytics dashboard
* User avatars / profiles
* Emotes, GIFs, stickers
* Add dark mode
* Add device-based login restrictions

---

# 🤝 Contributing

Pull requests are welcome!
If you'd like to improve UI/UX, performance, or add features — feel free to contribute.

---

# 📝 License

Please include your preferred license (MIT recommended).

---

Thank you for using **WebChat – Anonymous Chat Module System**.
Built with ❤️ by **[Gagan](https://devgagan.in/)**.

If you need help join us on [Telegram](https://t.me/team_spy_pro)
