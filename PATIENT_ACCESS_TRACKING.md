# Patient Record Access Tracking Implementation

## Overview
This feature implements HIPAA/GDPR-compliant patient record access tracking for the Know My Patient application. NHS users can view patient records, and every access is logged and displayed for audit purposes.

## Implementation Details

### 1. Database Logging
When an NHS user accesses a patient record via the Patient Passport page:

- **Activity Type**: `PATIENT_RECORD_ACCESSED`
- **Logged Data**:
  - `user_id`: NHS user who accessed the record
  - `target_user_id`: Patient whose record was accessed
  - `activity_type`: 'PATIENT_RECORD_ACCESSED'
  - `description`: JSON with patient name, NHS number, access method
  - `ip_address`: IP address of the accessing user
  - `timestamp`: Date and time of access

### 2. Access History Display
When a patient record is loaded, the system:

1. Queries the last 20 accesses of that patient's record from `audit_log`
2. Joins with `users` table to get accessor names and roles
3. Displays access history in a collapsible section at the top of the patient record

**Security Features**:
- Access history is ONLY visible when a user is logged in (`{% if user_id is defined and user_id %}`)
- Section is hidden for unauthenticated users
- Shows who accessed, when, from what IP, and their role

### 3. User Interface
The access history section includes:

- **Toggle Button**: Collapse/expand to reduce visual clutter
- **Table Format**: Clean display of access data
  - Date & Time (formatted as dd/mm/yyyy HH:mm:ss)
  - Accessed By (name and email)
  - Role (badge showing NHS_USER, ADMIN, etc.)
  - IP Address (in code format)
- **Security Notice**: Informs users that lookups are logged
- **Access Count**: Shows "Showing last X accesses"

### 4. Code Changes

#### PatientPassportAction.php
- Added PDO dependency injection for database operations
- Enhanced to handle POST requests with patient UID
- Fetches patient data from `patient_profiles` table
- Logs access to `audit_log` table
- Queries access history for display
- Passes data to template: `patientData`, `accessHistory`, `user_id`

#### patient_passport.html.twig
- Added access history section after patient profile header
- Conditional display: only visible when `user_id` is defined
- Collapsible design with Bootstrap accordion
- Responsive table showing last 20 accesses
- Icons and badges for better UX

## Testing Checklist

### As NHS User (Logged In)
1. ✅ Navigate to `/patient-passport`
2. ✅ Enter patient UID (manual or QR scan)
3. ✅ Verify patient record loads
4. ✅ Verify access history section appears at top
5. ✅ Click "Show/Hide" button to expand access history
6. ✅ Verify your current access is logged in the table
7. ✅ Check `/admin/audit-dashboard` for PATIENT_RECORD_ACCESSED entry

### As Unauthenticated User
1. ✅ Access history section should NOT appear (even if patient data loaded somehow)
2. ✅ Conditional `{% if user_id %}` prevents display

### Database Verification
```sql
SELECT 
    al.timestamp,
    al.ip_address,
    u.name as accessor_name,
    u.role as accessor_role,
    u2.name as patient_name
FROM audit_log al
LEFT JOIN users u ON al.user_id = u.id
LEFT JOIN users u2 ON al.target_user_id = u2.id
WHERE al.activity_type = 'PATIENT_RECORD_ACCESSED'
ORDER BY al.timestamp DESC
LIMIT 20;
```

## Compliance Benefits

### HIPAA Compliance
- **Audit Controls (§164.312(b))**: All accesses are logged with who, what, when, where
- **Access Control (§164.312(a)(1))**: Only NHS users can access patient records
- **Person/Entity Authentication (§164.312(d))**: User ID and session validated
- **Transmission Security (§164.312(e)(1))**: IP addresses logged for security tracking

### GDPR Compliance
- **Article 32 (Security)**: Access logging provides security audit trail
- **Article 5(1)(f) (Integrity/Confidentiality)**: Monitoring who accesses personal data
- **Article 15 (Right of Access)**: Patients can request access logs showing who viewed their data
- **Recital 39 (Processing Records)**: Documentation of all processing activities

## Future Enhancements

1. **Email Notifications**: Alert patients when their record is accessed
2. **Export Functionality**: Allow patients to download their access history
3. **Suspicious Activity Detection**: Flag unusual access patterns (multiple accesses in short time)
4. **Role-Based Display**: Different views for different user types
5. **Access Reason**: Require NHS users to provide reason for access
6. **Department Tracking**: Log which department/ward accessed the record

## Support

For questions or issues with the patient access tracking system:
- Check audit logs in Admin Dashboard → Audit Dashboard
- Review `audit_log` table for PATIENT_RECORD_ACCESSED entries
- Verify NHS user has proper role permissions
- Check session data includes `user_id` for conditional display
