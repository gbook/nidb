
     Upgrading to NiDB 2019a
	 
	 
  1) Copy the bin directory to /nidb/programs/
  2) Original nidb.cfg remains in /nidb/programs
  3) Copy all web/* files to /var/www/html/
  4) Replace cron with crontab.txt
  5) Drop the modules table in MySQL and replace it with the table and data in modules.sql
  6) Once website is working, go to Admin --> Settings...
       a) change the Modules variables to reflect the recommended values listed for each one
	   b) remove tls:// or ssl:// from the smtp.gmail.com variable if it is being used
	   c) Save the config file. New variables will be written to the config file when saved.