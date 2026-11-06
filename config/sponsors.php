<?php
declare(strict_types=1);
// Deployment-owned first-party creatives. Never accept ad HTML or client tier flags.
return ['enabled'=>(bool)env('SPONSOR_BANNERS_ENABLED',true),'campaign'=>['id'=>'sentinel-growth','title'=>env('SPONSOR_TITLE','Sentinel for growing security teams'),'body'=>env('SPONSOR_BODY','Explore the ad-free Essential, Professional and Enterprise plans.'),'cta'=>env('SPONSOR_CTA','View plans'),'url'=>env('SPONSOR_URL','/admin/index.html#company-workspace')]];
