Project Description:

# VolunTrax by Code & Conquer ⚖️
 
## Problem Statement
   
Due to funding restrictions, it is often very difficult for non-profit organizations to track the hours and location of all of their various volunteers. Often, this burden falls on a volunteer coordinator that is responsible for trying to keep track of every Volunteer across multiple sites. Considering that many volunteers do so, because of either an academic or legal requirement, our Tracking is of Keyport. Further, for safety reasons, it’s important that the organization knows where all of their active volunteers are located in case of an emergency.

## Intended User
The intended user would, at least initially, be US based non-profit organizations that hosts volunteers.

## Understanding the Problem
The problem is the result of a lack of funding allocated towards technological solutions, as well as a shortage of staffing to track volunteers manually.

## User Benefit
This software would fill a need that reduces both manual staffing burden, as well as would provide a solution that needs minimal ongoing maintenance. 



## User Stories
- As an agency administrator, I want to register my agency with the software so that I can begin using it.  
- As an agency administrator, I want to invite other agency employees/system admins/volunteer coordinators to access the platform so that they can assist with supporting/utilizing the software.  
- As an agency administrator, I want to deactivate agency employees/system admins/volunteer coordinators so that, once they’ve left the agency, they no longer have access to the platform.  
- As a volunteer coordinator, I want to add volunteer locations so that volunteers can be assigned to a location.  
- As a volunteer coordinator, I want the ability to store emergency contact information regarding the active volunteers so that in case of an emergency, we know who to contact.  
- As a volunteer coordinator, I want to register volunteers so that there is a comprehensive list of volunteers associated with the agency.  
- As a volunteer coordinator, I want to deactivate volunteers so that, once they are no longer serving with the agency, they are removed from the active listing.
- As a volunteer coordinator, I want to export reports of volunteers for syncing with other systems.  
- As a volunteer coordinator, I want to export reports of volunteer hours for syncing with other systems.  
- As a volunteer coordinator, I want a dashboard/real-time view of which volunteers are currently serving at which locations.  
- As a volunteer coordinator, I want to set up a printed/kiosk QR code, specifically to a location, so that volunteers can scan and sign in/out of their service hours.  
- As a volunteer, I want to scan in/out via QR code when arriving/departing from service hours so that my service hours and location are automatically tracked.  
- As a volunteer, I want to request and receive a digest of my service hours so that I can report back to another third party.   


## Case Diagram
![Use Case Diagram](https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/blob/96d88688fc552dd4360bf26b8264104944caa01d/Doc/VT%20Use%20Case%20Diagram.png)



## Requirements and Specifications:
- There shall be a requirement in which an agency administrator can register their agency with the software. It shall use a MySQL database along with bcrypt to securely store the user data.  
- There shall be a requirement to invite other agency entities to the platform. This shall be done by using a hyperlink sent by email which redirects to the login page.  
- There shall be a requirement which allows agency administrators to remove volunteers who are no longer active. This shall be done by implementing administrative controls in the dashboard which will remove user information from the MySQL database.  
- There shall be a requirement which allows agency administrators can, add locations so that volunteers can be assigned to a location. This shall be done by having an attribute in our MySQL database which keeps track of location.  
- There shall be a requirement which allows agency administrators to store the emergency contacts of volunteers. This shall be done by having an attribute in our MySQL database which keeps track of emergency contacts.  
- There shall be a requirement which allows agency administrators to add volunteers so they are associated with their agency. This shall be done by having an attribute in our MySQL database which keeps track of emergency contacts.  
- There shall be a requirement which allows agency administrators to export the volunteer data to excel and other formats. This shall be implemented with existing MySQL functionality.  
- There shall be a requirement which allows agency administrators to view a real time dashboard which syncs with stores volunteer data. This shall be implemented with a HTML/CSS/JS web app which calls GET requests from our database.  
- There shall be a requirement which allows agency administrators to generate a QR code which will allow volunteers to sign into an associated event. This shall be implemented with an existing QR code generation library.  
- There shall be a requirement which allows volunteers to use their camera to scan the QR code and check into an event. This shall be implemented by generating a link which triggers an event in the MySQL database to generate a new tuple in the check in relation.   
	
## 📘 Glossary

**Attribute**  
A column in the table. It describes one piece of information in a tuple.

**BCRYPT**  
A password hashing algorithm that turns a plain password into a scrambled, secure version that's hard to reverse. It's designed to protect passwords by making it slow and difficult for hackers to guess them using brute force or other attacks.

**Database**  
An organized collection of information that a computer can easily access, manage, and update. It stores data in a structured way, often in tables, so it can be quickly searched, sorted, updated, and used.

**GET Request**  
A GET request is a way your web browser asks a website for information. The information is transferred via the hyperlink.

**Hyperlink**  
A clickable link, usually in text or an image, that takes you to another webpage or part of a document.

**QR (Quick Response) Code**  
A type of 2D barcode that stores information, such as a website link or contact details, in a square grid of black and white squares. A common way QR codes are processed is by the camera of a personal smartphone.

**Relation**  
A table of data made up of rows and columns. Each row is a tuple, and each column is an attribute.

**Sync**  
Syncing is the process by which data is shared between two systems using a common, predefined format for the purpose of maintaining an accurate duplication of the data in both systems.

**Tuple**  
A row in a table. It represents one item or record in the data.

**Web App**  
A web app (short for web application) is a program you use through a web browser, like Chrome or Safari. Instead of installing it on your computer, you just go to a website, and it works online.


