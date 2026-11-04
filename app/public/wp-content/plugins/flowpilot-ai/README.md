
# FlowPilot AI

A modular workflow automation plugin for WordPress.

## Current Version

0.1.0 — Workflow Draft Manager

## Features

- WordPress administration dashboard
- Create and edit workflow definitions
- Select workflow triggers
- Configure planned action types
- Custom MySQL workflow table
- Execution-log database foundation
- Administrator permission checks
- WordPress nonce verification
- Input validation and escaping
- Responsive administration interface

## Requirements

- WordPress 6.4+
- PHP 8.0+
- MySQL or MariaDB supported by WordPress

## Installation

1. Copy the flowpilot-ai folder into wp-content/plugins/.
2. Open WordPress → Plugins.
3. Activate FlowPilot AI.
4. Open FlowPilot AI from the admin menu.

## Architecture

- flowpilot-ai.php: plugin bootstrap
- includes/class-fpa-install.php: database schema
- includes/class-fpa-repository.php: data access
- admin/class-fpa-admin.php: admin controller
- admin/views.php: workflow dashboard and editor
- admin/assets/: frontend styles and interactions

## Phase 1 Limitations

Workflows remain drafts and cannot execute.

The following features are not implemented yet:

- Workflow scheduling and execution
- Incoming webhooks
- AI provider integrations
- Workflow activation
- Drag-and-drop React editor
- Retries and execution history
- External CRM and email integrations

## Security

Administrator-only access, nonce-checked
form submissions, validated workflow
types, WordPress database helpers,
and escaped administrative output.

Additional security testing is required
before any production deployment.

## Development Roadmap

Phase 2: React visual workflow builder
Phase 3: Workflow execution engine
Phase 4: Webhook triggers
Phase 5: AI integration
Phase 6: LeadFlow CRM integration
Phase 7: Logging, retries, testing
Phase 8: Production hardening

## License

Add a suitable license before public distribution.
