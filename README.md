# phpBB Merge Script

## Synopsis

A script that merges the tables from two different phpBB installations. The result will be a third table. The original two tables will not be modified.

## Installation

1. Download the ZIP file.
2. Unzip and copy to a folder in your local webserver.
3. Point your web browser to that folder.
4. Enter the information for your two phpBB installs and click the Merge button
5. Do lots of testing and fix any issues you encounter before using the merged database in a live setting

## Notes

- Only merge two phpBB boards that are the exact same version! If they are not the same version, upgrade one or both so that they are the same version.
- There are two PHP keymap files included, keymap-3.2.7.php and keymap-3.0.php. To change which one is used you will have to edit the index.php file.
- The keymap-3.2.7.php file is used for phpBB 3.2.7 boards. The keymap-3.0.php file is used for phpBB 3.0 boards (I have not tested this).
- Database information can be set in the config.php file if you don’t want to type it into the form each time, but form entries will override the config file entries.
- I don’t recommend using this on a live site. Use it locally and then import the finalized database tables to your live site (backing up the originals, of course)

## License

[GPLv2](license.txt)
