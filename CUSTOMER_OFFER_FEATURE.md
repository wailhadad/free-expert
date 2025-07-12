# Customer Offer Feature Documentation

## Overview

The Customer Offer feature allows sellers to create custom offers for customers directly within the chat system. Customers can accept, decline, or let offers expire, and when accepted, they can proceed to checkout with optional form attachments.

## Features

### For Sellers
- Create custom offers with title, description, and price
- Attach optional forms for additional customer information
- Set expiration dates for offers
- Receive real-time notifications when offers are accepted/declined
- View offer status and order details

### For Customers
- View offers in chat with clear status indicators
- Accept or decline offers with one click
- Fill attached forms during checkout
- Complete payment through various gateways
- Receive order confirmations and invoices

### For Admins
- View all customer offers across the platform
- Monitor offer-to-order conversions
- Access order details and customer information

## Database Structure

### customer_offers Table
```sql
CREATE TABLE customer_offers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chat_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    subuser_id BIGINT UNSIGNED NULL,
    form_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency_symbol VARCHAR(10) DEFAULT '$',
    status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    expires_at TIMESTAMP NULL,
    form_data JSON NULL,
    accepted_order_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (chat_id) REFERENCES direct_chats(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subuser_id) REFERENCES subusers(id) ON DELETE SET NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE SET NULL,
    FOREIGN KEY (accepted_order_id) REFERENCES service_orders(id) ON DELETE SET NULL
);
```

## API Endpoints

### Seller Endpoints
- `GET /seller/customer-offer/forms` - Get available forms for seller
- `POST /seller/customer-offer/create` - Create a new customer offer
- `GET /seller/customer-offer/{chat}/offers` - Get offers for a specific chat

### User Endpoints
- `GET /customer-offer/{chat}/offers` - Get offers for a specific chat
- `POST /customer-offer/{offer}/accept` - Accept a customer offer
- `POST /customer-offer/{offer}/decline` - Decline a customer offer
- `GET /customer-offer/{offer}/details` - Get offer details for checkout

### Checkout Endpoints
- `GET /customer-offer/{offer}/checkout` - Show checkout page
- `POST /customer-offer/{offer}/process-checkout` - Process checkout
- `GET /customer-offer/{offer}/complete` - Show completion page

### Admin Endpoints
- `GET /admin/customer-offer/{chat}/offers` - Get offers for a specific chat

## Models

### CustomerOffer Model
```php
class CustomerOffer extends Model
{
    protected $fillable = [
        'chat_id', 'seller_id', 'user_id', 'subuser_id', 'form_id',
        'title', 'description', 'price', 'currency_symbol', 'status',
        'expires_at', 'form_data', 'accepted_order_id'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'form_data' => 'array',
    ];

    // Relationships
    public function chat() { return $this->belongsTo(DirectChat::class); }
    public function seller() { return $this->belongsTo(Seller::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function subuser() { return $this->belongsTo(Subuser::class); }
    public function form() { return $this->belongsTo(Form::class); }
    public function acceptedOrder() { return $this->belongsTo(ServiceOrder::class); }

    // Helper methods
    public function isExpired() { return $this->expires_at && $this->expires_at->isPast(); }
    public function canBeAccepted() { return $this->status === 'pending' && !$this->isExpired(); }
    public function getFormattedPriceAttribute() { return $this->currency_symbol . number_format($this->price, 2); }
}
```

## Controllers

### CustomerOfferController
Handles offer creation, acceptance, and management.

**Key Methods:**
- `getForms()` - Retrieve available forms for seller
- `create()` - Create new customer offer
- `getOffers()` - Get offers for a chat
- `accept()` - Accept an offer
- `decline()` - Decline an offer
- `getOfferDetails()` - Get offer details for checkout

### CustomerOfferCheckoutController
Handles the checkout process for accepted offers.

**Key Methods:**
- `checkout()` - Show checkout page
- `processCheckout()` - Process checkout and create order
- `complete()` - Show completion page

## Frontend Implementation

### Direct Chat Integration
The customer offer feature is integrated into the existing direct chat system:

1. **Customer Offer Button**: Sellers see a "Customer Offer" button in the chat interface
2. **Offer Modal**: Clicking the button opens a modal to create offers
3. **Form Selection**: Sellers can optionally attach forms to offers
4. **Offer Display**: Offers appear as styled cards in the chat
5. **Action Buttons**: Customers can accept/decline offers directly in chat

### JavaScript Functions
```javascript
// Load forms for seller
function loadCustomerOfferForms()

// Show form preview
function showFormPreview(formId)

// Create customer offer
function createCustomerOffer()

// Load offers for chat
function loadCustomerOffers(chatId)

// Accept offer
function acceptCustomerOffer(offerId)

// Decline offer
function declineCustomerOffer(offerId)

// Render offer in chat
function renderCustomerOffer(offer)
```

