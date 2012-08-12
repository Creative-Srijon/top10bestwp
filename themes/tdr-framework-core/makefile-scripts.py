import os
import shutil
import subprocess
import sys
import readline

if sys.platform == 'win32':
    print("Twitter Scraper auto setup not supported on WINDOWS.")
    sys.exit()
else:
    cwd = os.path.dirname(os.path.abspath(__file__)) # TDR Core directory
    scraper_php_file = os.path.abspath(os.path.join(cwd, "phirehose/twitter_scraper.php")) # Path to Phirehose twitter scraper php script
	# Questions for user
    admin_url_question = "What is the url for admin-ajax.php? eg: http://domain.tld/wp-admin/admin-ajax.php \n>"
    twitter_user_question = "What is the username of the twitter account you will be scraping under?\n>"
    twitter_password_question = "What is the password of the twitter account you will be scraping under?\n>"
    # raw_input renamed to input in python 3.x -- input evals code in older verisons
    if sys.version_info < (3, 0):
        admin_url = raw_input( admin_url_question ) # Ask the user for the AJAX url for the site
        twitter_username = raw_input( twitter_user_question ) # Ask the user for the twitter username
        twitter_password = raw_input( twitter_password_question ) # Ask for the twitter password
    else:
        admin_url = input( admin_url_question ) # Ask the user for the AJAX url for the site
        twitter_username = input( twitter_user_question ) # Ask the user for the twitter username
        twitter_password = input( twitter_password_question ) # Ask for the twitter password
    # Escape the password
    twitter_password = twitter_password.replace( '$', '\$' ) # $ char needs to be escaped if present -- add any others as necessary
    # Build script body
    bash_script_contents = '#! /bin/sh\n'
    bash_script_contents += 'nohup php5 ' + cwd + '/phirehose/twitter-scraper.php --url="' + admin_url + '" --username="' + twitter_username + '" --password="' + twitter_password + '" &\n'
    bash_script_contents += 'echo "running job on PID "$!'

	# Write bash script to /etc/init.d/tdr_twitter_scraper.sh
    twitter_scraper_rc_path = os.path.abspath(os.path.join(cwd, "tdr_twitter_scraper.sh"))
    bash_script_handle = open( twitter_scraper_rc_path, 'wb' )
    bash_script_handle.write( bash_script_contents )
    bash_script_handle.close()
    print "Wrote twitter scraper rc script to " + twitter_scraper_rc_path + "\n"
    print "Move script to /etc/init.d/tdr_twitter_scraper.sh\n"
    print "$ sudo mv " + twitter_scraper_rc_path + " /etc/init.d/tdr_twitter_scraper.sh\n"
    # Mark script as executable
    # sudo chmod +x twitter_scraper_rc_path
    print "Mark script as executable\n"
    print "$ sudo chmod +x /etc/init.d/tdr_twitter_scraper.sh\n"
    # Update rc.d to load the bash script
    # sudo update-rc.d tdr_twitter_scraper.sh defaults
    print "Update rc.d to load the bash script\n"
    print "$ sudo update-rc.d tdr_twitter_scraper.sh defaults\n"
