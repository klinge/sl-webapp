# Product Overview

SL Member System is a member and activities management application for a sailing club. It provides a web-based interface for managing club members, sailing activities (seglingar), payments (betalningar), and roles (roller).

## Core Features

- Member management (CRUD operations for club members)
- Sailing activity tracking and member participation
- Payment tracking and reporting
- Role-based access control (admin vs regular users)
- User authentication and registration with email verification
- Password reset functionality
- Reporting capabilities (payment reports, member email lists)
- GitHub webhook integration for deployments

## User Types

- **Admin users**: Full access to all management features
- **Regular users**: Limited access to view their own information and update contact details
- **Unauthenticated users**: Can register and log in

## Database

Uses SQLite for data storage (sldb.sqlite for development, sldb-prod.sqlite for production).
