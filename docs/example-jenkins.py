# coding: utf-8
import sys
from codecs import open
from urllib2 import urlopen
from simplejson import loads as load_json

url = urlopen("http://www.example.com/wp-admin/admin-ajax.php?action=externalUpdateCheck&secret=ABCDEFABCDEFABCDEFABCDEFABCDEFAB")
res = url.read()
if res == "0":
    sys.exit(0)

updates_input = load_json(res)
updates_output = open("updates.htm", "w", "utf-8")
updates_output.write("<h1>Available updates:</h1>\n")
for area in sorted(updates_input.keys()):
    updates_output.write("<h2>%s</h2>\n" % (area.capitalize(), ))
    if area == "core":
        for update in updates_input[area]:
            updates_output.write("<p>New version: <strong>%s</strong></p>\n" % (update["current"], ))
            updates_output.write('<p><a href="%s">Download</a></p>\n' % (update["download"], ))
    else:
        for update in updates_input[area].values():
            if update.has_key("Name"):
                updates_output.write("<h3>%s</h3>\n" % (update["Name"], ))
            if update.has_key("Version"):
                updates_output.write("<p>Current version: <strong>%s</strong></p>\n" % (update["Version"], ))
            if update.has_key("update") and update["update"].has_key("new_version"):
                updates_output.write("<p>New version: <strong>%s</strong></p>\n" % (update["update"]["new_version"], ))
            if update.has_key("update") and update["update"].has_key("package"):
                updates_output.write('<p><a href="%s">Download</a></p>\n' % (update["update"]["package"], ))
updates_output.flush()
updates_output.close()
sys.exit(1)