### CSS Styling
Customer offer cards have different styling based on status:
- **Pending**: Light background with warning border
- **Accepted**: Green gradient background
- **Declined**: Red gradient background
- **Expired**: Gray gradient background

## Workflow

### 1. Seller Creates Offer
1. Seller clicks "Customer Offer" button in chat
2. Modal opens with form fields
3. Seller fills offer details (title, description, price)
4. Optional: Select form to attach
5. Optional: Set expiration date
6. Seller submits offer

### 2. Customer Receives Offer
1. Customer receives real-time notification
2. Offer appears as styled card in chat
3. Customer can view offer details
4. Customer can accept or decline

### 3. Customer Accepts Offer
1. Customer clicks "Accept" button
2. System updates offer status to "accepted"
3. Customer is redirected to checkout page
4. Seller receives notification

### 4. Checkout Process
1. Customer fills attached form (if any)
2. Customer selects payment method
3. Customer completes payment
4. Order is created and linked to offer
5. Customer is redirected to completion page

### 5. Order Management
1. Order appears in seller's order list
2. Order appears in customer's order history
3. Admin can view and manage order
4. Invoice is generated and sent

## Notifications

### Real-time Notifications
The system sends real-time notifications for:
- New offer created (to customer)
- Offer accepted (to seller)
- Offer declined (to seller)
- Order created from offer (to seller and admin)

### Email Notifications
- Order confirmation emails
- Invoice emails
- Payment status updates

## Security Features

### Authorization
- Sellers can only create offers in their own chats
- Customers can only accept/decline their own offers
- Admins can view all offers
- Form validation for required fields

### Data Validation
- Price must be positive number
- Expiration date must be in future
- Form data validation based on field requirements
- File upload size limits

## Integration Points

### Existing Systems
- **Direct Chat System**: Offers are displayed within chat interface
- **Form System**: Reuses existing form builder functionality
- **Order System**: Creates orders using existing order processing
- **Payment System**: Integrates with existing payment gateways
- **Notification System**: Uses existing notification infrastructure

### Payment Gateways
Customer offer orders support all existing payment gateways:
- PayPal
- Stripe
- Razorpay
- Offline payment methods
- And more...

## Configuration

### Environment Variables
No additional environment variables required.

### Database Configuration
The feature uses existing database connections and configurations.

### File Storage
- Form files are stored in `./assets/file/zip-files/`
- Invoices are stored in `./assets/file/invoices/service/`

## Testing

### Manual Testing Scenarios
1. **Seller creates offer**: Verify offer appears in chat
2. **Customer accepts offer**: Verify redirect to checkout
3. **Customer declines offer**: Verify status update
4. **Offer expires**: Verify automatic status change
5. **Form attachment**: Verify form fields in checkout
6. **Payment processing**: Verify order creation
7. **Notifications**: Verify real-time and email notifications

### Automated Testing
Recommended test cases:
- Offer creation validation
- Offer acceptance/decline flow
- Checkout form validation
- Payment processing
- Notification delivery
- Order creation and linking

## Troubleshooting

### Common Issues
1. **Offers not appearing**: Check chat permissions and user authentication
2. **Form not loading**: Verify form exists and seller has access
3. **Payment failures**: Check payment gateway configuration
4. **Notifications not sent**: Verify notification service configuration

### Debug Information
- Check browser console for JavaScript errors
- Review Laravel logs for backend errors
- Verify database relationships and foreign keys
- Check notification service status

## Future Enhancements

### Potential Improvements
1. **Bulk Offers**: Allow sellers to create multiple offers at once
2. **Offer Templates**: Pre-defined offer templates for common services
3. **Counter Offers**: Allow customers to propose counter offers
4. **Offer Analytics**: Track offer acceptance rates and performance
5. **Automated Expiration**: Automatic cleanup of expired offers
6. **Offer Scheduling**: Schedule offers to be sent at specific times
7. **Multi-language Support**: Support for multiple languages in offers

### Performance Optimizations
1. **Caching**: Cache frequently accessed offer data
2. **Database Indexing**: Add indexes for offer queries
3. **Lazy Loading**: Implement lazy loading for offer lists
4. **API Rate Limiting**: Add rate limiting for offer creation

## Support

For technical support or questions about the Customer Offer feature:
1. Check this documentation
2. Review the code comments
3. Check the Laravel logs
4. Contact the development team

## Version History

- **v1.0.0** - Initial implementation
  - Basic offer creation and management
  - Chat integration
  - Checkout process
  - Payment integration
  - Notification system 