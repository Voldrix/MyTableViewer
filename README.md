# MyTableViewer
A single page, ultra minimalist, read-only database table viewer. Written in PHP for MySQL / MariaDB databases.

## Description
Other database tools like Adminer / PHPMyAdmin are great for when you need control over your databases. But if you just need to view the contents of your databases, their UI can be a bit bulky for that.\
This interface is designed to use your whole viewing window for the actual contents of your databases.

## Usage
The welcome screen will display only a list of databases you have added.  
To add a new database, hover your cursor at the bottom of the screen.  
- If hostname is left blank, it will default to localhost.
- The 'private' toggle switch will set a cookie that makes that database only viewable to you. Otherwise anyone who visits this page can see it. This is denoted by a lock icon next to it in the database list.
- Remove a database by clicking on it, then clicking the trash icon in the top right.
Click on a table name header to pull and display that table below.\
Click on a table's column header to sort the table client-side.\
Right-click (long press on mobile) on a column header to have the database sort the table server-side.\
Hover over the database name to see the user and hostname.

The database credential cache is stored in /tmp/MyTableViewer and is readable (0600) only to your webserver user (e.g. www-data).

![screen1](.github/screen1.png) ![screen2](.github/screen2.png) ![screen3](.github/screen3.png)

### Releases
There is no code to assemble, so the release is just [MyTableViewer.php](MyTableViewer.php)

## Contributing
This is my first open source project, and I am not primarily a developer, so you are welcome to contribute, but please be patient while I get the hang of this.\
The code is also as minimalistic as possible, so you should be able to easily make any changes that might be unique to your preference.

## License
[MIT License](LICENSE)
