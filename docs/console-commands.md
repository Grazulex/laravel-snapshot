# Console Commands

Laravel Snapshot provides several Artisan commands for managing snapshots from the command line.

## Overview

All commands are prefixed with `snapshot:` and provide comprehensive help via `--help`.

```bash
# List all available snapshot commands
php artisan list snapshot
```

## Commands

### `snapshot:save`

Create a manual snapshot of a model instance.

#### Signature
```bash
php artisan snapshot:save {model} {--label=} {--id=}
```

#### Parameters
- `model` - The fully qualified model class name (e.g., `"App\Models\User"`)

#### Options
- `--label` - Custom label for the snapshot (optional, auto-generated if not provided)
- `--id` - Model ID to snapshot (required)

#### Examples

```bash
# Create a snapshot of User with ID 1
php artisan snapshot:save "App\Models\User" --id=1 --label=user-before-update

# Create a snapshot with auto-generated label
php artisan snapshot:save "App\Models\User" --id=1

# Create a snapshot of an order
php artisan snapshot:save "App\Models\Order" --id=123 --label=order-before-shipping
```

#### Output
```
Snapshot 'user-before-update' created successfully for App\Models\User#1
```

#### Error Handling
- Returns exit code 1 if model not found
- Returns exit code 1 if ID is not provided
- Returns exit code 1 if snapshot creation fails

---

### `snapshot:diff`

Compare two snapshots and display their differences.

#### Signature
```bash
php artisan snapshot:diff {labelA} {labelB}
```

#### Parameters
- `labelA` - First snapshot label
- `labelB` - Second snapshot label

#### Examples

```bash
# Compare two user snapshots
php artisan snapshot:diff user-before-update user-after-update

# Compare order processing snapshots  
php artisan snapshot:diff order-received order-completed
```

#### Output
```
Comparing snapshots: user-before-update vs user-after-update

Modified fields:
  name: "John Doe" → "John Smith"
  email: "john@example.com" → "john.smith@example.com"
  updated_at: "2024-07-19 10:00:00" → "2024-07-19 10:05:00"

Added fields: (none)
Removed fields: (none)
```

#### Advanced Output

For complex changes, the command shows structured output:

```bash
php artisan snapshot:diff order-initial order-final
```

```
Comparing snapshots: order-initial vs order-final

Modified fields:
  status: "pending" → "completed"
  total: 100.00 → 85.00
  items.0.price: 50.00 → 42.50
  items.1.discount: null → 15.00

Added fields:
  payment_method: "credit_card"
  transaction_id: "txn_abc123"

Removed fields: (none)
```

#### Error Handling
- Returns exit code 1 if either snapshot is not found
- Shows detailed error message for missing snapshots

---

### `snapshot:list`

List all available snapshots with filtering options.

#### Signature
```bash
php artisan snapshot:list {--model=} {--limit=50}
```

#### Options
- `--model` - Filter by model class (optional)
- `--limit` - Maximum number of snapshots to show (default: 50)

#### Examples

```bash
# List all snapshots
php artisan snapshot:list

# List only User model snapshots
php artisan snapshot:list --model="App\Models\User"

# List recent 10 snapshots
php artisan snapshot:list --limit=10

# Combine filters
php artisan snapshot:list --model="App\Models\Order" --limit=20
```

#### Output (Table Format)
```
+---------------------------+------------------+------------+-------------+---------------------+
| Label                     | Model           | Model ID   | Event Type  | Created At          |
+---------------------------+------------------+------------+-------------+---------------------+
| user-before-update        | App\Models\User | 1          | manual      | 2024-07-19 10:00:00 |
| user-after-update         | App\Models\User | 1          | manual      | 2024-07-19 10:05:00 |
| order-123-received        | App\Models\Order| 123        | manual      | 2024-07-19 09:30:00 |
| auto-User-1-updated-...   | App\Models\User | 1          | updated     | 2024-07-19 10:05:00 |
+---------------------------+------------------+------------+-------------+---------------------+

Total: 4 snapshots
```

#### Output (JSON Format)
```bash
php artisan snapshot:list --format=json --limit=2
```

