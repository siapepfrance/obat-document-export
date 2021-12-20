Obat Document Export
====================

This tool allow you to export easily your documents from Obat when migrating to CRM.
You can export your documents as xlsx or csv format. The exported files are created in exports forlder.


Getting Started
---------------
#### 1 - Install dependencies
```
$ php -d memory_limit=-1 composer.phar install
```

#### 2 - Create the .env file
```
$ cp .env.dist .env
```

#### 3 - Fill the .env file with your Obat account configurations
* 1 - Log in to your Obat account
* 2 - Open the developer panel ( F12 on windows or CMD+ALT+I on MacOs ) > Network tab
* 3 - Search the request the following request => https://www.obat.fr/app/onboard/current-step
* 4 - Grab the following request headers ( X-Obat-Session-Id, Cookie, X-Requested-With ) and fill the .env file with

#### 4 - Launch the web server
```bash
php -S 127.0.0.1:8001 export.php
```

#### 5 - Open your browser
Open the URL http://127.0.0.1:8001 in your browser and wait until you see it is written "All exports are done"
