INSERT INTO `mail_templates` (`mail_type`, `mail_subject`, `mail_body`) VALUES
(
  'seller_membership_extend',
  'Your Seller Package Extension Invoice',
  '<p>Hi {username},</p>
  <p>Your seller package has been successfully extended on {website_title}.</p>
  <p>
    <strong>Package Title:</strong> {package_title}<br>
    <strong>Package Price:</strong> {package_price}<br>
    <strong>Activation Date:</strong> {activation_date}<br>
    <strong>Expire Date:</strong> {expire_date}
  </p>
  <p>We have attached an invoice with this email.<br>Thank you for your continued trust.</p>
  <p>Best Regards,<br>{website_title}</p>'
); 