## Software Architecture
### System Overview
The purpose of our software, VolunTrax, is to ease the burden of tracking the hours served and real-time location of a multitude of volunteers across multiple locations.
Frontend: This will consist of a web-app/browser based application with the front developed with a mix of CSS, HTML, PHP, and Javascript.
Backend: The structure of the backend will be hosted on a cloud-based droplet stored on Google gSuite’s Developer Cloud.  The droplet is running Ubuntu, and is hosting the site using Apache.  The database is stored in MySQL, and we are leveraging the “bcrypt” algorithm for user credential security.
APIs: Messaging will be accomplished using an API with Mailgun.  The QR code generation will be managed with an API to quickcharts.io.

### Data Storage
This application is based on an instance of MySQL with a set of relations that include:
- Orgs - Table to store the details about the various organizations that will use the application.
- Users - a table to store user information and credentials along with their organization associations.
- Locations - a table of locations associated with the organization.
- Volunteers - a table of volunteers who are registered to the organization.
- ER-Contact - a table of emergency contacts associated with the volunteers.
- Check-in - a table itemizing the checking in and checking out of each volunteer, the time and date of the check in/out, and the location based on a QR code scanned on location.
	- This table will be able to provide both the current location and count of “checked in” volunteers, as well as a digest of hours served, organized by volunteers. 
 
### Schema Diagram
![Schema Diagram](https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/blob/main/Doc/Schema%20Diagram.png)

### Architectural Assumptions
The architecture assumptions include:  
 - The presence of internet access
 - Volunteers having smartphones capable of QR scanning OR
 - A kiosk present at the location that can be used instead

### Component Diagram
![Component Diagram](https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/blob/main/Doc/Component%20Diagram.png)

## Software Design
### User Interface Component
#### Internal design structure
- Packages:
	- HTML
		- Responsibilities: The HTML acts as the skeleton of the web application. It provides structure to the information and data we present to our users. The HTML must be organized in an intuitive manner so that the application is readable and easily accessible to anyone.
	- Javascript
		- Responsibilities: Javascript acts as the brains of the web application. It provides functionality to the static HTML components. It provides the ability to open connections between the client and the server. Along with this it handles the logic used to generate the QR code. Another key use is the handling of user sign in and account registration.
	- CSS
		- Responsibilities: CSS takes the bare-bones of the HTML and tells our web app how it should look. The CSS should create a web-application which is appealing to our users. It should also provide a consistent design/theme identity throughout the web application. 
	- JQuery
		- Responsibilities: JQuery allows us to simplify DOM manipulation, animation handling, event handling and Ajax interactions. Essentially it simplifies many javascript operations in order to speed up the development process.
	- Tailwind CSS
		- Responsibilities: Tailwind CSS offers premade CSS styles to use out-of-the-box so that we can speed up development and create a visually appealing web app.
- Classes:
	- Window
		- Responsibilities: The Window class is our main container in the web app. Everything else is a subcomponent of this class. The window class keeps track of session information, ensuring that a logged user is authenticated properly.
	- Dashboard
		- Responsibilities: The Dashboard class holds the components related to data presentation and data manipulation. The dashboard class is encapsulated by the Window class so that the dashboard can sync with the data of the application. The user needs to be able to interact with recorded data and to also generate QR codes. Along with this the user will need to be able 
	- Login/Sign-In Form
		- Responsibilities: The Login/Sign-In form encapsulated by the Window class. It acts as an interface to input data which can be sent to the Business Component of the application. Apon authentication, the relevant session information is communicated to the Window class so that it can be kept track of across the application. 
	- Data Viewer/Export Form
		- Responsibilities: The Data Viewer/Export Form is encapsulated by the Dashboard class. It takes care of retrieving data from the Business Component relevant to the logged session id (The logged administrator) and then presenting that data so that the client can view it. 
- Modules:
	- QR Code Generation Library
		- Responsibilities: This is a pre-built module we will leverage to handle QR code generation. 
	- Bcrypt Encryption Library
		- Responsibilities: This is a pre-built module we will leverage to handle password hashing. The component will then send this information to the Business Component.

### Business Component
#### Internal Design Structure
- Packages
	- PHP
		- Responsibilities: PHP handles logic before our website is served to a client. It allows us to instantiate the client session and set initial variables before the client receives the website. 
	- APACHE
		- Responsibilities: APACHE is an open source web server that can serve web content on the internet. APACHE will server HTML/CSS/JAVASCRIPT to our clients from our server.
- Classes
	- Session Manager
		- Responsibilities: The session manager is an object which manages active sessions. It ensures that old sessions are terminated. And that new sessions are properly authenticated. 
- Modules
	- QR Code Form
		- Responsibilities:  The QR Code form is encapsulated by the Dashboard class. It holds all the logic needed to generate a QR Code which embeds a link which can send signals to the Business Component that a volunteer would like to check in.

### Data Access Component
#### Internal Design Structure
- Packages
	- AJAX
		- Responsibilities: AJAX acts as a data bridge between the client and the server. It handles asynchronous calls from the client and serves data back to it.
	- MYSQL
		- Responsibilities: MYSQL is a relational database management system. It will allow us to easily create relations that store different attributes. It will also allow us to easily make queries on existing tables.
- Classes
	- Data Manager 
		- Responsibilities: The Data Manager will act as the bridge between the Business Component and our MYSQL database. It will handle the creation of new tuples in our database along with processing queries for the Business Component.



