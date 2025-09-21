# Supplier CRUD System

This document describes the complete CRUD (Create, Read, Update, Delete) system for managing suppliers in the Axontis CRM.

## Features

### 1. Supplier List (`/crm/suppliers`)
- **Search**: Search suppliers by name, code, email, or contact person
- **Filter**: Filter suppliers by status (Active/Inactive)
- **Sort**: Sort by name or code (ascending/descending)
- **Pagination**: Navigate through multiple pages of suppliers
- **Actions**: View, Edit, Toggle Status, Delete suppliers

### 2. Add Supplier (`/crm/suppliers/create`)
- Complete form with all supplier fields
- Required fields: Name and Code
- Optional fields: Contact information, address, notes
- Status toggle (Active by default)

### 3. Edit Supplier (`/crm/suppliers/{id}/edit`)
- Pre-populated form with existing supplier data
- Same validation as create form
- Unique code validation (excluding current supplier)

### 4. View Supplier (`/crm/suppliers/{id}`)
- Detailed view of supplier information
- Related orders and devices
- Quick stats and actions
- Record timestamps

## Database Structure

The suppliers table includes:
- `id` - Primary key
- `name` - Supplier name (required)
- `code` - Unique supplier code (required)
- `email` - Contact email
- `phone` - Contact phone
- `address` - Street address
- `city` - City
- `postal_code` - Postal/ZIP code
- `country` - Country
- `contact_person` - Primary contact person
- `website` - Company website
- `notes` - Additional notes
- `is_active` - Status flag (default: true)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

## API Endpoints

### Resource Routes
- `GET /crm/suppliers` - List suppliers (with search, filter, sort)
- `GET /crm/suppliers/create` - Show create form
- `POST /crm/suppliers` - Store new supplier
- `GET /crm/suppliers/{id}` - Show supplier details
- `GET /crm/suppliers/{id}/edit` - Show edit form
- `PUT/PATCH /crm/suppliers/{id}` - Update supplier
- `DELETE /crm/suppliers/{id}` - Delete supplier

### Additional Routes
- `PATCH /crm/suppliers/{id}/toggle-status` - Toggle active status

## Navigation

The Suppliers menu item is located in the main sidebar navigation with a truck icon. It provides direct access to the supplier list page.

## Validation Rules

### Create/Update Supplier
- **name**: required, string, max 255 characters
- **code**: required, string, max 50 characters, unique
- **email**: optional, valid email, max 255 characters
- **phone**: optional, string, max 20 characters
- **address**: optional, string, max 500 characters
- **city**: optional, string, max 100 characters
- **postal_code**: optional, string, max 20 characters
- **country**: optional, string, max 100 characters
- **contact_person**: optional, string, max 255 characters
- **website**: optional, valid URL, max 255 characters
- **notes**: optional, string, max 1000 characters
- **is_active**: boolean

## Security Features

- All routes are protected by authentication middleware
- CSRF protection on all forms
- Input validation and sanitization
- Confirmation dialogs for destructive actions

## Usage Examples

### Creating a Supplier
1. Navigate to `/crm/suppliers`
2. Click "Add Supplier" button
3. Fill in required fields (Name, Code)
4. Add optional information as needed
5. Click "Create Supplier"

### Searching Suppliers
1. Use the search box to find suppliers by name, code, email, or contact person
2. Use the status filter to show only active or inactive suppliers
3. Click column headers to sort by name or code

### Managing Supplier Status
- Use the toggle button in the list to quickly activate/deactivate suppliers
- Inactive suppliers are visually distinguished with red status badges

### Deleting Suppliers
- Suppliers with existing orders or devices cannot be deleted
- Confirmation dialog prevents accidental deletions

## Test Data

The system includes a seeder (`SupplierSeeder`) that creates sample suppliers for testing:
- TechCorp Solutions (TECH001) - Active
- Global Electronics Ltd (GLOB002) - Active  
- Mobile Parts Express (MOB003) - Active
- Inactive Supplier Co (INAC004) - Inactive

Run the seeder with: `php artisan db:seed --class=SupplierSeeder`