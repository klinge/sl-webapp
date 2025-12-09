# Automated Deployment System

## Overview

This application uses a two-stage automated deployment system triggered by GitHub webhooks. When code is pushed to a release branch, the system automatically updates a staging directory and schedules deployment to production via a cron job.

## Architecture

### Stage 1: Webhook Processing
1. GitHub sends webhook on push to release branch
2. WebhookController validates the request
3. GitRepositoryService updates staging repository
4. DeploymentService creates trigger file

### Stage 2: Cron Job Deployment
1. Cron job monitors trigger directory
2. When trigger file detected, copies staging to production
3. Removes trigger file after successful deployment

## Components

### WebhookController
**Location:** `App/Controllers/WebhookController.php`

Receives and processes GitHub webhook requests. Performs security validation and orchestrates the deployment workflow.

### GitHubService
**Location:** `App/Services/Github/GitHubService.php`

Validates webhook authenticity and checks deployment criteria:
- **Signature validation:** HMAC-SHA256 using shared secret
- **Repository validation:** Only accepts repo ID `781366756`
- **Branch validation:** Only deploys branches matching `release/vX.Y` pattern

### GitRepositoryService
**Location:** `App/Services/Github/GitRepositoryService.php`

Manages git operations in the staging directory:
- Clones repository if not present
- Fetches latest changes from remote
- Checks out specified release branch
- Pulls latest commits

### DeploymentService
**Location:** `App/Services/Github/DeploymentService.php`

Creates trigger files to signal deployment readiness. Files are named `deploy_{timestamp}.trigger`.

## Configuration

### Environment Variables

Add these to your `.env` file:

```shell
# GitHub webhook secret (must match GitHub webhook configuration)
GITHUB_WEBHOOK_SECRET=your_secret_here

# Directory where staging repository is cloned/updated
REPO_BASE_DIRECTORY=/var/staging

# Directory where trigger files are created
TRIGGER_FILE_DIRECTORY=/var/triggers
```

### GitHub Webhook Setup

1. Go to your GitHub repository settings
2. Navigate to Webhooks → Add webhook
3. Configure:
   - **Payload URL:** `https://yourdomain.com/webhook/github`
   - **Content type:** `application/json`
   - **Secret:** Same value as `GITHUB_WEBHOOK_SECRET` in `.env`
   - **Events:** Select "Just the push event"
   - **Active:** Check the box

## Release Branch Pattern

Only branches matching the pattern `release/vX.Y` trigger deployment:

**Valid examples:**
- `release/v1.0`
- `release/v2.5`
- `release/v10.15`

**Invalid examples:**
- `main`
- `develop`
- `release/test`
- `v1.0`

## Security

### Webhook Validation
Every webhook request is validated through multiple checks:

1. **Event type:** Must be `push` or `ping`
2. **Signature header:** Must include `X-Hub-Signature-256`
3. **Signature format:** Must be `sha256=<hash>`
4. **HMAC validation:** Signature must match computed HMAC-SHA256
5. **Repository ID:** Must match configured repository (781366756)
6. **Branch pattern:** Must match release branch pattern

### Repository ID
The system only processes webhooks from repository ID `781366756`. This prevents unauthorized deployments from other repositories.

## Deployment Workflow

### 1. Push to Release Branch
```bash
git checkout -b release/v1.0
git push origin release/v1.0
```

### 2. GitHub Sends Webhook
GitHub automatically sends a webhook to your configured endpoint.

### 3. Webhook Processing
- WebhookController validates the request
- GitRepositoryService updates staging directory
- DeploymentService creates trigger file

### 4. Cron Job Deployment
**TODO: Add cron job details when production server is accessible**

## Directory Structure

```
/var/staging/
└── sl-webapp/          # Staging repository (updated by webhook)

/var/triggers/
└── deploy_*.trigger    # Trigger files (created by webhook, consumed by cron)

/var/www/html/
└── sl-webapp/          # Production directory (updated by cron job)
```

## Troubleshooting

### Check Webhook Logs
Application logs include detailed webhook processing information:
```bash
tail -f /path/to/logs/app.log | grep webhook
```

### Verify Staging Repository
```bash
cd /var/staging/sl-webapp
git status
git log -1
```

### Check Trigger Files
```bash
ls -la /var/triggers/
```

### Test Webhook Manually
Use GitHub's webhook "Recent Deliveries" to redeliver a webhook and check the response.

## Cron Job Configuration

**TODO: Document cron job setup**

Topics to cover:
- Cron schedule (how often to check for triggers)
- Script location and permissions
- Deployment script logic
- Error handling and logging
- Rollback procedures

## Maintenance

### Updating Webhook Secret
1. Update `GITHUB_WEBHOOK_SECRET` in `.env`
2. Update secret in GitHub webhook settings
3. Restart application if needed

### Changing Repository ID
Update the constant in `App/Services/Github/GitHubService.php`:
```php
private const REPOSITORY_ID = 781366756;
```

### Modifying Release Branch Pattern
Update the regex in `App/Services/Github/GitHubService.php`:
```php
private const RELEASE_BRANCH_PATTERN = '/^release\/v\d+(\.\d+)?$/';
```

## Related Files

- `App/Controllers/WebhookController.php` - Main webhook handler
- `App/Services/Github/GitHubService.php` - Webhook validation
- `App/Services/Github/GitRepositoryService.php` - Git operations
- `App/Services/Github/DeploymentService.php` - Trigger file creation
- `App/Config/ContainerConfigurator.php` - DI configuration (lines 48-55)
