# User Data Management API

## Overview
This assignment involves building a set of APIs that manage user data, interact with a database, and handle email notifications. The APIs will handle operations such as uploading user data, viewing user data, backing up the database, and restoring the database.


### Build APIs
1. **Upload and Store Data API**
   - **Endpoint**: POST /api/upload
   - **Description**: Allows an admin to upload the `data.csv` file.
   - **Functionality**:
     - Parse the `data.csv` file.
     - Save the data into a database.
     - Send an email to each user upon successful storage.
     - Ensure the email sending does not block the API response.

2. **View Data API**
   - **Endpoint**: GET /api/users
   - **Description**: Allows viewing of all user data stored in the database.

3. **Backup Database API**
   - **Endpoint**: GET /api/backup
   - **Description**: Allows an admin to take a backup of the database.
   - **Functionality**:
     - Generate a backup file (e.g., backup.sql).

4. **Restore Database API**
   - **Endpoint**: POST /api/restore
   - **Description**: Allows an admin to restore the database from the backup.sql file.
   - **Functionality**:
     - Restore the database using the backup file.

### Email Sending
- Utilize an email service to send emails to users upon successful data storage.
- Ensure emails are sent asynchronously to avoid blocking the API response.