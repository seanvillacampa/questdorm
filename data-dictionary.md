# QuestDorm — Updated Data Dictionary

---

## Table 7: `users`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | User ID | — | auto-increment | Yes | PK | |
| first_name | varchar | First Name | 100 | | Yes | | |
| middle_name | varchar | Middle Name | 100 | | No | | |
| last_name | varchar | Last Name | 100 | | Yes | | |
| email | varchar | Email Address | 255 | | Yes | | |
| email_verified_at | timestamp | Email Verified Date | — | YYYY-MM-DD HH:MM:SS | No | | |
| phone | varchar | Phone Number | 255 | | No | | |
| phone_country_code | varchar | Country Code | 5 | +63 | No | | |
| password | varchar | Hashed Password | 255 | | Yes | | |
| is_active | boolean | Account Active Status | — | 0=Inactive, 1=Active | Yes | | |
| last_login_at | timestamp | Last Login Date | — | YYYY-MM-DD HH:MM:SS | No | | |
| remember_token | varchar | Session Remember Token | 100 | | No | | |
| created_at | timestamp | Record Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Record Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 8: `roles` *(Spatie laravel-permission)*

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Role ID | — | auto-increment | Yes | PK | |
| name | varchar | Role Name | 255 | owner / employee / tenant | Yes | | |
| guard_name | varchar | Authentication Guard | 255 | web | Yes | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 8a: `model_has_roles` *(Spatie pivot)*

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| role_id | bigint unsigned | Role ID | — | | Yes | FK | roles |
| model_type | varchar | Polymorphic Model Class | 255 | App\Models\User | Yes | | |
| model_id | bigint unsigned | Model Record ID | — | | Yes | FK | users |

---

## Table 9: `tenants`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Tenant ID | — | auto-increment | Yes | PK | |
| user_id | bigint unsigned | User ID | — | | Yes | FK (unique) | users |
| birthdate | date | Date of Birth | — | YYYY-MM-DD | No | | |
| id_type | varchar | Government ID Type | 50 | | No | | |
| id_number | varchar | Government ID Number | 50 | | No | | |
| emergency_name | varchar | Emergency Contact Name | 255 | | No | | |
| emergency_phone | varchar | Emergency Contact Phone | 30 | | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 10: `rooms`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Room ID | — | auto-increment | Yes | PK | |
| room_number | varchar | Room Number | 10 | e.g. "101" | Yes | | |
| floor | tinyint unsigned | Floor Level | 3 | | Yes | | |
| capacity | varchar | Maximum Occupants | 20 | e.g. "2 beds" | Yes | | |
| monthly_rate | decimal | Monthly Rent Rate | 10,2 | | Yes | | |
| deposit_required | decimal | Required Security Deposit | 10,2 | | Yes | | |
| meter_number | varchar | Electric Meter Number | 30 | | No | | |
| status | varchar | Room Availability | 20 | vacant / occupied / maintenance | Yes | | |
| is_airconditioned | boolean | Has Air Conditioning | — | 0=No, 1=Yes | Yes | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 11: `contracts`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Contract ID | — | auto-increment | Yes | PK | |
| room_id | bigint unsigned | Room ID | — | | Yes | FK | rooms |
| start_date | date | Contract Start Date | — | YYYY-MM-DD | Yes | | |
| end_date | date | Contract End Date | — | YYYY-MM-DD | No | | |
| monthly_rent | decimal | Monthly Rent Amount | 10,2 | | No | | |
| due_day | tinyint unsigned | Payment Due Day of Month | 3 | 1–28 | Yes | | |
| deposit_collected | decimal | Deposit Amount Collected | 10,2 | | Yes | | |
| status | varchar | Contract Status | 20 | active / ended / ending_soon | Yes | | |
| is_active | boolean | Active Flag | — | 0=Inactive, 1=Active | Yes | | |
| contract_file_path | varchar | Uploaded Contract File Path | 255 | | No | | |
| created_by | bigint unsigned | Staff Who Created Contract | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 12: `contract_tenants`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Record ID | — | auto-increment | Yes | PK | |
| contract_id | bigint unsigned | Contract ID | — | | Yes | FK | contracts |
| tenant_id | bigint unsigned | Tenant ID | — | | Yes | FK | tenants |
| joined_on | date | Date Tenant Joined Room | — | YYYY-MM-DD | No | | |
| left_on | date | Date Tenant Left Room | — | YYYY-MM-DD | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 13: `invoices`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Invoice ID | — | auto-increment | Yes | PK | |
| invoice_number | varchar | Invoice Number | 30 | BILL-YYMM-RRR | Yes | | |
| contract_id | bigint unsigned | Contract ID | — | | Yes | FK | contracts |
| tenant_count | tinyint unsigned | Number of Tenants in Room | 3 | | Yes | | |
| billing_month | varchar | Month of Billing | 7 | YYYY-MM | Yes | | |
| due_date | date | Payment Due Date | — | YYYY-MM-DD | Yes | | |
| rent_amount | decimal | Rent Charge | 10,2 | | Yes | | |
| electricity_amount | decimal | Electricity Charge | 10,2 | | Yes | | |
| carry_over_balance | decimal | Unpaid Balance Carried Forward | 10,2 | | Yes | | |
| credit_balance | decimal | Overpayment Credit Applied | 10,2 | | Yes | | |
| total_amount | decimal | Total Amount Due | 10,2 | | Yes | | |
| amount_paid | decimal | Total Amount Paid | 10,2 | | Yes | | |
| status | varchar | Invoice Status | 20 | pending / partial / paid / late / overdue / void | Yes | | |
| paid_at | timestamp | Date Fully Paid | — | YYYY-MM-DD HH:MM:SS | No | | |
| paymongo_link_id | varchar | PayMongo Payment Link ID | 60 | | No | | |
| created_by | bigint unsigned | User Who Created Invoice | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 14: `tenant_payments`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Tenant Payment ID | — | auto-increment | Yes | PK | |
| invoice_id | bigint unsigned | Invoice ID | — | | Yes | FK | invoices |
| tenant_id | bigint unsigned | Tenant ID | — | | Yes | FK | tenants |
| share_amount | decimal | Tenant's Share of Invoice | 10,2 | | Yes | | |
| carry_over_balance | decimal | Unpaid Balance from Prior Billing | 10,2 | | Yes | | |
| amount_paid | decimal | Amount Paid by Tenant | 10,2 | | Yes | | |
| status | varchar | Payment Status | 20 | pending / paid / overdue | Yes | | |
| method | varchar | Payment Method Used | 30 | cash / gcash / maya / card / bank_transfer / paymongo / qr_ph | No | | |
| paid_at | timestamp | Date Paid | — | YYYY-MM-DD HH:MM:SS | No | | |
| paymongo_link_id | varchar | PayMongo Link ID | 60 | | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

