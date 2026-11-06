<?php
declare(strict_types=1);
return ['encryption_key'=>env('BACKUP_ENCRYPTION_KEY'),'directory'=>(env('BACKUP_DIRECTORY')?:dirname(__DIR__).'/storage/private/backups'),'public_directory'=>dirname(__DIR__).'/public','max_bytes'=>(int)env('BACKUP_MAX_BYTES',2147483648)];
