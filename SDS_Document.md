# Software Design Specification (SDS)
## 08600 Services and Quotations WordPress Plugin

**Version:** 1.0  
**Date:** December 2024  
**Author:** Thando Hlophe (Kayise IT)  
**Document Status:** Draft

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Overview](#system-overview)
3. [Architecture Design](#architecture-design)
4. [Database Design](#database-design)
5. [Module Specifications](#module-specifications)
6. [User Interface Design](#user-interface-design)
7. [Security Considerations](#security-considerations)
8. [Performance Requirements](#performance-requirements)
9. [Testing Strategy](#testing-strategy)
10. [Deployment and Maintenance](#deployment-and-maintenance)
11. [Technical Requirements](#technical-requirements)

---

## Executive Summary

The 08600 Services and Quotations WordPress Plugin is a comprehensive courier and logistics management system designed to handle service management, customer management, waybill creation, quotation generation, and delivery tracking. The plugin integrates seamlessly with WordPress and provides a complete solution for courier companies to manage their operations.

### Key Features
- Service management and cataloging
- Customer database management
- Waybill creation and tracking
- Quotation generation and management
- Delivery scheduling and tracking
- Multi-country and city support
- PDF generation capabilities
- Advanced pricing calculations

---

## System Overview

### Purpose
The plugin serves as a complete courier and logistics management solution within WordPress, enabling businesses to:
- Manage courier services and pricing
- Handle customer information and relationships
- Create and track waybills
- Generate and manage quotations
- Schedule and monitor deliveries
- Calculate shipping costs based on mass, volume, and distance

### Scope
The system encompasses the following functional areas:
1. **Service Management**: CRUD operations for courier services
2. **Customer Management**: Customer database with contact information
3. **Waybill System**: Multi-step waybill creation with item tracking
4. **Quotation System**: Automated quotation generation and management
5. **Delivery Management**: Scheduling and tracking of deliveries
6. **Geographic Management**: Countries and cities for shipping routes
7. **Pricing Engine**: Complex pricing calculations based on multiple factors

### System Context
- **Platform**: WordPress 5.0+
- **Database**: MySQL/MariaDB (WordPress database)
- **Frontend**: WordPress Admin Interface
- **Styling**: Tailwind CSS
- **PDF Generation**: DOMPDF library
- **JavaScript**: jQuery for AJAX operations

---

## Architecture Design

### High-Level Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                           │
├─────────────────────────────────────────────────────────────┤
│                08600 Services Plugin                        │
├─────────────────┬─────────────────┬─────────────────────────┤
│   Admin Layer   │   Business      │    Data Access Layer   │
│                 │   Logic Layer   │                        │
├─────────────────┼─────────────────┼─────────────────────────┤
│ • Menu System   │ • Service Mgmt  │ • Database Operations  │
│ • UI Components │ • Customer Mgmt │ • Query Optimization   │
│ • Form Handling │ • Waybill Logic │ • Data Validation      │
│ • PDF Generation│ • Quotation     │                        │
│                 │   Engine       │                        │
└─────────────────┴─────────────────┴─────────────────────────┘
```

### Design Patterns
1. **MVC Pattern**: Separation of concerns between data, business logic, and presentation
2. **Singleton Pattern**: Database connection management
3. **Factory Pattern**: PDF generation and form creation
4. **Observer Pattern**: Event-driven waybill status updates

### File Structure
```
courier-finance-plugin/
├── 08600-services-quotations.php    # Main plugin file
├── includes/
│   ├── class-database.php           # Database operations
│   ├── class-plugin.php             # Plugin initialization
│   ├── admin-menu.php               # Menu registration
│   ├── admin-pages.php              # Page handlers
│   ├── commons.php                  # Common utilities
│   ├── services/                    # Service management
│   ├── customers/                   # Customer management
│   ├── waybill/                     # Waybill system
│   ├── quotations/                  # Quotation system
│   ├── deliveries/                  # Delivery management
│   └── countries/                   # Geographic data
├── assets/
│   ├── css/                         # Stylesheets
│   └── js/                          # JavaScript files
├── vendor/                          # Composer dependencies
└── fonts/                           # Custom fonts
```

---

## Database Design

### Database Schema Overview

The plugin creates 12 custom tables in the WordPress database:

#### 1. kit_services
**Purpose**: Store courier services and their descriptions
```sql
- id (INT, Primary Key)
- name (VARCHAR(255))
- description (TEXT)
- image (VARCHAR(255))
```

#### 2. kit_customers
**Purpose**: Customer information management
```sql
- id (INT, Primary Key)
- cust_id (MEDIUMINT(10), Unique)
- name (VARCHAR(255))
- surname (VARCHAR(255))
- cell (VARCHAR(20))
- email (VARCHAR(100))
- address (TEXT)
- created_at (DATETIME)
```

#### 3. kit_waybills
**Purpose**: Core waybill data with comprehensive shipping information
```sql
- id (INT, Primary Key)
- direction_id (INT, Foreign Key)
- delivery_id (INT, Foreign Key)
- customer_id (MEDIUMINT(10), Foreign Key)
- approval (ENUM: 'approved','pending','cancelled','rejected','completed')
- waybill_no (INT, Unique)
- product_invoice_number (VARCHAR(50))
- product_invoice_amount (DECIMAL(10,2))
- item_length, item_width, item_height (DECIMAL(10,2))
- total_mass_kg, total_volume (DECIMAL(10,2))
- mass_charge, volume_charge (DECIMAL(10,2))
- charge_basis (VARCHAR(20))
- vat_number (VARCHAR(50))
- warehouse (VARCHAR(50))
- miscellaneous (LONGTEXT)
- include_sad500, include_sadc, return_load (TINYINT(1))
- tracking_number (VARCHAR(50))
- status (ENUM: 'pending','quoted','paid','completed')
- created_by, last_updated_by (INT)
- created_at, last_updated_at (DATETIME)
```

#### 4. kit_waybill_items
**Purpose**: Individual items within waybills
```sql
- id (INT, Primary Key)
- waybillno (INT, Foreign Key)
- item_name (VARCHAR(255))
- quantity (INT)
- unit_price (DECIMAL(10,2))
- unit_mass, unit_volume (DECIMAL(10,2))
- total_price (DECIMAL(10,2))
- created_at (DATETIME)
```

#### 5. kit_quotations
**Purpose**: Quotation management and tracking
```sql
- id (INT, Primary Key)
- delivery_id, waybill_id (INT, Foreign Keys)
- waybillNo (VARCHAR(255))
- customer_id (MEDIUMINT(10), Foreign Key)
- subtotal, vat_amount, total (DECIMAL(10,2))
- quotation_notes (TEXT)
- status (ENUM: 'pending','sent','accepted','declined')
- created_by, last_updated_by (INT)
- created_at, last_updated_at (DATETIME)
```

#### 6. kit_deliveries
**Purpose**: Delivery scheduling and tracking
```sql
- id (INT, Primary Key)
- delivery_reference (VARCHAR(100))
- direction_id (INT, Foreign Key)
- dispatch_date (DATE)
- truck_number (VARCHAR(50))
- status (ENUM: 'scheduled','in_transit','delivered')
- created_by (INT)
- created_at (DATETIME)
```

#### 7. kit_invoices
**Purpose**: Invoice generation and management
```sql
- id (INT, Primary Key)
- waybill_id, customer_id (INT, Foreign Keys)
- invoice_number (VARCHAR(50))
- invoice_date, due_date (DATE)
- subtotal, vat_amount, total (DECIMAL(10,2))
- status (ENUM: 'unpaid','paid','overdue')
- created_by, last_updated_by (INT)
- created_at, last_updated_at (DATETIME)
```

#### 8. Geographic Tables
- **kit_operating_countries**: Country information
- **kit_operating_cities**: City information with country relationships
- **kit_shipping_directions**: Shipping routes between cities
- **kit_shipping_rate_types**: Rate type definitions
- **kit_shipping_rates_mass**: Mass-based pricing
- **kit_shipping_rates_volume**: Volume-based pricing
- **kit_shipping_dedicated_truck_rates**: Specialized truck pricing
- **kit_discounts**: Discount management

### Relationships
- Customers → Waybills (1:Many)
- Waybills → Waybill Items (1:Many)
- Waybills → Quotations (1:Many)
- Waybills → Invoices (1:Many)
- Deliveries → Waybills (1:Many)
- Countries → Cities (1:Many)
- Cities → Shipping Directions (Many:Many)

---

## Module Specifications

### 1. Service Management Module
**Location**: `includes/services/services-functions.php`

**Responsibilities**:
- CRUD operations for courier services
- Service catalog management
- Service image handling

**Key Functions**:
- `insert_service()`: Add new services
- `service_name_exists()`: Validate service uniqueness
- `plugin_services_list_page()`: Display service catalog

### 2. Customer Management Module
**Location**: `includes/customers/customers-functions.php`

**Responsibilities**:
- Customer database management
- Customer search and filtering
- Customer waybill history

**Key Functions**:
- `customer_dashboard()`: Main customer interface
- `edit_customer()`: Customer information editing
- `view_customer_waybills()`: Customer waybill history

### 3. Waybill Management Module
**Location**: `includes/waybill/waybill-functions.php`

**Responsibilities**:
- Multi-step waybill creation
- Waybill status management
- Item tracking and calculations

**Key Functions**:
- `waybill_page()`: Main waybill creation interface
- `KIT_Waybills::waybillView()`: Waybill viewing
- AJAX handlers for dynamic operations

**Waybill Creation Process**:
1. **Step 1**: Basic information and customer selection
2. **Step 2**: Item details and dimensions
3. **Step 2a**: Additional item information
4. **Step 3**: Pricing calculations
5. **Step 4**: Final review and submission
6. **Step 5**: Confirmation and PDF generation

### 4. Quotation Module
**Location**: `includes/quotations/quotations-functions.php`

**Responsibilities**:
- Automated quotation generation
- Quotation status tracking
- PDF quotation generation

**Key Functions**:
- `plugin_quotations_list_page()`: Quotation listing
- `quotation_view_page()`: Quotation details
- `quotation_form()`: Quotation creation form

### 5. Delivery Management Module
**Location**: `includes/deliveries/deliveries-functions.php`

**Responsibilities**:
- Delivery scheduling
- Status tracking
- Route management

### 6. Geographic Management Module
**Location**: `includes/countries/opc-functions.php`

**Responsibilities**:
- Country and city management
- Shipping route configuration
- Rate calculation based on geography

---

## User Interface Design

### Design Principles
- **WordPress Integration**: Seamless integration with WordPress admin
- **Responsive Design**: Mobile-friendly interface using Tailwind CSS
- **User Experience**: Intuitive navigation and clear visual hierarchy
- **Accessibility**: WCAG 2.1 compliance

### Admin Menu Structure
```
WordPress Admin
├── 08600 Services (Main Dashboard)
├── Quotations
│   └── View Quotation (Hidden)
├── 08600 Waybill
│   ├── Create Waybills
│   └── View Waybill (Hidden)
└── 08600 Customers
    ├── All Customers
    ├── Edit Customer (Hidden)
    └── Customer Waybills (Hidden)
```

### Key UI Components

#### 1. Dashboard Cards
- Service management cards
- Customer overview
- Waybill statistics
- Recent quotations

#### 2. Multi-Step Forms
- Waybill creation wizard
- Customer registration
- Service configuration

#### 3. Data Tables
- Customer listing with search
- Waybill management table
- Quotation tracking table

#### 4. Modal Dialogs
- Quick customer lookup
- Item addition forms
- Confirmation dialogs

### Styling Framework
- **Primary Framework**: Tailwind CSS 3.4.17
- **Custom Styles**: Austin CSS for branding
- **Icons**: WordPress Dashicons
- **Typography**: Inter font family

---

## Security Considerations

### Data Protection
1. **Input Sanitization**: All user inputs sanitized using WordPress functions
   - `sanitize_text_field()` for text inputs
   - `sanitize_textarea_field()` for textareas
   - `sanitize_email()` for email addresses

2. **SQL Injection Prevention**: Prepared statements and WordPress database functions
   - `$wpdb->prepare()` for all database queries
   - Parameterized queries for dynamic data

3. **Nonce Verification**: CSRF protection for all forms
   - `wp_create_nonce()` for form generation
   - `wp_verify_nonce()` for form validation

4. **Capability Checks**: WordPress role-based access control
   - `edit_pages` capability for general access
   - `manage_options` for administrative functions

### Authentication & Authorization
- WordPress user authentication
- Role-based access control
- Session management through WordPress

### Data Validation
- Client-side validation with JavaScript
- Server-side validation with PHP
- Database constraint validation

---

## Performance Requirements

### Response Time
- Page load time: < 3 seconds
- AJAX operations: < 1 second
- PDF generation: < 5 seconds

### Scalability
- Support for 10,000+ customers
- Handle 1,000+ waybills per month
- Efficient database queries with proper indexing

### Optimization Strategies
1. **Database Optimization**
   - Proper indexing on frequently queried columns
   - Query optimization and caching
   - Pagination for large datasets

2. **Frontend Optimization**
   - Minified CSS and JavaScript
   - Lazy loading for large tables
   - AJAX for dynamic content loading

3. **Caching**
   - WordPress object caching
   - Database query caching
   - Static asset caching

---

## Testing Strategy

### Unit Testing
- Individual function testing
- Database operation testing
- Input validation testing

### Integration Testing
- WordPress integration testing
- Database integration testing
- AJAX functionality testing

### User Acceptance Testing
- End-to-end workflow testing
- User interface testing
- Cross-browser compatibility testing

### Test Scenarios
1. **Service Management**
   - Add new service
   - Edit existing service
   - Delete service
   - Duplicate service name validation

2. **Customer Management**
   - Customer registration
   - Customer search
   - Customer editing
   - Customer waybill history

3. **Waybill Creation**
   - Complete waybill workflow
   - Item addition and removal
   - Pricing calculations
   - PDF generation

4. **Quotation System**
   - Quotation generation
   - Status updates
   - PDF quotation creation

---

## Deployment and Maintenance

### Installation Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer for dependency management

### Installation Process
1. Upload plugin files to `/wp-content/plugins/courier-finance-plugin/`
2. Activate plugin through WordPress admin
3. Database tables created automatically
4. Configure initial settings

### Backup Strategy
- Database backup before updates
- Plugin file backup
- Configuration backup

### Update Process
1. Deactivate plugin
2. Backup current installation
3. Upload new version
4. Reactivate plugin
5. Run database migrations if needed

### Monitoring
- Error logging through WordPress
- Performance monitoring
- User activity tracking

---

## Technical Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **WordPress**: 5.0 or higher
- **Memory**: Minimum 256MB PHP memory limit
- **Upload Size**: 10MB minimum for file uploads

### Dependencies
```json
{
  "require": {
    "dompdf/dompdf": "^3.1"
  },
  "devDependencies": {
    "autoprefixer": "^10.4.20",
    "postcss": "^8.5.3",
    "tailwindcss": "^3.4.17"
  }
}
```

### Browser Support
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### File Permissions
- Plugin directory: 755
- Plugin files: 644
- Upload directory: 755

---

## Conclusion

The 08600 Services and Quotations WordPress Plugin provides a comprehensive solution for courier and logistics management within the WordPress ecosystem. The modular architecture ensures maintainability and scalability, while the robust database design supports complex business operations.

The plugin successfully integrates modern web technologies with WordPress standards, providing a professional-grade solution for courier companies to manage their operations efficiently.

---

**Document Version History**
- v1.0 (December 2024): Initial SDS document

**Approval**
- [ ] Technical Lead Review
- [ ] Project Manager Approval
- [ ] Client Sign-off 