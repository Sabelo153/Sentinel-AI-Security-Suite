<?php
declare(strict_types=1);
return ['host'=>env('REPORT_SMTP_HOST',''),'port'=>(int)env('REPORT_SMTP_PORT',587),'encryption'=>env('REPORT_SMTP_ENCRYPTION','tls'),'username'=>env('REPORT_SMTP_USERNAME',''),'password'=>env('REPORT_SMTP_PASSWORD',''),'from_address'=>env('REPORT_FROM_ADDRESS',''),'from_name'=>env('REPORT_FROM_NAME','Sentinel Reports'),'portal_url'=>env('REPORT_PORTAL_URL','')];
