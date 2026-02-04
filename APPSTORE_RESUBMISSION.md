# App Store Resubmission Message

Dear App Review Team,

Thank you for your feedback on submission **c7ea889e-e9ae-49ad-903a-e212e6c75eb1**. We have addressed both issues identified in your review:

## Issue 1: Phone and WhatsApp Numbers (Guideline 5.1.1) - RESOLVED ✓

**Changes Made:**
- Phone number and WhatsApp number fields are now **optional** during registration
- Users can create accounts and use all core features without providing these details
- Fields are only validated for format if the user chooses to provide them
- Clear "(Optional)" labels added to both fields in the UI

**Testing:**
- Users can successfully register without phone/WhatsApp
- Existing users can remove phone numbers from their profiles
- All core functionality works without phone numbers

**Location in App:**
- Registration screen: Phone and WhatsApp fields marked as optional
- Profile Settings: Phone field marked as optional

## Issue 2: Account Deletion (Guideline 5.1.1(v)) - RESOLVED ✓

**Changes Made:**
- Added comprehensive account deletion feature accessible from user profile
- Users can initiate account deletion with password confirmation
- 30-day grace period allows users to cancel deletion via email link
- Email notifications sent for both deletion requests and cancellations
- All user data is permanently deleted after grace period

**Deletion Process:**
1. User navigates to Profile > Account Management tab
2. Clicks "Delete My Account" button
3. Confirms with password and types "DELETE"
4. Receives email with cancellation link (30-day grace period)
5. Can cancel anytime within 30 days
6. After 30 days, account and all data permanently deleted

**What Gets Deleted:**
- Personal information and profile
- Order history and tracking data
- Wallet balance and transaction history
- Saved addresses and preferences
- Wishlist and cart items

**Location in App:**
- Profile > Account Management > Delete Account
- Cancellation link provided via email

## Compliance Confirmation

Both features are now fully functional and comply with App Store guidelines 5.1.1 and 5.1.1(v). We have thoroughly tested these implementations to ensure a smooth user experience while respecting user privacy and data control.

Thank you for your review. Please let us know if you need any additional information or clarification.

Best regards,
[Your Name]
[Your Company]
