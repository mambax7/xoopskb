# 🎯 Getting Started with XOOPS

> Everything you need to install, configure, and start building with XOOPS CMS.

---

## 📋 Prerequisites

Before installing XOOPS, ensure you have:

- **PHP 8.2+**
- **MySQL 5.7+** or MariaDB 10.3+
- **Web Server**: Apache with mod_rewrite or Nginx
- **Composer** (for modern development)

---

## 🗂️ Section Contents

### Installation
- [[Installation/Installation|Complete Installation Guide]]
- [[Installation/Server-Requirements|Server Requirements]]
- [[Installation/Upgrading-XOOPS|Upgrading from Previous Versions]]

### Configuration
- [[Configuration/Basic-Configuration|Basic Configuration]]
- [[Configuration/System-Settings|System Settings]]
- [[Configuration/Security-Configuration|Security Configuration]]
- [[Configuration/Performance-Optimization|Performance Optimization]]

### First Steps
- [[First-Steps/Admin-Panel-Overview|Admin Panel Overview]]
- [[First-Steps/Creating-Your-First-Page|Creating Your First Page]]
- [[First-Steps/Installing-Modules|Installing Modules]]
- [[First-Steps/Managing-Users|Managing Users]]

---

## 🚀 Quick Start (5 minutes)

### Step 1: Download XOOPS
```bash
git clone https://github.com/XOOPS/XoopsCore25.git
cd XoopsCore25
```

### Step 2: Create Database
```sql
CREATE DATABASE xoops CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'xoops'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON xoops.* TO 'xoops'@'localhost';
FLUSH PRIVILEGES;
```

### Step 3: Configure Web Server
Point your web server document root to the `htdocs` directory.

### Step 4: Run Installation Wizard
Navigate to `http://your-domain.com/install/` and follow the wizard.

### Step 5: Secure Your Installation
```bash
# After installation, remove or protect the install directory
rm -rf htdocs/install
# Or rename it
mv htdocs/install htdocs/install.bak
```

---

## 📚 Next Steps

After installation, we recommend:

1. **[[First-Steps/Admin-Panel-Overview|Explore the Admin Panel]]** - Understand the control center
2. **[[Configuration/Security-Configuration|Configure Security]]** - Protect your installation
3. **[[First-Steps/Installing-Modules|Install Essential Modules]]** - Extend functionality
4. **[[../03-Module-Development/Tutorials/Hello-World-Module|Build Your First Module]]** - Start developing

---

## 🔗 Related Sections

- [[../02-Core-Concepts/Core-Concepts|Core Concepts]] - Understand XOOPS architecture
- [[../08-Troubleshooting/Troubleshooting|Troubleshooting]] - Common installation issues
- [[../05-XMF-Framework/XMF-Framework|XMF Framework]] - Modern module development

---

#xoops #getting-started #installation #configuration
