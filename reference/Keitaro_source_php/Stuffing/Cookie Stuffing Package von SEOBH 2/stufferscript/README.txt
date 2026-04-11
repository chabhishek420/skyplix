----------------------------
NEW INSTALLATION
----------------------------
1. Create a MySQL database and import the sql.sql file.

2. Make sure you have a working SSL domain

3. Edit config.php in /kickback/

4. Upload the files in /kickback/ into a directory on your SSL domain.

5. Edit config.php in this folder.

6. Edit db.php in this folder.

7. Upload all files into a directory on your non-SSL domain.

8. Create a folder name /files/ in the same directory and make sure it is writeable (chmod 777).

9. Chmod admin.php to 755

10. Login to admin.php as username: default password: default

Note: You can install both parts of the script on the same domain.
Note: You can edit and add users in your PHPMyAdmin.

----------------------------
UPGRADE 1.1 to 1.2
----------------------------
1. Import the sql_1.1_to_1.2.sql file into the database using PHPMyAdmin.

2. Upload and overwrite the following files to the non-SSL installation:
	-admin.php
	-img.php
	-script.js

----------------------------
UPGRADE 1.0 to 1.2
----------------------------
1. Import the sql_1.0_to_1.1.sql file into the database using PHPMyAdmin.

2. Import the sql_1.1_to_1.2.sql file into the database using PHPMyAdmin.

2. Upload and overwrite the following files to the non-SSL installation:
	-admin.php
	-img.php
	-style.css
	-script.js

----------------------------
CHANGELOG
----------------------------
1.2	-Added the ability to enable/disable detailed logging in campaigns.
	-Decreased detailed log sizes.
	-Added statistics caching.
	-Added user-agent caching.
	-Added hits/blocks/stuffs stats to Affiliate Links page.
	-Added block stats to Campaigns page.

1.1	-Added Whois/Map IP Address lookup in the traffic logs.
	-Added support for blocking IP Address ranges.
	-Added pagination for traffic logs.
	-Added display User-Agent on individual IP Address log page.
	-Added affiliate link stuff count on campaigns page.
	-Fixed stuffing multiple times per IP Address if setup on separate domains.