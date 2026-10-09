# Dormitory Management System - Documentation

## Overview

This directory contains comprehensive documentation for the Dormitory Management System, including database design, entity relationship diagrams, and user manuals.

## Documents Available

### 1. Database Entity Relationship Diagram (ERD)
- **File:** `database-erd.pdf`
- **Format:** A3 Landscape, Crow's Foot Notation
- **Size:** ~369 KB
- **Contents:**
  - Visual representation of all 16 database entities
  - Complete field listings with data types
  - Relationship mappings with cardinality
  - Primary and foreign key indicators
  - Comprehensive relationship legend

### 2. Database Design Document
- **File:** `database-design.pdf`
- **Format:** A4 Portrait
- **Size:** ~443 KB
- **Contents:**
  - Detailed table definitions with all columns
  - Data types and constraints
  - Business rules for each entity
  - Indexing strategy
  - Relationship documentation
  - Calculation formulas and business logic
  - DBMS specifications (MySQL 8.0+)

### 3. User Manual
- **File:** `user-manual.pdf`
- **Format:** A4 Portrait
- **Size:** ~392 KB
- **Contents:**
  - Complete user guide for all three roles (Owner, Employee, Tenant)
  - Step-by-step instructions with numbered steps
  - System overview and feature descriptions
  - Troubleshooting guide
  - Login and account management
  - **NEW: Comprehensive Deposit Management section**
  - **NEW: Deposit Deduction procedures**
  - Screenshots placeholders for future updates

## Key Features Documented

### Newly Added Features (September 2026)
1. **Deposit Deduction System**
   - Record property damage deductions from security deposits
   - Full transparency with detailed reasons
   - Real-time updates visible to tenants
   - Audit trail with staff member tracking
   - Balance validation (cannot exceed available deposit)

2. **Enhanced Deposit Tracking**
   - Collection entries
   - Deduction entries with reasons
   - Refund entries
   - Complete transaction history
   - Current balance calculations

### Core System Features
- Multi-tenant contract management
- Automated monthly invoicing
- Split billing for shared rooms
- Electricity meter reading and billing
- Payment processing (cash and online)
- Laundry service management
- Financial reporting
- Comprehensive audit logging

## Database Schema Highlights

### Total Entities: 16
1. Users
2. Rooms
3. Tenants
4. Contracts
5. Contract_Tenants (Junction)
6. **Deposit_Entries (Enhanced)**
7. Invoices
8. Tenant_Payments
9. Payments
10. Meter_Readings
11. Services
12. Service_Prices
13. Laundry_Orders
14. Order_Items
15. Settings
16. Audit_Logs

### Key Relationships
- **1:1** - Users ↔ Tenants
- **1:N** - Rooms → Contracts, Contracts → Invoices, Contracts → Deposit_Entries
- **M:N** - Contracts ↔ Tenants (via Contract_Tenants)

## ERD Notation

The Entity Relationship Diagram uses **Crow's Foot Notation**:
- **One**: Single line (—)
- **Many**: Crow's foot (⟨)
- **Zero or One**: Circle + line (○—)
- **One or More**: Crow's foot + line (⟨—)

### Symbol Legend
- **PK** (Red): Primary Key
- **FK** (Orange): Foreign Key
- **Regular fields**: Standard attributes

## Business Rules

### Deposit Management
1. Current Balance = SUM(collected) - SUM(deduction) - SUM(refund)
2. Deductions cannot exceed current balance
3. All deductions require detailed reason (max 500 characters)
4. Deductions are recorded with staff member ID and timestamp
5. Tenants see all deductions immediately in their portal

### Invoice Processing
1. Per-tenant share = total_amount ÷ tenant_count
2. Carry-over balances from unpaid invoices
3. Credit balances from overpayments applied automatically
4. Status updates based on payment completion and due dates

### Contract Rules
1. One active contract per room at a time
2. Multiple tenants per contract (up to room capacity)
3. Contracts can be deactivated (voids unpaid invoices)
4. Room status changes automatically with contract status

## Generating Documentation

### Prerequisites
- Node.js 16+
- Puppeteer library

### Commands
```bash
# Install dependencies
npm install

# Generate all PDF documents
node scripts/generate-pdfs.js
```

### Output
All PDFs are generated in the `docs/` directory with:
- Print-ready formatting
- Professional styling
- Page breaks for readability
- Proper margins for binding

## Document Maintenance

### When to Update
- After database schema changes
- When adding new features
- After business rule modifications
- Following user feedback on documentation clarity

### Version Control
- Each document includes version number and date
- Major releases increment version number
- Update dates reflect last modification

## Support

For questions or clarifications about the documentation:
1. Review the relevant PDF document
2. Check the troubleshooting section in the User Manual
3. Contact system administrator
4. Refer to the audit logs for historical data

## File Structure

```
docs/
├── README.md                    (This file)
├── database-erd.html           (ERD source)
├── database-erd.pdf            (ERD - printable)
├── database-design.html        (Design doc source)
├── database-design.pdf         (Design doc - printable)
├── user-manual.html            (Manual source)
└── user-manual.pdf             (User manual - printable)
```

## Technical Specifications

### Database
- **DBMS:** MySQL 8.0+
- **Engine:** InnoDB
- **Character Set:** UTF-8 (utf8mb4_unicode_ci)
- **Total Tables:** 16
- **Total Relationships:** 20+

### Application
- **Framework:** Laravel 11
- **PHP Version:** 8.2+
- **Frontend:** Blade Templates with Alpine.js
- **Payment Integration:** PayMongo

## Changelog

### Version 1.0 (September 2026)
- Initial comprehensive documentation
- Complete ERD with crow's foot notation
- Full user manual for all roles
- Database design document
- **Added:** Deposit deduction system documentation
- **Added:** Enhanced deposit tracking
- Professional PDF formatting
- Print-ready layouts

---

**Last Updated:** September 30, 2026  
**Documentation Version:** 1.0  
**System Version:** 1.0
