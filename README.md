# 🎮 Gaming Store Inventory System

ระบบจัดการสต็อกสินค้าร้านขายอุปกรณ์เกมมิ่ง พัฒนาด้วย PHP 8.2 + MariaDB 10.6 + Docker

## 🚀 วิธีรันโปรเจค

```bash
# 1. Build และรัน Docker containers
docker-compose up -d --build

# 2. เปิดเว็บแอป
# http://localhost:8001

# 3. เปิด phpMyAdmin (ถ้าต้องการดูฐานข้อมูล)
# http://localhost:8080
# Login: root / rootpassword
```

## 📁 โครงสร้างไฟล์

```
├── Dockerfile              # PHP 8.2 Apache image
├── docker-compose.yml      # 3 services: PHP, MariaDB, phpMyAdmin
├── schema.sql              # Database schema + seed data
├── db.php                  # PDO database connection
├── index.php               # Dashboard page
├── products.php            # Products CRUD page
├── sales.php               # Sales management page
├── assets/css/style.css    # Dark theme design system
└── README.md
```

## 📄 หน้าเว็บ

| หน้า | URL | หน้าที่ |
|------|-----|---------|
| Dashboard | `/index.php` | สรุปสถิติ + ยอดขายล่าสุด |
| Products | `/products.php` | เพิ่ม / แก้ไข / ลบสินค้า |
| Sales | `/sales.php` | สร้างรายการขาย + ประวัติ |

## 🛠️ เทคโนโลยี

- **PHP 8.2** + PDO (Prepared statements)
- **MariaDB 10.6** (Auto-initialized schema)
- **Docker Compose 3.8**
- **Dark Theme UI** (Inter font, glow effects, animations)

## 🔒 Security

- PDO Prepared Statements (SQL Injection protection)
- `htmlspecialchars()` (XSS protection)
- Transaction-safe sales with stock validation