*Unique constraint: (invoice_id, tenant_id)*

---

## Table 15: `payments`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Payment ID | — | auto-increment | Yes | PK | |
| invoice_id | bigint unsigned | Invoice ID | — | | Yes | FK | invoices |
| tenant_payment_id | bigint unsigned | Tenant Payment ID | — | | No | FK | tenant_payments |
| amount | decimal | Payment Amount | 10,2 | | Yes | | |
| method | varchar | Payment Method | 30 | cash / gcash / maya / card / bank_transfer / paymongo / qr_ph | Yes | | |
| received_at | date | Date Payment Received | — | YYYY-MM-DD | Yes | | |
| reference | varchar | Payment Reference Number | 100 | | No | | |
| notes | varchar | Additional Notes | 255 | | No | | |
| recorded_by_type | varchar | Who Recorded Payment | 20 | staff / paymongo | Yes | | |
| recorded_by_user | bigint unsigned | Staff User Who Recorded | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 16: `meter_readings`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Meter Reading ID | — | auto-increment | Yes | PK | |
| room_id | bigint unsigned | Room ID | — | | Yes | FK | rooms |
| reading_month | varchar | Month of Meter Reading | 7 | YYYY-MM | Yes | | |
| previous_kwh | decimal | Previous Meter Reading (kWh) | 10,1 | | Yes | | |
| current_kwh | decimal | Current Meter Reading (kWh) | 10,1 | | Yes | | |
| kwh_used | decimal | Electricity Consumed (kWh) | 10,1 | | Yes | | |
| recorded_by | bigint unsigned | Staff Who Recorded Reading | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

*Unique constraint: (room_id, reading_month)*

---