```json
[
  {
    "label": "user-before-update",
    "model_type": "App\\Models\\User",
    "model_id": "1",
    "event_type": "manual",
    "created_at": "2024-07-19 10:00:00",
    "data_size": "1.2KB"
  },
  {
    "label": "user-after-update", 
    "model_type": "App\\Models\\User",
    "model_id": "1",
    "event_type": "manual",
    "created_at": "2024-07-19 10:05:00",
    "data_size": "1.3KB"
  }
]
```

---

### `snapshot:report`

Generate comprehensive reports for model snapshots.

#### Signature  
```bash
php artisan snapshot:report {model} {--id=} {--format=html} {--period=} {--output=}
```

#### Parameters
- `model` - The fully qualified model class name (e.g., `"App\Models\User"`)

#### Options
- `--id` - Model ID (optional, generates report for specific model instance)  
- `--format` - Report format: html, json, csv (default: html)
- `--period` - Time period for the report (optional)
- `--output` - Output file path (optional, prints to console if not specified)

#### Examples

```bash
# Generate HTML report for a user
php artisan snapshot:report "App\Models\User" --id=1

# Generate JSON report and save to file
php artisan snapshot:report "App\Models\User" --id=1 --format=json --output=user_report.json

# Generate CSV report for an order
php artisan snapshot:report "App\Models\Order" --id=123 --format=csv --output=order_history.csv

# Generate report for all users (summary)
php artisan snapshot:report "App\Models\User"
```

#### Output (HTML Format)
The HTML report includes:
- Model information and current state
- Complete timeline of all snapshots
- Change summary and statistics
- Visual diff for each change
- Export links for other formats

#### Output (JSON Format)
```json
{
  "model": {
    "type": "App\\Models\\User",
    "id": 1,
    "current_state": {...}
  },
  "statistics": {
    "total_snapshots": 15,
    "snapshots_by_event": {...},
    "most_changed_fields": [...]
  },
  "timeline": [...],
  "generated_at": "2024-07-19T10:15:00Z"
}
```

---

### `snapshot:clear`

Delete snapshots with various filtering options.

#### Signature
```bash
php artisan snapshot:clear {--model=} {--id=} {--event=} {--before=} {--after=}
```

#### Options
- `--model` - Delete snapshots for specific model class only
- `--id` - Delete snapshots for specific model ID only
- `--event` - Delete snapshots of specific event type only
- `--before` - Delete snapshots created before this date (Y-m-d format)
- `--after` - Delete snapshots created after this date (Y-m-d format)

#### Examples

```bash
# Clear all snapshots (with confirmation)
php artisan snapshot:clear

# Clear all User snapshots
php artisan snapshot:clear --model="App\Models\User"

# Clear snapshots for specific user
php artisan snapshot:clear --model="App\Models\User" --id=1

# Clear manual snapshots only
php artisan snapshot:clear --event=manual

# Clear snapshots before a specific date
php artisan snapshot:clear --before=2024-01-01

# Clear snapshots from a specific date range
php artisan snapshot:clear --after=2024-01-01 --before=2024-07-01
```

#### Output
```bash
php artisan snapshot:clear --model="App\Models\User" --dry-run
```

```
Dry run: The following snapshots would be deleted:

- user-before-update (App\Models\User #1, created 2024-07-19 10:00:00)
- user-after-update (App\Models\User #1, created 2024-07-19 10:05:00) 
- auto-User-1-updated-2024-07-19-10-05-00 (App\Models\User #1, created 2024-07-19 10:05:00)

Total: 3 snapshots would be deleted
```

#### Safety Features
- Always prompts for confirmation unless `--confirm` is used
- Shows what will be deleted before proceeding
- Supports dry-run mode for safety
- Provides detailed deletion summary

---

### `snapshot:restore`

Restore a model to a previous snapshot state.

#### Signature
```bash
php artisan snapshot:restore {model} {id} {snapshot} {--dry-run} {--force}
```

#### Parameters
- `model` - The fully qualified model class name (e.g., `"App\Models\User"`)
- `id` - The model ID to restore
- `snapshot` - The snapshot ID or label to restore from

#### Options
- `--dry-run` - Show what would be restored without actually restoring
- `--force` - Force restoration without confirmation

#### Examples

```bash
# Restore a user to a previous snapshot
php artisan snapshot:restore "App\Models\User" 1 user-before-update

# Dry run to see what would be restored
php artisan snapshot:restore "App\Models\User" 1 user-before-update --dry-run

# Force restore without confirmation
php artisan snapshot:restore "App\Models\User" 1 user-before-update --force

# Restore using snapshot ID
php artisan snapshot:restore "App\Models\Order" 123 15
```

