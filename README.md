# NourTech ERP (AMC Factory)

NourTech ERP is a robust, production-grade Enterprise Resource Planning system built with the Laravel framework. It provides a comprehensive suite of modules designed to streamline operations, manage inventory, facilitate financial transactions, and improve overall factory productivity.

## 📦 ERP Modules

The system is organized into several interconnected modules. Data integrity and Single Responsibility Principles (SRP) are maintained using the Service layer architecture.

1. **Inventory & Warehouse Management**
   - Track Raw Materials and Finished Goods.
   - Monitor real-time stock levels across multiple warehouses.
   - Record manual stock adjustments and view complete item movement history.
2. **Purchasing & Suppliers**
   - Manage Suppliers and their financial balances.
   - Create, edit, and approve Purchase Invoices (Draft to Finalized flow).
   - Automatically add received stock to warehouses upon approval.
3. **Sales & Customers**
   - Manage Customers and track their balances.
   - Create and approve Sales Invoices.
   - Automatically deduct stock quantities upon invoice approval.
4. **Production & Bill of Materials (BOM)**
   - Define exact formulations (BOM) for finished products (how much raw material is consumed per unit).
   - Execute production orders that seamlessly consume raw materials and add finished goods to inventory.
5. **Returns Module**
   - Support both Sales Returns and Purchase Returns.
   - Revert stock changes automatically (add back to inventory for sales returns; deduct for purchase returns).
   - Adjust customer/supplier financial balances or issue direct treasury refunds.
6. **Treasury & Accounting**
   - Manage Cash/Bank accounts.
   - Record income (receipts) and expenses (payments).
   - Link financial transactions to specific Customers or Suppliers.
7. **Settings**
   - Configure global system settings such as Company Name, Logo, Currency, Timezone, and Default Warehouse/Treasury endpoints.

---

## 🛠 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 / MariaDB 10.3 or higher
- Node.js & NPM (for frontend assets compilation if needed)

---

## 🚀 Installation & Setup

Follow these steps to get the ERP up and running in your local or production environment.

**1. Clone the repository and install dependencies**
```bash
git clone <repository-url>
cd nourtech-erp
composer install
npm install && npm run build
```

**2. Environment Configuration**
Copy the `.env.example` file to create your own `.env` file.
```bash
cp .env.example .env
```
Update your `.env` file with your database credentials and app details:
```env
APP_NAME="NourTech ERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nourtech_erp
DB_USERNAME=root
DB_PASSWORD=secret
```

**3. Generate Application Key**
```bash
php artisan key:generate
```

**4. Run Database Migrations**
This will build the schema for all modules (Users, Inventory, Sales, Purchases, Production, Treasury, Settings, and Returns).
```bash
php artisan migrate
```

**5. Storage Link**
Link the storage directory to the public path, which is required for displaying the Company Logo and uploaded assets.
```bash
php artisan storage:link
```

*(Optional) Database Seeding*
If you have a seeder file to generate dummy data for testing (warehouses, products, etc.), you can run:
```bash
php artisan db:seed
```

**6. Start the Server**
```bash
php artisan serve
```
Access the application at `http://localhost:8000`.

---

## 💡 Key Technical Features

- **Dark Mode & Responsive UI:** Fully accessible dark theme utilizing Bootstrap 5 CSS variables (`data-bs-theme="dark"`).
- **Service Pattern Architecture:** Business logic is decoupled from Controllers and strictly placed into Service classes (`SalesService`, `InventoryService`, etc.) to adhere to clean code principles.
- **Database Transactions:** All financial and inventory updates are wrapped in `DB::transaction()` blocks to prevent data corruption during simultaneous or failed operations.
- **Caching:** Expensive reference queries (e.g., getting the list of active warehouses or customers) are cached using `Cache::remember` to reduce database load.

---

## 📄 License

The NourTech ERP framework is proprietary software. All rights reserved.