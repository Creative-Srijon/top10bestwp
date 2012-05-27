import shutil
import os, sys

print("Compiling...")

# Get the current working directoy.
cwd = os.path.dirname(os.path.abspath(__file__))

# The destination combined file.
destfile_temp = os.path.join(cwd, "js", "tdr-framework-temp.js")
destination_temp = open( destfile_temp, 'wb' )
destfile = os.path.join(cwd, "js", "tdr-framework.js")

# The source JS files.
source_dir = os.path.join(cwd, "../tdr-framework-core/js/")

# Combine all of these core files.
core_files = (
  # Bootstrap
  'bootstrap-transition.js',
  'bootstrap-alert.js',
  'bootstrap-button.js',
  'bootstrap-carousel.js',
  'bootstrap-collapse.js',
  'bootstrap-dropdown.js',
  'bootstrap-modal.js',
  'bootstrap-tooltip.js',  # bootstrap-tooltip.js *MUST* be compiled before bootstrap-popover.js since popover extends tooltip.
  'bootstrap-popover.js',
  'bootstrap-scrollspy.js',
  'bootstrap-tab.js',
  'bootstrap-typeahead.js',
  'bootstrap-transition.js',
  # Utils
  'google-code-prettify/prettify.js',
  'html5.js',
  # Thunder Core
  'tdr-framework-core.js',
)

# Combine the files.
for js_file in core_files:
  # Expand the path.
  js_file = os.path.abspath(os.path.join(source_dir, js_file))

  # Minify the script if the argument was given.
  if (len(sys.argv) == 2) and (sys.argv[1] == '-m'):
    mini_js = os.path.abspath(os.path.join(cwd, js_file + '-min'))

    command = ' '.join([
      'java -jar',
      '"' + os.path.abspath(os.path.join(cwd, "./tools/yuicompressor.jar")) + '"',
      '"' + os.path.abspath(os.path.join(source_dir, js_file)) + '"',
      '--type js',
      '-o ' + '"' + mini_js + '"',
    ])

    print("Minifying: " + js_file)
    os.popen(command).read()

    shutil.copyfileobj(open(mini_js, 'rb'), destination_temp)
    destination_temp.write("\n")  # Make sure there's a newline after each file.

    # Cleanup temp file.
    os.remove(mini_js)
  else:
    shutil.copyfileobj(open(js_file, 'rb'), destination_temp)
    destination_temp.write("\n")

# Add the custom JS if it exists.
custom_child_js = os.path.join(cwd, "js/tdr-child-custom.js")
if os.path.exists(custom_child_js):
  shutil.copyfileobj( open( custom_child_js, 'rb' ), destination_temp )

# Close the destination file.
destination_temp.close()

# Open Temp and Destination.
destination = open( destfile, 'wb' )
destination_temp = open( destfile_temp )

# Replace $ with jQuery.
for line in destination_temp:
  if sys.version_info > (3,0):
    # The 2nd argument to bytes() is only supported on Python 3.0+
	  destination.write( bytes( line.replace('$', 'jQuery'), 'UTF-8' ) )
  else:
    destination.write( bytes( line.replace('$', 'jQuery') ))

# Close the files.
destination_temp.close()
destination.close()

# Remove the temporary file.
os.remove( destfile_temp )

print("Done.")
