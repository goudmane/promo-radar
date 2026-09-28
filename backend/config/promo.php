<?php
return ['email_enabled'=>env('MAIL_MAILER','log')!=='log','scraper_url'=>env('SCRAPER_URL','http://scraper:4100')];
