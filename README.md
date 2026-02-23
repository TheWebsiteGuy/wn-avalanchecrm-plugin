# Nexus CRM Plugin

A comprehensive Customer Relationship Management (CRM) plugin for Winter CMS, designed to streamline client management, project tracking, invoicing, support tickets, and subscription handling directly within your CMS backend and frontend.

## Features

*   **Client Management**: Keep track of client details and associate them with users.
*   **Projects & Tasks**: Create projects for clients, assign staff, and track tasks using a Kanban board.
*   **Invoicing**: Generate professional PDF invoices for clients.
    *   Automatic PDF generation using DomPDF.
    *   Support for invoice items, tax calculation, and currency settings.
*   **Support Tickets**: Integrated ticket system for client support requests. Features categories and status tracking.
*   **Subscriptions**: Manage recurring billing plans and subscriptions for clients.
    *   Tracks active subscriptions, payment methods, and plans.
*   **Staff Management**: Manage internal staff members and their roles.
*   **Payment Gateway Integration**: Built-in webhook controllers for major payment gateways:
    *   Stripe
    *   PayPal
    *   GoCardless
*   **Frontend Components**: Ready-to-use components for client portals:
    *   `clientInvoices`: Display a list of invoices and details.
    *   `clientProjects`: Show project lists and details.
    *   `clientTickets`: Allow clients to view and create support tickets.
    *   `subscriptions`: Manage subscription plans and active subscriptions.

## Requirements

*   Winter CMS (or October CMS)
*   Winter.User Plugin
*   PHP `dompdf` extension (usually handled via composer)

## Installation

1.  Clone this repository into `plugins/thewebsiteguy/nexuscrm`.
2.  Run `php artisan plugin:refresh TheWebsiteGuy.NexusCRM` to migrate the database tables.

## Configuration

To configure the plugin settings, go to **Settings > CRM > Nexus CRM** in the backend.
*   **Currency Symbol**: Set the currency symbol for invoices (default: $).
*   **Invoice Settings**: Configure invoice templates and details.

## Components

This plugin provides several components to build a client portal on the frontend:

### Client Invoices
Displays a list of invoices for the logged-in user.
```ini
[clientInvoices]
perPage = 10
```

### Client Projects
Displays projects associated with the client.
```ini
[clientProjects]
perPage = 10
```

### Client Tickets
A system for clients to open and track support tickets.
```ini
[clientTickets]
perPage = 10
```

### Subscriptions
Manages user subscriptions and plans.

## Permissions

The plugin registers several permissions for backend access:
*   Manage Clients
*   Manage Projects
*   Manage Invoices
*   Manage Tickets
*   Manage Settings

## Support

For issues and feature requests, please contact TheWebsiteGuy.
