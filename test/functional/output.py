#!/usr/bin/env python3
#########################################################################################
#
# Description:
#       Requirement of python 3.4 to execute this script and required result log file(s) 
#       are in the same location
#       Run with command line without options required. Example: py output.py
#       This script parse output of PHP Native Test
#
#############################################################################################

import os
import stat
import re

# This module appends an entry to the tests list, may include the test title.
# Input:    search_pattern - pattern to look for in the line of the log file
#           line - current line of the log file
#           index - the current index of tests
#           tests_list - a list of xml entries
#           get_title - boolean flag to get the test title or not
# Output:   None
def get_test_entry(search_pattern, line, index, tests_list, get_title = False):
    # find the full path to the test name, enclosed by square brackets
    result = re.search(search_pattern, line)
    pos1 = result.group(1).find('[')
    pos2 = result.group(1).find(']')
    test_line = str(result.group(1))

    # get the test name by splitting this full path delimited by os.sep
    substr = test_line[pos1+1:pos2]
    tmp_array = substr.split(os.sep)
    pos = len(tmp_array) - 1
    test_name = tmp_array[pos]

    # only upon a failure do we get the test title
    if (get_title is True):
        entry = '\t<testcase name="' + test_name + '-' + index + '">'
        tests_list.append(entry)
        test_title = test_line[0:pos1]
        entry = '\t\t<failure message=" Failed in ' + test_title + '"/>'
        tests_list.append(entry)
        tests_list.append('\t</testcase>')
    else:
        entry = '\t<testcase name="' + test_name + '-' + index + '"/>'
        tests_list.append(entry)

# Extract individual test results from the log file and
# enter it in the nativeresult.xml file.
# Input:    logfile - the log file
#           number - the number for this xml file
def gen_XML(logfile, number):
    print('================================================')
    print("\n" + os.path.splitext(logfile)[0] + "\n" )

    tests_list = []
    with open(os.path.dirname(os.path.realpath(__file__)) + os.sep + logfile) as f:
        num = 1
        failnum = 0
        for line in f:
            if "FAIL" in line or "PASS" in line:
                if ".phpt" in line:
                    if "FAIL" in line:
                        failnum += 1
                        get_test_entry('FAIL(.*).', line, str(num), tests_list, True)
                    else:
                        get_test_entry('PASS(.*).', line, str(num), tests_list)
                    num += 1
            elif 'Number of tests :' in line or 'Tests skipped ' in line or 'Tests warned ' in line or'Tests failed ' in line or 'Expected fail ' in line or 'Tests passed ' in line:
                print(line)
        print('================================================')

    # Generating the nativeresult.xml file.
    file = open('nativeresult' + str(number) + '.xml', 'w')
    file.write('<?xml version="1.0" encoding="UTF-8" ?>' + os.linesep)
    file.write('<testsuite tests="' + str(num - 1) + '" failures="' + str(failnum) + '" name="Native Tests" >' + os.linesep)

    index = 1
    for test in tests_list:
        file.write(test + os.linesep)
    file.write('</testsuite>' + os.linesep)
    file.close()

# ----------------------- Main Function -----------------------

# Display results on screen from result log file.
if __name__ == '__main__':
    num = 1
    for f in os.listdir(os.path.dirname(os.path.realpath(__file__))):
        if f.endswith("log"):
            logfile = f
            gen_XML(logfile, num)
            num = num + 1