#### Output
```
Restoring App\Models\User #1 to snapshot 'user-before-update'...

Changes to be applied:
  name: "John Smith" → "John Doe"
  email: "john.smith@example.com" → "john@example.com"

Do you want to continue? (yes/no) [no]: yes

Model restored successfully!
```

#### Safety Features
- Always prompts for confirmation unless `--force` is used
- Shows what will be changed before proceeding
- Supports dry-run mode for safety
- Validates snapshot exists before attempting restore

---

### `snapshot:schedule`

Create snapshots for models on a scheduled basis.

#### Signature
```bash
php artisan snapshot:schedule {model} {--id=} {--label=} {--limit=100}
```

#### Parameters
- `model` - The fully qualified model class name (e.g., `"App\Models\User"`)

#### Options
- `--id` - Specific model ID (if not provided, snapshots all models of this type)
- `--label` - Custom label prefix for the snapshots
- `--limit` - Limit number of models to snapshot (default: 100)

#### Examples

```bash
# Schedule snapshots for all users (limited to 100)
php artisan snapshot:schedule "App\Models\User"

# Schedule snapshot for specific user
php artisan snapshot:schedule "App\Models\User" --id=1

# Schedule with custom label prefix
php artisan snapshot:schedule "App\Models\Order" --label=daily-backup

# Schedule with custom limit
php artisan snapshot:schedule "App\Models\User" --limit=50
```

#### Output
```
Creating scheduled snapshots for App\Models\User...

Processing 15 models...
✓ User #1: scheduled-User-1-daily-2024-07-19-14-30-00
✓ User #2: scheduled-User-2-daily-2024-07-19-14-30-01  
✓ User #3: scheduled-User-3-daily-2024-07-19-14-30-02
...

Successfully created 15 snapshots.
```

#### Use Cases
- Daily backups via cron jobs
- Bulk snapshot creation
- Scheduled auditing
- Regular state preservation

---

## Advanced Usage

### Chaining Commands

You can chain multiple snapshot commands for complex workflows:

```bash
# Create snapshot, make changes, create another snapshot, then compare
php artisan snapshot:save "App\Models\User" --id=1 --label=before
# ... make changes to user via tinker or another process ...  
php artisan snapshot:save "App\Models\User" --id=1 --label=after
php artisan snapshot:diff before after
```

### Scripting

Commands return appropriate exit codes for use in scripts:

```bash
#!/bin/bash

# Create snapshot before deployment
if php artisan snapshot:save "App\Models\Config" --id=1 --label=before-deploy; then
    echo "Snapshot created successfully"
    
    # Deploy changes...
    
    # Create post-deployment snapshot
    php artisan snapshot:save "App\Models\Config" --id=1 --label=after-deploy
    
    # Compare changes
    php artisan snapshot:diff before-deploy after-deploy
else
    echo "Failed to create snapshot, aborting deployment"
    exit 1
fi
```

### Automated Reports

Generate regular reports via cron:

```bash
# Add to crontab for daily user activity reports
0 2 * * * cd /path/to/app && php artisan snapshot:report --model="App\Models\User" --id=1 --format=html --output=storage/reports/user-$(date +\%Y\%m\%d).html
```

## Configuration

Some command behavior can be configured in `config/snapshot.php`:

```php
'commands' => [
    'default_limit' => 50,           // Default limit for list command
    'max_diff_size' => 10000,        // Max characters to show in diff
    'report_template' => 'default',  // Default report template
    'confirm_destructive' => true,   // Always confirm destructive operations
],
```

## Error Handling

All commands handle errors gracefully:

- **Model not found**: Clear error message with suggestions
- **Snapshot not found**: Lists available snapshots for reference  
- **Permission errors**: File/database permission guidance
- **Storage errors**: Helpful troubleshooting information

## Command Help

Get detailed help for any command:

```bash
php artisan snapshot:save --help
php artisan snapshot:diff --help
php artisan snapshot:list --help
php artisan snapshot:report --help
php artisan snapshot:clear --help
```

## Next Steps

- [Storage Backends Guide](storage-backends.md) 
- [Automatic Snapshots Configuration](automatic-snapshots.md)
- [API Reference](api-reference.md)