## Table 17: `deposit_entries`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Deposit Entry ID | — | auto-increment | Yes | PK | |
| contract_id | bigint unsigned | Contract ID | — | | Yes | FK | contracts |
| type | varchar | Transaction Type | 20 | collected / deduction / refund | Yes | | |
| amount | decimal | Deposit Amount | 10,2 | | Yes | | |
| date | date | Transaction Date | — | YYYY-MM-DD | Yes | | |
| reason | varchar | Reason for Transaction | 255 | | No | | |
| recorded_by | bigint unsigned | Staff Who Recorded Entry | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 18: `audit_logs`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Audit Log ID | — | auto-increment | Yes | PK | |
| user_id | bigint unsigned | User ID | — | | No | FK | users |
| user_label | varchar | Display Name of User | 100 | | No | | |
| action | varchar | Action Performed | 50 | Created / Updated / Deleted / Voided / Payment | Yes | | |
| record_type | varchar | Type of Record Affected | 60 | Invoice / Tenant / Contract / Room | Yes | | |
| record_id | bigint unsigned | ID of Affected Record | — | | No | | |
| description | text | Detailed Log Description | — | | Yes | | |
| ip_address | varchar | IP Address of Request | 45 | | No | | |
| created_at | timestamp | Log Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

*Note: No updated_at — audit logs are insert-only.*

---

## Table 19: `settings`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Setting ID | — | auto-increment | Yes | PK | |
| key | varchar | Setting Key Name | 60 | electricity_rate / invoice_advance_days / overdue_grace_days / deposit_months / advance_months | Yes | | |
| value | decimal | Setting Value | 12,4 | | Yes | | |
| effective_from | date | Effective Start Date | — | YYYY-MM-DD | No | | |
| set_by | bigint unsigned | User Who Configured Setting | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 20: `services`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Service ID | — | auto-increment | Yes | PK | |
| code | varchar | Service Code | 255 | WASH / DRY / WASH_DRY / FOLD / IRON / DRY_CLEAN | Yes | | |
| name | varchar | Service Name | 255 | | Yes | | |
| weight_limit_kg | decimal | Max Weight per Load (kg) | 5,2 | | Yes | | |
| is_active | boolean | Service Active Status | — | 0=Inactive, 1=Active | Yes | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 21: `service_prices`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Service Price ID | — | auto-increment | Yes | PK | |
| service_id | bigint unsigned | Service ID | — | | Yes | FK | services |
| customer_type | enum | Customer Type Category | — | tenant / student / non_student | Yes | | |
| price | decimal | Price per Load | 8,2 | | Yes | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

*Unique constraint: (service_id, customer_type)*

---

## Table 22: `customers`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Customer ID | — | auto-increment | Yes | PK | |
| name | varchar | Customer Full Name | 255 | | Yes | | |
| type | enum | Customer Type | — | tenant / student / non_student | Yes | | |
| room_no | varchar | Room Number | 255 | | No | | |
| contact_no | varchar | Contact Number | 255 | | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 23: `laundry_orders`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Laundry Order ID | — | auto-increment | Yes | PK | |
| order_no | varchar | Order Number | 255 | LDY-YYYYMMDD-XXXX | Yes | | |
| customer_id | bigint unsigned | Customer ID | — | | Yes | FK | customers |
| customer_type | enum | Customer Type | — | tenant / student / non_student | Yes | | |
| room_no | varchar | Room Number | 255 | | No | | |
| date_received | date | Date Order Received | — | YYYY-MM-DD | Yes | | |
| payment_method | enum | Payment Method | — | none / cash / online | Yes | | |
| payment_status | enum | Payment Status | — | paid / unpaid | Yes | | |
| paid_before_service | boolean | Paid Before Service Flag | — | 0=No, 1=Yes | Yes | | |
| total_amount | decimal | Total Order Amount | 10,2 | | Yes | | |
| total_weight_kg | decimal | Total Laundry Weight (kg) | 8,2 | | Yes | | |
| total_loads | int unsigned | Total Number of Loads | 10 | | Yes | | |
| total_liquid_ml | int unsigned | Total Detergent Used (mL) | 10 | | Yes | | |
| remarks | text | Additional Remarks | — | | No | | |
| recorded_by | bigint unsigned | Staff Who Recorded Order | — | | Yes | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| deleted_at | timestamp | Soft Delete Timestamp | — | YYYY-MM-DD HH:MM:SS | No | | |

---

