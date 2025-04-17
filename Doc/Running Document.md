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



[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/5Wo5gQYL)

Part 1: List of User Stories
As an agency administrator, I want to register my agency with the software so that I can begin using it.
As an agency administrator, I want to invite other agency employees/system admins/volunteer coordinators to access the platform so that they can assist with supporting/utilizing the software.
As an agency administrator, I want to deactivate agency employees/system admins/volunteer coordinators so that, once they’ve left the agency, they no longer have access to the platform.
As a volunteer coordinator, I want to add volunteer locations so that volunteers can be assigned to a location.
As a volunteer coordinator, I want the ability to store emergency contact information regarding the active volunteers so that in case of an emergency, we know who to contact.
As a volunteer coordinator, I want to register volunteers so that there is a comprehensive list of volunteers associated with the agency.
As a volunteer coordinator, I want to deactivate volunteers so that, once they are no longer serving with the agency, they are removed from the active listing.
As a volunteer coordinator, I want to export reports of volunteers for syncing with other systems.
As a volunteer coordinator, I want to export reports of volunteer hours for syncing with other systems.
As a volunteer coordinator, I want a dashboard/real-time view of which volunteers are currently serving at which locations.
As a volunteer coordinator, I want to set up a printed/kiosk QR code, specifically to a location, so that volunteers can scan and sign in/out of their service hours.
As a volunteer, I want to scan in/out via QR code when arriving/departing from service hours so that my service hours and location are automatically tracked.
As a volunteer, I want to request and receive a digest of my service hours so that I can report back to another third party. 


Part 2: Use Case Diagram 

Part 3: Requirements and Specifications:
There shall be a requirement in which an agency administrator can register their agency with the software. It shall use a MySQL database along with bcrypt to securely store the user data. 
There shall be a requirement to invite other agency entities to the platform. This shall be done by using a hyperlink sent by email which redirects to the login page. 
There shall be a requirement which allows agency administrators to remove volunteers who are no longer active. This shall be done by implementing administrative controls in the dashboard which will remove user information from the MySQL database.
There shall be a requirement which allows agency administrators can, add locations so that volunteers can be assigned to a location. This shall be done by having an attribute in our MySQL database which keeps track of location. 
There shall be a requirement which allows agency administrators to store the emergency contacts of volunteers. This shall be done by having an attribute in our MySQL database which keeps track of emergency contacts.
There shall be a requirement which allows agency administrators to add volunteers so they are associated with their agency. This shall be done by having an attribute in our MySQL database which keeps track of emergency contacts.
There shall be a requirement which allows agency administrators to export the volunteer data to excel and other formats. This shall be implemented with existing MySQL functionality. 
There shall be a requirement which allows agency administrators to view a real time dashboard which syncs with stores volunteer data. This shall be implemented with a HTML/CSS/JS web app which calls GET requests from our database.
There shall be a requirement which allows agency administrators to generate a QR code which will allow volunteers to sign into an associated event. This shall be implemented with an existing QR code generation library.
There shall be a requirement which allows volunteers to use their camera to scan the QR code and check into an event. This shall be implemented by generating a link which triggers an event in the MySQL database to generate a new tuple in the check in relation. 
	
Part 4: Glossary
QR (Quick Response) Code: A type of 2D barcode that stores information, such as a website link or contact details, in a square grid of black and white squares. A common way QR codes are processed are by the camera of a personal smartphone. 
sync: Syncing is the process by which data is shared between two systems using a common, predefined format for the purpose of maintaining an accurate duplication of the data in both systems.
BCRYPT: is a password hashing algorithm that turns a plain password into a scrambled, secure version that's hard to reverse. It's designed to protect passwords by making it slow and difficult for hackers to guess them using brute force or other attacks.
Database: is an organized collection of information that a computer can easily access, manage, and update. It stores data in a structured way, often in tables, so it can be quickly searched, sorted, updated, and used.
GET Request: A GET request is a way your web browser asks a website for information. The information is transferred via the hyperlink.
hyperlink: is a clickable link, usually in text or an image, that takes you to another webpage or part of a document.
Tuple: A row in a table. It represents one item or record in the data.
Relation: A table of data made up of rows and columns. Each row is a tuple, and each column is an attribute.
Attribute: A column in the table. It describes one piece of information in a tuple.
Web App: A web app (short for web application) is a program you use through a web browser, like Chrome or Safari. Instead of installing it on your computer, you just go to a website, and it works online.
