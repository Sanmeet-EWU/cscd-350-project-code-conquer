# VolunTrax by Code & Conquer ⚖️
[![Demo Video](https://i9.ytimg.com/vi_webp/VdgTkMpCSSs/mq2.webp?sqp=COjNocIG-oaymwEmCMACELQB8quKqQMa8AEB-AH-CYAC0AWKAgwIABABGC8gZSgrMA8=&rs=AOn4CLAgH3jpxxa4ILrtzg_u62rdh2jcSg)](https://youtu.be/VdgTkMpCSSs?si=SqvYzlEZajvCIPWZ)
<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
[Click here to watch a demo](https://youtu.be/VdgTkMpCSSs?si=SqvYzlEZajvCIPWZ)

## Quck Links:
[Project Overview](https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/blob/main/Doc/Running%20Document.md)<br>
[Developer Guidelines](https://github.com/Sanmeet-EWU/cscd-350-project-code-conquer/blob/main/Doc/Developer%20Documentation.md)


## Problem Statement
   
Due to funding restrictions, it is often very difficult for non-profit organizations to track the hours and location of all of their various volunteers. Often, this burden falls on a volunteer coordinator that is responsible for trying to keep track of every Volunteer across multiple sites. Considering that many volunteers do so, because of either an academic or legal requirement, our Tracking is of Keyport. Further, for safety reasons, it’s important that the organization knows where all of their active volunteers are located in case of an emergency.

## Intended User
The intended user would, at least initially, be US based non-profit organizations that hosts volunteers.

## Understanding the Problem
The problem is the result of a lack of funding allocated towards technological solutions, as well as a shortage of staffing to track volunteers manually.

## User Benefit
This software would fill a need that reduces both manual staffing burden, as well as would provide a solution that needs minimal ongoing maintenance. 

## Testing

This section explains how to run UI tests for the project using Selenium.
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
