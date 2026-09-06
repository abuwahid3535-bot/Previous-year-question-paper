# Mother Theresa Degree College - Project Structure

```
T:\myproject\
│
├── HTML Pages (Frontend)
│   ├── openwebsite.html          # Public homepage (question papers portal)
│   ├── register.html             # Student registration page
│   ├── form.html                 # Student login page
│   ├── staffregister.html        # Staff registration page
│   ├── stafflogin.html           # Staff login page
│   ├── student_dashboard.html    # Student dashboard (sidebar, stats, courses)
│   ├── staff_dashboard.html      # Staff dashboard (sidebar, stats, docs)
│   ├── profile.html              # Student profile view/edit
│   ├── course.html               # Course listings page
│   ├── contact.html              # Contact form page
│   ├── forgot_password.html      # Password reset (3-step flow)
│   ├── upload_documents.html     # Staff document upload page
│   ├── verify_otp_student.html   # Student email OTP verification
│   ├── verify_otp_staff.html     # Staff OTP verification
│   ├── verify_mobile_otp.html    # Student mobile OTP verification
│   ├── test_register.html        # Minimal test registration form
│   └── degree.PNG                # College logo/image
│
├── CSS
│   ├── style.css                 # Shared styles (login, dashboard)
│   └── register.css              # Student registration styles
│
├── PHP Backend (includes/)
│   ├── register_student.php      # Student registration handler
│   ├── register_staff.php        # Staff registration handler
│   ├── login_student.php         # Student login (email/student_id + password)
│   ├── login_staff.php           # Staff login (email/staff_id + password)
│   ├── get_profile.php           # Fetch student profile data
│   ├── update_profile.php        # Update student profile
│   ├── upload_profile_pic.php    # Upload student profile picture
│   ├── upload_documents.php      # Staff document CRUD (upload/list/delete)
│   ├── submit_contact.php        # Submit contact form message
│   ├── twilio_config.php         # Twilio API config + OTP functions
│   ├── send_mobile_otp.php       # Resend student mobile OTP
│   ├── send_staff_otp.php        # Resend staff mobile OTP
│   ├── verify_mobile_otp.php     # Verify student mobile OTP
│   ├── verify_staff_mobile_otp.php  # Verify staff mobile OTP
│   ├── verify_student_otp.php    # Verify student email OTP
│   ├── verify_staff_otp.php      # Verify staff email OTP
│   ├── verify_otp_student.php    # Student OTP (legacy/redirect)
│   ├── verify_otp_staff.php      # Staff OTP (legacy/redirect)
│   ├── forgot_password_send_otp.php    # Send OTP for password reset
│   ├── forgot_password_verify_otp.php  # Verify OTP for password reset
│   └── forgot_password_reset.php       # Reset password
│
├── Database (db/)
│   ├── connect.php               # DB connection + helper functions
│   └── init_db.sql               # Schema + seed data
│       Tables: students, staff, documents, contact_messages
│
├── Uploads
│   └── uploads/                  # User-uploaded files (staff documents, profile pics)
│
├── Utility
│   ├── hash.php                  # Password hash generator
│   └── test_php.php              # DB connectivity test script
│
└── Config
    └── .git/                     # Git repository
```

## Tech Stack
- **Frontend:** Vanilla HTML/CSS/JavaScript
- **Backend:** Vanilla PHP (mysqli)
- **Database:** MySQL (XAMPP)
- **Server:** Apache (XAMPP) on localhost

## Access URLs
- Homepage: http://localhost/myproject/openwebsite.html
- Student Login: http://localhost/myproject/form.html
- Student Register: http://localhost/myproject/register.html
- Student Dashboard: http://localhost/myproject/student_dashboard.html
- Staff Login: http://localhost/myproject/stafflogin.html
- Staff Register: http://localhost/myproject/staffregister.html
- Staff Dashboard: http://localhost/myproject/staff_dashboard.html
