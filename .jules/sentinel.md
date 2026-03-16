## 2024-05-24 - CSRF Protection Missing
**Vulnerability:** The application is missing Cross-Site Request Forgery (CSRF) protection on forms that modify data.
**Learning:** State-changing forms across the application lacked CSRF protection, allowing attackers to forge requests on behalf of authenticated users.
**Prevention:** Implement and enforce CSRF token validation on all POST forms.
