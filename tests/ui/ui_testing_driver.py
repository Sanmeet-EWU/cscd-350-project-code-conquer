from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

print("--- UI TESTING FOR VOLUNTRAX.COM --- \n")

secret = "XXXXXXXXXXXX"
email = "staff@voluntrax.com"

driver = webdriver.Chrome()
driver.get("https://voluntrax.com/")

def test_ui_title(driver):

    t = "WEB TITLE"
    m = ""
    title = driver.title

    if title == "VolunTrax - Streamline Your Volunteer Management":
        m = "PASSED"
        
    else:
        m = " !! FAILED !!"
        
    p_m(t, m);

def test_ui_button_login(driver):
    
    t = "LOGIN BUTTON"
    m = ""
    link = driver.find_element(By.LINK_TEXT, "Login")
    link.click()
    e_u = "https://voluntrax.com/login.php"
    c_u = driver.current_url

    if c_u == e_u:
        m = "PASSED"
        
    else:
        m = " !! FAILED !!"
        
    p_m(t, m)

def test_sign_in(driver):

    t = "SIGN IN"
    m = ""
    email_box = driver.find_element(By.ID, "email")
    password_box = driver.find_element(By.ID, "password")

    email_box.send_keys(email)
    password_box.send_keys(secret)
    button = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//button[contains(@class, 'w-full') and contains(@class, 'bg-forest-accent')]"))
    )
    
    button.click();

    if driver.title == "Staff Dashboard - VolunTrax":
        m = "PASSED"
    else:
        m = " !! FAILED !!"

    p_m(t, m)

def test_add_organization_nav(driver):
    t = "ADD ORG NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='organization_registration.php'].bg-forest-accent.text-white.p-4"))
    )
    link.click()

    if driver.current_url == "https://voluntrax.com/organization_registration.php":
        m = "PASSED"
    else:
        m = " !! FAILED !!"
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='staff_dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()
    p_m(t, m)

def test_add_manage_users_nav(driver):
    t = "MANAGE USERS NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_users.php'].bg-forest-accent.text-white.p-4"))
    )
    link.click()

    if driver.current_url == "https://voluntrax.com/manage_users.php":
        m = "PASSED"
    else: 
        m = "!! FAILED !!"
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='staff_dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()
    p_m(t, m)

def test_switch_to_standard_view(driver):
    t = "STANDARD VIEW NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()

    if driver.current_url == "https://voluntrax.com/dashboard.php":
        m = "PASSED"
    else:
        m = "!! FAILED !!"
    p_m(t, m)

def test_manage_volunteer_nav(driver):
    t = "MANAGE VOLUNTEER NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_volunteers.php?action=add'].bg-forest-accent.text-white.p-4"))
    )
    link.click()
    
    if driver.current_url == "https://voluntrax.com/manage_volunteers.php?action=add":
        m = "PASSED"
    else:
        m = "!! FAILED !!"
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()
    p_m(t, m)

def test_manage_locations_nav(driver):
    t = "MANAGE LOCATIONS NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_locations.php'].bg-forest-accent.text-white.p-4"))
    )
    link.click()
    if driver.current_url == "https://voluntrax.com/manage_locations.php":
        m = "PASSED"
    else:
        m = "!! FAILED !!"
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()
    p_m(t,m)

def test_manage_qr_code_nav(driver):
    t = "MANAGE QR CODE NAVIGATION"
    m = ""
    
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_checkins.php'].bg-forest-accent.text-white.p-4"))
    )

    link.click()
    if driver.current_url == "https://voluntrax.com/manage_checkins.php":
        m = "PASSED"
    else:
        m = "!! FAILED !!"
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()
    p_m(t, m)

def test_hours_report_nav(driver):
    t = "HOURS REPORT NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='volunteer_hours.php'].bg-forest-accent.text-white.p-4"))
    )

    link.click()
    if driver.current_url == "https://voluntrax.com/volunteer_hours.php":
        m = "PASSED"
    else:
        m = "!! FAILED !!"

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()

    p_m(t,m)

def test_logout_nav(driver):
    t = "LOGOUT NAVIGATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='logout.php'].bg-red-500.text-white.px-4.py-2"))
    )

    link.click()
    if driver.current_url == "https://voluntrax.com/":
        m = "PASSED"
    else:
        m = "!! FAILED !!"

    p_m(t,m)


def navigate_to_dashboard(driver):
    link = driver.find_element(By.LINK_TEXT, "Login")
    link.click()
    email_box = driver.find_element(By.ID, "email")
    password_box = driver.find_element(By.ID, "password")

    email_box.send_keys(email)
    password_box.send_keys(secret)
    button = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//button[contains(@class, 'w-full') and contains(@class, 'bg-forest-accent')]"))
    )
    
    button.click();
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()

def return_to_dashboard(driver):
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()

