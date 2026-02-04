# Business Expense & Invoice Manager

A modern, web-based application to manage business expenses, generate invoices, track payments, and analyze financial data for SMBs.

## Features
- **Authentication**: Role-based access (Admin, Staff, Client).
- **Dashboard**: Real-time financial overview with stats and recent activities.
- **Invoice Management**: Create, customize, and print/download professional invoices.
- **Advanced Features**:
  - **Multi-currency**: Automatic exchange rates via API.
  - **Bulk Billing**: Generate invoices for multiple clients at once.
  - **Client Portal**: Public links for clients to view invoices without login.
  - **SMTP Notifications**: Automated email delivery for invoices (configurable).
  - **Data Export**: Export financial reports to CSV.
- **Expense Tracking**: Categorized expenses with bill/receipt uploads.
- **Client Management**: Track client history and outstanding balances.
- **Payment Tracking**: Partial payments support and manual reconciliation.
- **Reports**: Visual trends and category-wide analysis using Chart.js.
- **Security**: PDO prepared statements, CSRF protection, and input sanitization.

## Tech Stack
- **Backend**: Core PHP 8.x
- **Database**: MySQL (PDO)
- **Frontend**: Bootstrap 5, FontAwesome 6, Chart.js
- **Design**: Modern Inter font, clean indigo-based theme.

## Installation
1. Clone or copy the folder to your local server (e.g., `C:/xampp/htdocs/expense-manage`).
2. Create a database named `expense_manage` in MySQL.
3. Import the `database.sql` file into the database.
4. Update `config/config.php` with your database credentials and `BASE_URL`.
5. Login with default credentials:
   - **Email**: `admin@example.com`
   - **Password**: `password`

## Security Considerations
- Uses `password_hash()` (bcrypt) for secure password storage.
- All database queries use PDO prepared statements to prevent SQL Injection.
- Input is sanitized using `htmlspecialchars()` and trim.
- Simple CSRF token implementation included for forms.
