# Security Recommendations

This application has been built with security in mind, including input validation, output escaping, CSRF protection, and prevention against SQL injection. However, overall security also heavily depends on the server environment and operational practices.

## 1. Web Server Configuration

*   **`.htaccess` (For Apache Users)**:
    *   The included `.htaccess` file provides basic protections:
        *   It disables directory listing (`Options -Indexes`).
        *   It denies direct web access to sensitive files like `config.php` and `create_users_table.sql`.
    *   Ensure your Apache server is configured to allow `.htaccess` overrides (`AllowOverride All` or specific directives for the directory in your Apache configuration).

*   **Non-Apache Servers (Nginx, etc.)**:
    *   If you are not using Apache, the `.htaccess` file will have no effect. You must translate these rules into your web server's specific configuration syntax.
        *   **Disable directory listing**: (e.g., `autoindex off;` in Nginx).
        *   **Deny access to sensitive files**: (e.g., using `location` blocks with `deny all;` in Nginx for `config.php`, `create_users_table.sql`, etc.).

*   **Document Root Configuration (Highly Recommended)**:
    *   For enhanced security, structure your project so that the web server's document root (e.g., `public_html`, `/var/www/html`) only contains publicly accessible files (typically an `index.php` front controller and asset directories like `css/`, `js/`, `images/`).
    *   Sensitive files like `config.php`, PHP classes, and other application logic should reside **outside** this public document root. Your `index.php` would then include these files using their server file paths. This significantly reduces the risk of accidental exposure of sensitive code or credentials. *(This application currently does not use a public subdirectory structure, so all PHP files are in the root. Consider refactoring for a public/ webroot directory if your security requirements are high).*

## 2. File and Directory Permissions

*   Apply the principle of least privilege. Files and directories should only have the permissions they absolutely need.
*   Typically, directories should be `755` (rwxr-xr-x) and files should be `644` (rw-r--r--).
*   `config.php` could be set to `600` (rw-------) if the web server user is the owner of the file, further restricting access.
*   Avoid world-writable permissions (e.g., `777`).

## 3. PHP Configuration

*   In a production environment, ensure `display_errors` is set to `Off` in your `php.ini` file.
*   Configure `log_errors` to `On` and specify an `error_log` file path that is not accessible from the web. This ensures PHP errors are logged securely without exposing details to users.

## 4. Regular Maintenance

*   Keep your server software (OS, web server, PHP, database) and any libraries (including JavaScript libraries like Bootstrap, marked.js, DOMPurify) up to date with the latest security patches.
*   Regularly back up your application files and database.

## 5. Database Security

*   Use strong, unique passwords for your database user.
*   The database user specified in `config.php` should only have the necessary permissions for the application to function (e.g., SELECT, INSERT, UPDATE, DELETE on the application's tables, but not global privileges like FILE, PROCESS, SUPER unless absolutely required for other reasons).

By following these recommendations, you can significantly enhance the security of your web application.