def test_add_volunteer(driver):
    t = "ADD VOLUNTEER"
    m = ""
    navigate_to_dashboard(driver)    
    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_volunteers.php?action=add'].bg-forest-accent.text-white.p-4"))
    )
    link.click()

    fname_box = driver.find_element(By.ID, "fname")
    lname_box = driver.find_element(By.ID, "lname")
    dob_box = driver.find_element(By.ID, "dob")
    email_box = driver.find_element(By.ID, "email")

    fname_box.send_keys("UI_TESTING_USER_FNAME")
    lname_box.send_keys("UI_TESTING_USER_LNAME")
    dob_box.send_keys("01012025")
    email_box.send_keys("UI@TEST.COM")

    button = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//button[contains(@class, 'text-white') and contains(@class, 'bg-forest-accent')]"))
    )

    button.click()

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_volunteers.php'].text-forest-accent"))
    )

    link.click()

    div_element = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "div.bg-white.rounded-lg.shadow-lg.p-6.mb-8"))
    )

    child_elements = div_element.find_elements(By.CSS_SELECTOR, "*")

    element_texts = [element.text for element in child_elements if element.text]

    m = "!! FAILED !!"
    for e in element_texts:
        if e == "UI@TEST.COM":
            m = "PASSED"
    p_m(t, m)


    # Find table row with volunteer name (replace 'John Doe' with actual name)
    row = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.XPATH, f"//tr[td[contains(text(), 'UI_TESTING_USER_FNAME UI_TESTING_USER_LNAME')]]"))
    )

    # Click delete button in the row
    delete_button = row.find_element(By.XPATH, ".//form[@class='inline']//button[@title='Delete']")
    delete_button.click()

    # Accept JavaScript confirm pop-up
    alert = WebDriverWait(driver, 5).until(EC.alert_is_present())
    alert.accept()

    div_element = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.CSS_SELECTOR, "div.bg-white.rounded-lg.shadow-lg.p-6.mb-8"))
    )

    child_elements = div_element.find_elements(By.CSS_SELECTOR, "*")

    element_texts = [element.text for element in child_elements if element.text]
    t = "DELETE VOLUNTEER"
    m = "PASSED"
    for e in element_texts:
        if e == "UI@TEST.COM":
            m = "!! FAILED !!"
    p_m(t, m)

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='dashboard.php'].bg-forest-accent.text-white.px-4.py-2"))
    )
    link.click()




def test_add_location(driver):
    t = "ADD LOCATION"
    m = ""

    link = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.CSS_SELECTOR, "a[href='manage_locations.php'].bg-forest-accent.text-white.p-4"))
    )
    link.click()

    loc_box = driver.find_element(By.ID, "location_name")
    loc_box.send_keys("TESTLOC")

    button = WebDriverWait(driver, 10).until(
        EC.element_to_be_clickable((By.XPATH, "//button[contains(@class, 'w-full') and contains(@class, 'bg-forest-accent')]"))
    )
    button.click()

    div_element = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.XPATH, f"//div[h3[contains(text(), 'Current Locations')]]"))
    )
    
    child_elements = div_element.find_elements(By.CSS_SELECTOR, "*")

    element_texts = [element.text for element in child_elements if element.text]
    
    m = "!! FAILED !!"
    for e in element_texts: 
        if e == "TESTLOC":
            m = "PASSED"
    p_m(t, m)

    
    # Find table row with volunteer name (replace 'John Doe' with actual name)
    row = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.XPATH, f"//tr[td[contains(text(), 'TESTLOC')]]"))
    )

    # Click delete button in the row
    delete_button = row.find_element(By.XPATH, ".//form[@class='inline']//button[@title='Delete']")
    delete_button.click()

    # Accept JavaScript confirm pop-up
    alert = WebDriverWait(driver, 5).until(EC.alert_is_present())
    alert.accept()

    div_element = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.XPATH, f"//div[h3[contains(text(), 'Current Locations')]]"))
    )

    child_elements = div_element.find_elements(By.CSS_SELECTOR, "*")

    element_texts = [element.text for element in child_elements if element.text]
    t = "DELETE LOCATION"
    m = "PASSED"
    for e in element_texts:
        if e == "TESTLOC":
            m = "!! FAILED !!"
    p_m(t, m)

  
def p_m(t, m):
    print("[TEST][" + t + "] -- Status: " + m )

start_time = time.time()
test_ui_title(driver)
test_ui_button_login(driver)
test_sign_in(driver)
test_add_organization_nav(driver)
test_add_manage_users_nav(driver)
test_switch_to_standard_view(driver)
test_manage_volunteer_nav(driver)
test_manage_locations_nav(driver)
test_manage_qr_code_nav(driver)
test_hours_report_nav(driver)
test_logout_nav(driver)
test_add_volunteer(driver)
test_add_location(driver)

end_time = time.time()
elapsed = end_time - start_time
print(f"\nTime elapsed: {elapsed:.2f} seconds.")
driver.quit()
