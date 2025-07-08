# Comprehensive Notification System Documentation

## Overview
This document outlines the comprehensive notification system implemented across the platform, covering all major actions and providing detailed information to relevant users.

## Notification Classes

### 1. OrderNotification
**Purpose**: Handles all order-related notifications
**Location**: `app/Notifications/OrderNotification.php`

**Detailed Data Included**:
- `order_id`: Order ID
- `order_number`: Order number
- `service_name`: Service title
- `service_id`: Service ID
- `order_status`: Current order status
- `payment_status`: Current payment status
- `amount`: Order amount
- `currency`: Currency symbol
- `customer_name`: Customer name
- `seller_name`: Seller name
- `package_name`: Package name
- `payment_method`: Payment method used
- `gateway_type`: Payment gateway type

### 2. ServiceNotification
**Purpose**: Handles all service-related notifications
**Location**: `app/Notifications/ServiceNotification.php`

**Detailed Data Included**:
- `service_id`: Service ID
- `service_name`: Service title
- `seller_id`: Seller ID
- `seller_name`: Seller username
- `is_featured`: Featured status
- `status`: Service status
- `created_at/updated_at/deleted_at`: Timestamps

### 3. UserNotification
**Purpose**: Handles all user-related notifications
**Location**: `app/Notifications/UserNotification.php`

**Detailed Data Included**:
- `user_id`: User ID
- `username`: Username
- `email`: Email address
- `registration_date`: Registration date
- `verified_at`: Email verification date

### 4. PaymentNotification
**Purpose**: Handles all payment-related notifications
**Location**: `app/Notifications/PaymentNotification.php`

### 5. SystemNotification
**Purpose**: Handles all system-related notifications
**Location**: `app/Notifications/SystemNotification.php`

### 6. WithdrawalNotification
**Purpose**: Handles all withdrawal-related notifications
**Location**: `app/Notifications/WithdrawalNotification.php`

**Detailed Data Included**:
- `withdraw_id`: Withdrawal ID
- `seller_id`: Seller ID
- `seller_name`: Seller username
- `amount`: Withdrawal amount
- `payable_amount`: Payable amount after charges
- `total_charge`: Total charges
- `method_name`: Withdrawal method name
- `status`: Withdrawal status
- `requested_at/approved_at/declined_at`: Timestamps

## Implemented Notifications

### Order Actions

#### 1. Order Creation
**Triggered**: When a new order is placed
**Notified Users**:
- **Seller**: New order received with service details, package, amount, and status
- **Admin**: New order placed by customer with all details
- **User**: Order placed successfully with service and payment details

**Message Examples**:
- Seller: "New order #12345 received for service: Web Development - Package: Premium - Amount: $500 - Status: Pending"
- Admin: "New order #12345 placed by John Doe for service: Web Development - Package: Premium - Amount: $500 - Payment: Pending"
- User: "Your order #12345 for service: Web Development - Package: Premium has been placed successfully. Amount: $500 - Payment Status: Pending"

#### 2. Order Status Updates
**Triggered**: When order status changes (completed/rejected)
**Notified Users**:
- **Seller**: Order completion with earnings or rejection notification
- **User**: Order completion or rejection with details

**Message Examples**:
- Seller (Completed): "Order #12345 for service: Web Development has been completed. You earned $450"
- User (Completed): "Your order #12345 for service: Web Development has been completed successfully!"
- User (Rejected): "Your order #12345 for service: Web Development has been rejected. Please contact support for more information."

#### 3. Payment Status Updates
**Triggered**: When payment status changes (completed/pending/rejected)
**Notified Users**:
- **User**: Payment status updates with amount and service details
- **Seller**: Payment received or rejected notifications
- **Admin**: Payment completion notifications

**Message Examples**:
- User (Completed): "Payment for order #12345 (Web Development) has been completed successfully. Amount: $500"
- Seller (Received): "Payment received for order #12345 (Web Development). Amount: $500"
- Admin (Completed): "Payment completed for order #12345 by John Doe (Web Development). Amount: $500"

### Service Actions

#### 1. Service Creation
**Triggered**: When a new service is created
**Notified Users**:
- **Admin**: New service created by seller
- **Seller**: Service created successfully

**Message Examples**:
- Admin: "New service 'Web Development' has been created by JohnSeller"
- Seller: "Your service 'Web Development' has been created successfully"

#### 2. Service Updates
**Triggered**: When a service is updated
**Notified Users**:
- **Admin**: Service updated by seller
- **Seller**: Service updated successfully

**Message Examples**:
- Admin: "Service 'Web Development' has been updated by JohnSeller"
- Seller: "Your service 'Web Development' has been updated successfully"

#### 3. Service Deletion
**Triggered**: When a service is deleted
**Notified Users**:
- **Admin**: Service deleted by seller
- **Seller**: Service deleted notification

