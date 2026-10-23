# QuizMaster 🎯

[![PHP Version](https://img.shields.io/badge/PHP-8.3+-8892BF?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue?style=for-the-badge)](LICENSE)
[![Security](https://img.shields.io/badge/Security-Hardened-brightgreen?style=for-the-badge)](SECURITY.md)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)](docker-compose.yml)

QuizMaster is a secure, modern interactive web platform to create, share, and take custom quizzes on a variety of topics. Built with PHP 8.3+ featuring hardened security, prepared statements, bcrypt password hashing, CSRF protection, and modern PHP features.

## 🚀 Live Demo

[https://quizcraft.wasmer.app/](https://quizcraft.wasmer.app/)

## ✨ Features

- **🔐 Secure Authentication** - Bcrypt password hashing (cost 12), secure sessions, CSRF protection
- **🛡️ SQL Injection Prevention** - All queries use prepared statements with parameter binding
- **📝 Quiz Management** - Create, edit, delete quizzes with admin approval workflow
- **🤖 AI Quiz Generator** - Upload PDF documents, generate questions via Gemini AI
- **🏆 Leaderboard System** - Track scores, percentages, completion times
- **👥 User Management** - Admin panel for user/quiz management
- **📱 Responsive Design** - Mobile-friendly with dark mode support
- **🐳 Docker Ready** - Production-ready containerization

## 🛡️ Security Features

| Vulnerability | Status | Implementation |
|--------------|--------|----------------|
| SQL Injection | ✅ Fixed | Prepared statements with mysqli |
| Weak Password Hashing | ✅ Fixed | Bcrypt (cost 12) |
| CSRF Attacks | ✅ Fixed | Per-form CSRF tokens |
| XSS | ✅ Fixed | Input sanitization + CSP headers |
| Session Hijacking | ✅ Fixed | Secure, HttpOnly, SameSite cookies |
| Rate Limiting | ✅ Implemented | Login/register attempt limiting |
| File Upload | ✅ Secured | MIME validation, size limits |
| Security Headers | ✅ Implemented | CSP, X-Frame-Options, etc. |

See [SECURITY.md](SECURITY.md) for complete documentation.

## 💻 Tech Stack

- **Backend**: PHP 8.3+ (strict types, enums, match expressions, readonly classes)
- **Database**: MySQL 8.0+ / MariaDB 10.3+ (InnoDB, utf8mb4)
- **Frontend**: HTML5, CSS3 (CSS Variables), Vanilla JS
- **AI**: Google Gemini API integration
- **Deployment**: Docker, Docker Compose
- **Web Server**: Apache 2.4+ / Nginx

## 🚀 Quick Start

### Prerequisites

- PHP 8.3 or higher
- MySQL 8.0+ or MariaDB 10.3+
- Composer 2.x
- (Optional) Docker & Docker Compose

### Option 1: Docker (Recommended)

```bash
# Clone repository
git clone https://github.com/yourusername/QuizMaster.git
cd QuizMaster

# Start all services
docker-compose up -d

# Access application
# App: http://localhost:8080
# phpMyAdmin: http://localhost:8081
```

### Option 2: Manual Installation

```bash
# Clone repository
git clone https://github.com/yourusername/QuizMaster.git
cd QuizMaster

# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your database credentials

# Setup database
php setup.php

# Run migrations (if updating)
php update_db.php

# Start development server
php -S localhost:8000
```

## 📋 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | `localhost` |
| `DB_PORT` | Database port | `3306` |
| `DB_USERNAME` | Database username | `root` |
| `DB_PASSWORD` | Database password | *(required)* |
| `DB_NAME` | Database name | `quizmaster` |
| `APP_URL` | Application URL | `http://localhost` |

## 📁 Project Structure

```
QuizMaster/
├── admin/                 # Admin panel
│   ├── dashboard.php      # Admin dashboard
│   ├── manage_users.php   # User management
│   ├── manage_quizzes.php # Quiz management
│   ├── edit_quiz.php      # Quiz editor
│   ├── delete_quiz.php    # Quiz deletion
│   └── preview_quiz.php   # Quiz preview
├── src/
│   └── Features/
│       └── ModernPhpFeatures.php  # PHP 8.3 features demo
├── .github/workflows/     # CI/CD pipelines
├── logs/                  # Security & error logs
├── config.php             # Main configuration
├── security.php           # Security helpers
├── db.php                 # Database class
├── database_setup.sql     # Schema & migrations
├── setup.php              # Initial setup
├── update_db.php          # Migration runner
├── composer.json          # Dependencies
├── Dockerfile             # Container definition
├── docker-compose.yml     # Multi-container setup
├── .env.example           # Environment template
├── SECURITY.md            # Security documentation
├── MIGRATION_GUIDE.md     # Upgrade guide
└── *.php                  # Application pages
```

## 🧪 Testing & Quality

```bash
# Run static analysis
composer static-analysis

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Run tests
composer test
```

## 🔧 Development

### PHP 8.3 Features Used

- **Enums** - `UserRole`, `QuizStatus` with methods
- **Match Expressions** - Clean conditional logic
- **Readonly Classes** - Immutable DTOs
- **Constructor Property Promotion** - Concise classes
- **Union Types** - Flexible parameter types
- **Attributes** - Metadata for routing/middleware
- **Nullsafe Operator** - Safe property access
- **Strict Types** - `declare(strict_types=1)`

### Code Style

Follows PSR-12 with PHP_CodeSniffer. Run `composer cs-fix` to auto-fix.

## 📦 Deployment

### Production Checklist

- [ ] Copy `.env.example` to `.env` with production values
- [ ] Generate strong `APP_KEY` for session encryption
- [ ] Enable HTTPS (set `SESSION_SECURE=true`)
- [ ] Configure web server (Apache/Nginx) with provided configs
- [ ] Set up SSL certificates
- [ ] Configure log rotation for `logs/`
- [ ] Set up database backups
- [ ] Change default admin password
- [ ] Run `php update_db.php` to apply migrations
- [ ] Monitor `logs/security.log` for anomalies

### Docker Production

```bash
# Build production image
docker build -t quizmaster:prod .

# Run with production compose
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please follow PSR-12 coding standards and write tests for new features.

## 📄 License

This project is licensed under the Apache License 2.0 - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Team Members**: Shreyash, Vansh, And-Mann
- **Security**: Hardened following OWASP guidelines
- **Icons**: Unicode emoji for zero-dependency UI

## 📞 Support

- **Email**: contact@quizmaster.example.com
- **Issues**: [GitHub Issues](https://github.com/yourusername/QuizMaster/issues)
- **Security**: security@quizmaster.example.com (private disclosure)

---

**Built with ❤️ using PHP 8.3+** - Secure by default, modern by design.