## Table 24: `order_items`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Order Item ID | — | auto-increment | Yes | PK | |
| laundry_order_id | bigint unsigned | Laundry Order ID | — | | Yes | FK | laundry_orders |
| service_id | bigint unsigned | Service ID | — | | Yes | FK | services |
| weight_kg | decimal | Weight of This Item (kg) | 8,2 | | Yes | | |
| weight_limit_kg | decimal | Max Weight per Load Snapshot | 5,2 | | Yes | | |
| unit_price | decimal | Price per Load Snapshot | 8,2 | | Yes | | |
| loads | int unsigned | Number of Machine Loads | 10 | | Yes | | |
| liquid_ml | int unsigned | Detergent Used (mL) | 10 | | Yes | | |
| subtotal | decimal | Item Subtotal Amount | 10,2 | | Yes | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 25: `tenant_deposits`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Tenant Deposit ID | — | auto-increment | Yes | PK | |
| contract_id | bigint unsigned | Contract ID | — | | Yes | FK | contracts |
| tenant_id | bigint unsigned | Tenant ID | — | | Yes | FK | tenants |
| amount_required | decimal | Tenant's Required Deposit Share | 10,2 | | Yes | | |
| amount_paid | decimal | Amount Paid by Tenant | 10,2 | | Yes | | |
| amount_deducted | decimal | Amount Deducted from Deposit | 10,2 | | Yes | | |
| amount_refunded | decimal | Amount Refunded to Tenant | 10,2 | | Yes | | |
| deduction_reason | text | Reason for Deduction | — | | No | | |
| notes | text | Additional Notes | — | | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

*Balance formula: amount_paid − amount_deducted − amount_refunded*

---

## Table 26: `tenant_messages`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Message ID | — | auto-increment | Yes | PK | |
| tenant_id | bigint unsigned | Tenant ID | — | | Yes | FK | tenants |
| room_id | bigint unsigned | Room ID | — | | No | FK | rooms |
| subject | varchar | Message Subject | 255 | | Yes | | |
| message | text | Message Body | — | | Yes | | |
| status | enum | Message Status | — | unread / read / replied | Yes | | |
| read_at | timestamp | Date Read | — | YYYY-MM-DD HH:MM:SS | No | | |
| read_by | bigint unsigned | Staff Who Read Message | — | | No | FK | users |
| admin_reply | text | Admin Reply Text | — | | No | | |
| replied_at | timestamp | Date Replied | — | YYYY-MM-DD HH:MM:SS | No | | |
| replied_by | bigint unsigned | Staff Who Replied | — | | No | FK | users |
| resolved_at | timestamp | Date Resolved | — | YYYY-MM-DD HH:MM:SS | No | | |
| resolved_by | bigint unsigned | Staff Who Resolved | — | | No | FK | users |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 27: `tenant_message_replies`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Reply ID | — | auto-increment | Yes | PK | |
| tenant_message_id | bigint unsigned | Parent Message ID | — | | Yes | FK | tenant_messages |
| user_id | bigint unsigned | User Who Replied | — | | Yes | FK | users |
| message | text | Reply Body | — | | Yes | | |
| read_at | timestamp | Date Read by Recipient | — | YYYY-MM-DD HH:MM:SS | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

---

## Table 28: `detergent_inventory`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Inventory ID | — | auto-increment | Yes | PK | |
| stock_ml | int unsigned | Current Detergent Stock (mL) | — | | Yes | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |

*Singleton table — always contains one row. Access via DetergentInventory::current().*

---

## Table 29: `detergent_logs`

| Field Name | Data Type | Description | Size | Format | Required | PK/FK | Reference To |
|---|---|---|---|---|---|---|---|
| id | bigint unsigned | Log ID | — | auto-increment | Yes | PK | |
| type | enum | Transaction Type | — | restock / deduct | Yes | | |
| amount_ml | int unsigned | Amount Added or Removed (mL) | — | | Yes | | |
| stock_before_ml | int unsigned | Stock Before Transaction (mL) | — | | Yes | | |
| stock_after_ml | int unsigned | Stock After Transaction (mL) | — | | Yes | | |
| laundry_order_id | bigint unsigned | Linked Laundry Order | — | | No | FK | laundry_orders |
| user_id | bigint unsigned | User Who Performed Action | — | | No | FK | users |
| notes | text | Additional Notes | — | | No | | |
| created_at | timestamp | Created Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
| updated_at | timestamp | Updated Date | — | YYYY-MM-DD HH:MM:SS | Yes | | |