**Message Examples**:
- Admin: "Service 'Web Development' has been deleted by JohnSeller"
- Seller: "Your service 'Web Development' has been deleted"

### User Actions

#### 1. User Registration
**Triggered**: When a new user registers
**Notified Users**:
- **Admin**: New user registration with username and email

**Message Examples**:
- Admin: "New user 'john_doe' (john@example.com) has registered"

#### 2. Email Verification
**Triggered**: When user verifies email
**Notified Users**:
- **Admin**: User email verification

**Message Examples**:
- Admin: "User 'john_doe' has verified their email address"

### Withdrawal Actions

#### 1. Withdrawal Request
**Triggered**: When seller submits withdrawal request
**Notified Users**:
- **Seller**: Withdrawal request sent successfully
- **Admin**: New withdrawal request from seller

**Message Examples**:
- Seller: "Your withdrawal request #WD123 has been sent successfully. Amount: $1000 - Payable: $950"
- Admin: "New withdrawal request #WD123 from JohnSeller. Amount: $1000"

#### 2. Withdrawal Approval
**Triggered**: When admin approves withdrawal
**Notified Users**:
- **Seller**: Withdrawal approved with details
- **Admin**: Withdrawal approved notification

**Message Examples**:
- Seller: "Your withdrawal request #WD123 has been approved. Amount: $1000 - Payable: $950"
- Admin: "Withdrawal request #WD123 from JohnSeller has been approved. Amount: $1000"

#### 3. Withdrawal Decline
**Triggered**: When admin declines withdrawal
**Notified Users**:
- **Seller**: Withdrawal declined with balance return
- **Admin**: Withdrawal declined notification

**Message Examples**:
- Seller: "Your withdrawal request #WD123 has been declined. Amount: $1000 has been returned to your balance."
- Admin: "Withdrawal request #WD123 from JohnSeller has been declined. Amount: $1000"

## Real-time Features

### Broadcasting
All notifications support real-time broadcasting using Laravel's broadcasting system with Pusher integration.

### Notification Types
Each notification includes:
- **Title**: Clear action description
- **Message**: Detailed information about the action
- **URL**: Direct link to relevant page
- **Icon**: FontAwesome icon for visual identification
- **Extra Data**: Comprehensive details about the action

### Notification Icons
- `fas fa-shopping-cart`: Order-related actions
- `fas fa-check-circle`: Successful completions
- `fas fa-times-circle`: Rejections/failures
- `fas fa-credit-card`: Payment actions
- `fas fa-clock`: Pending actions
- `fas fa-plus-circle`: Creation actions
- `fas fa-edit`: Update actions
- `fas fa-trash`: Deletion actions
- `fas fa-user-plus`: User registration
- `fas fa-paper-plane`: Request submissions

## Benefits

### For Users
- **Real-time Updates**: Immediate notifications for all actions
- **Detailed Information**: Complete context about each action
- **Direct Links**: Easy navigation to relevant pages
- **Status Tracking**: Clear visibility of order and payment status

### For Sellers
- **Order Management**: Instant notifications for new orders
- **Payment Tracking**: Real-time payment status updates
- **Service Management**: Notifications for service actions
- **Withdrawal Updates**: Complete withdrawal request tracking

### For Admins
- **Platform Monitoring**: Comprehensive overview of all activities
- **User Management**: Track user registrations and verifications
- **Order Oversight**: Monitor all order and payment activities
- **Withdrawal Management**: Track all withdrawal requests and actions

## Technical Implementation

### Database Storage
All notifications are stored in the `notifications` table with:
- `type`: Notification class name
- `notifiable_type`: User type (User, Seller, Admin)
- `notifiable_id`: User ID
- `data`: JSON data with all notification details
- `read_at`: Read timestamp
- `created_at`: Creation timestamp

### Broadcasting
Notifications are broadcasted using:
- **Pusher**: Real-time delivery
- **Database**: Persistent storage
- **Queue**: Asynchronous processing for better performance

### Security
- **Guard-based**: Notifications respect user authentication
- **Permission-based**: Admin notifications respect role permissions
- **Data Validation**: All notification data is validated before storage

## Future Enhancements

### Potential Additions
1. **Email Notifications**: Send email copies of important notifications
2. **SMS Notifications**: Critical notifications via SMS
3. **Push Notifications**: Mobile app notifications
4. **Notification Preferences**: User-configurable notification settings
5. **Notification Templates**: Customizable notification messages
6. **Notification Analytics**: Track notification engagement and effectiveness

### Additional Actions to Cover
1. **Support Ticket Actions**: Ticket creation, updates, responses
2. **Review Actions**: Service reviews and ratings
3. **Message Actions**: Order message exchanges
4. **System Maintenance**: Platform maintenance notifications
5. **Security Alerts**: Login attempts, password changes
6. **Promotional Notifications**: Special offers and announcements

This comprehensive notification system ensures that all platform participants are kept informed of relevant actions with detailed context, improving user experience and platform transparency. 