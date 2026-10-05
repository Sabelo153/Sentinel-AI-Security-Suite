# Local patrol test setup

This guide prepares only the local `sentinel_ai_security` development database for a patrol demonstration. It does not touch a production database.

The Sentinel runtime account intentionally cannot create tables or seed global reference records. Open HeidiSQL, phpMyAdmin, or another local MariaDB administration tool using the migration administrator account, select `sentinel_ai_security`, and run these files in order:

1. `database/schema/065_patrol_schedule_windows.sql`
2. `database/seed/local/001_patrol_workflow_reference_data.sql`

Confirm the following query returns five active rows before opening Sentinel:

```sql
SELECT authority_code, authority_name, rank_value
FROM authority_levels
WHERE authority_status = 'ACTIVE'
ORDER BY rank_value;
```

Then sign in as the test company owner and follow **Patrol routes → Set up a patrol** in the admin site:

1. Create a `GUARD` position.
2. Create an active employee assignment using that position and the `Guard` authority level.
3. Create an assigned shift at the same site.
4. Create an active route and ordered checkpoints. Give each checkpoint its printed QR value or NFC tag UID.
5. Create a patrol schedule window inside that shift.
6. Sign in to Sentinel Guard and synchronize. The guard sees the shift, route, schedule, patrol history, and checkpoint evidence.

A window becomes `MISSED` only once it ends without a matching patrol start. A later offline synchronization can supply the missing evidence, so supervisors should contact the guard before treating the result as misconduct.
