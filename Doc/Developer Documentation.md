# Developer Guidelines

## How to Obtain the Source Code

The full set of code necessary to run this project can be found in the code folder at:  
🔗 [https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/tree/main/Code](https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/tree/main/Code)

You can download the code directly or clone the repository using Git:

```bash
git clone https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer.git
````

---

## Directory Structure Layout

Place the files in the `public/` directory according to your system's web server settings.

* Files: `664` permissions
* Directories: `775`
* Sensitive files (e.g., connection details): `660`

Example directory structure:

```
public/
├── ct/
├── functions/
│   └── volunteer_functions.php
├── includes/
│   └── db_connect.php
├── functions.php
├── pricing_section.php
├── js/
├── admin_page.php
├── landing.php
├── manage_locations.php
├── register.php
├── location_qr_code.php
├── manage_users.php
├── request_hours.php
├── volunteer_checkin.php
├── login.php
├── manage_volunteers.php
├── staff_dashboard.php
├── volunteer_hours.php
├── dashboard.php
├── index.php
├── logout.php
├── organization_registration.php
├── styles.css
├── footer.php
├── manage_checkins.php
├── privacy_policy.php
├── terms_of_service.php
tests/
├── FunctionTest.php
├── VolunteerTest.php
├── bootstrap.php
logs/
└── (created by system — typically includes access.log and error.log)
```

---

## How to Build the Software

### Install Ubuntu Server

➡️ [Get Ubuntu Server | Download | Ubuntu](https://ubuntu.com/download/server)

### Install LAMP on Ubuntu

1. **Update System**

   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

2. **Install Apache**

   ```bash
   sudo apt install apache2 -y
   ```

   * Test: Open a browser and go to `http://your_server_ip`

3. **Install MySQL**

   ```bash
   sudo apt install mysql-server -y
   ```

   * (Optional) Secure MySQL:

     ```bash
     sudo mysql_secure_installation
     ```

4. **Install PHP**

   ```bash
   sudo apt install php libapache2-mod-php php-mysql -y
   ```

   * Test PHP:

     ```bash
     echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
     ```

     Visit `http://your_server_ip/info.php` to confirm.

5. **Restart Apache**

   ```bash
   sudo systemctl restart apache2
   ```

6. **(Optional) Enable Firewall**

   ```bash
   sudo ufw allow in "Apache Full"
   ```

7. **Clone Git Files to `/var/www/`**

   ```bash
   git clone https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer.git /var/www/html
   ```

---

## How to Test the Software

- Clone project with the following command
  ```bash
  git clone https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer.git
- Ensure you are in the correct working directory
- Run UI tests with the following command:
  ```bash
  python3 ./tests/ui/ui_testing_driver.py
### Prerequisites
- Ensure you have [Python](https://www.python.org/downloads/) (version 3.8 or higher) installed.
- Install Selenium WebDriver by running the following command:
  ```bash
  pip install selenium

### Unit and Code Coverage Testing
This is done automatically, at the top of each hour on the server.  There is a bash script called cactest that runs and updates a results file accessbile at:
[Test Results](https://voluntrax.com/ct/volunteer_functions.php.html)

To run the unit tests:
```bash
cd /var/www
php composer-setup.php --install-dir=bin --filename=composer
composer require --dev phpunit/phpunit
/var/www/vendor/bin/phpunit ./tests/VolunteerTest.php --verbose > /tmp/unit-test-results.txt
```

---

## How to Add New Tests

New tests can be added either through Selenium, or by adding additional unit tests to the VolunteerTest.php file located in the tests directory.

