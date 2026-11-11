#!/usr/bin/env python3
#########################################################################################
#
# Description:
#       Requirement of python 3.4 to execute this script and required result log file(s) 
#       are in the same location
#       Run with command line without options required. Example: py output.py
#       This script parse output of PHP Test logs
#
#############################################################################################

import os
import stat
import re
import argparse
import xml.sax.saxutils

# This module appends an entry to the tests list, may include the test title and diff content.
# Input:    search_pattern - pattern to look for in the line of the log file
#           line - current line of the log file
#           index - the current index of tests
#           tests_list - a list of xml entries
#           get_title - boolean flag to get the test title or not
#           diff_content - the accumulated diff content from the log file
# Output:   None
def get_test_entry(search_pattern, line, index, tests_list, get_title = False, diff_content = ""):
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

    # A FAIL line may be stale if the test passed on retry.
    # Check whether the corresponding .diff file still exists.
    if get_title:
        diff_file = substr.replace('.phpt', '.diff')
        if os.path.exists(diff_file):
            # .diff exists at the exact path — genuine failure
            pass
        elif os.sep not in diff_file and '/' not in diff_file:
            # Path is just a filename (e.g. Linux runs cd into driver dir).
            # Check both sqlsrv/ and pdo_sqlsrv/ subdirectories.
            script_dir = os.path.dirname(os.path.realpath(__file__))
            srv_path = os.path.join(script_dir, 'sqlsrv', diff_file)
            pdo_path = os.path.join(script_dir, 'pdo_sqlsrv', diff_file)
            if not os.path.exists(srv_path) and not os.path.exists(pdo_path):
                # .diff removed by retry — treat as PASS
                entry = '\t<testcase name="' + test_name + '-' + index + '"/>'
                tests_list.append(entry)
                return 0
        elif not os.path.exists(diff_file):
            # .diff removed by retry — treat as PASS
            entry = '\t<testcase name="' + test_name + '-' + index + '"/>'
            tests_list.append(entry)
            return 0

    # only upon a failure do we get the test title
    if (get_title is True):
        entry = '\t<testcase name="' + test_name + '-' + index + '">'
        tests_list.append(entry)
        test_title = test_line[0:pos1]
        
        # Escape the test title and diff content for XML
        escaped_title = xml.sax.saxutils.escape(test_title)
        escaped_diff = xml.sax.saxutils.escape(diff_content)
        
        # Create failure entry with diff content in the body
        entry = '\t\t<failure message="Failed in ' + escaped_title + '">'
        tests_list.append(entry)
        if diff_content:
            tests_list.append(escaped_diff)
        tests_list.append('\t\t</failure>')
        tests_list.append('\t</testcase>')
        return 1
    else:
        entry = '\t<testcase name="' + test_name + '-' + index + '"/>'
        tests_list.append(entry)
        return 0

# Extract individual test results from the log file and
# Input:    logfile - the test log file
#           number - the number for this xml file (applicable if using the default report name)
#           logfilename - use the log file name for the xml output file Instead
def gen_XML(logfile, number, logfilename):
    print('================================================')
    filename = os.path.splitext(logfile)[0]
    print("\n" + filename + "\n" )

    tests_list = []
    script_dir = os.path.dirname(os.path.realpath(__file__))
    
    # Auto-detect encoding - check for UTF-16 LE BOM
    log_path = script_dir + os.sep + logfile
    encoding = 'utf-8'
    with open(log_path, 'rb') as fb:
        first_bytes = fb.read(2)
        if first_bytes == b'\xff\xfe':  # UTF-16 LE BOM
            encoding = 'utf-16-le'
        elif first_bytes.startswith(b'\xef\xbb'):  # UTF-8 BOM
            encoding = 'utf-8-sig'
    
    with open(log_path, encoding=encoding, errors='replace') as f:
        num = 1
        failnum = 0
        in_diff_section = False
        diff_lines = []
        
        for line in f:
            # Check if we're entering a diff section
            if "========DIFF========" in line:
                in_diff_section = True
                diff_lines = []
                continue
            
            # Check if we're exiting a diff section
            if "========DONE========" in line:
                in_diff_section = False
                continue
            
            # Accumulate diff lines
            if in_diff_section:
                diff_lines.append(line.rstrip('\n'))
                continue
            
            # Process FAIL/PASS lines
            if "FAIL" in line or "PASS" in line:
                if ".phpt" in line:
                    if "FAIL" in line:
                        diff_content = '\n'.join(diff_lines) if diff_lines else ""
                        failnum += get_test_entry('FAIL(.*).', line, str(num), tests_list, True, diff_content)
                        diff_lines = []  # Reset for next test
                    else:
                        get_test_entry('PASS(.*).', line, str(num), tests_list)
                    num += 1
            elif 'Number of tests :' in line or 'Tests skipped ' in line or 'Tests warned ' in line or'Tests failed ' in line or 'Expected fail ' in line or 'Tests passed ' in line:
                print(line)
        print('================================================')

    # Generating the xml report.
    if logfilename is True:
        file = open(filename + '.xml', 'w', encoding='utf-8')
        report = filename
    else:
        file = open('nativeresult' + str(number) + '.xml', 'w', encoding='utf-8')
        report = filename + ' Tests'
    
    file.write('<?xml version="1.0" encoding="UTF-8" ?>' + os.linesep)
    file.write('<testsuite tests="' + str(num - 1) + '" failures="' + str(failnum) + '" name="' + report + '" >' + os.linesep)

    index = 1
    for test in tests_list:
        file.write(test + os.linesep)
    file.write('</testsuite>' + os.linesep)
    file.close()

# ----------------------- Main Function -----------------------

# Generate XML reports from test result log files.
if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--LOGFILENAME', action='store_true', help="Generate XML files using log file names (default: False)")

    args = parser.parse_args()
    logfilename = args.LOGFILENAME
    
    num = 1
    for f in os.listdir(os.path.dirname(os.path.realpath(__file__))):
        if f.endswith("log"):
            logfile = f
            gen_XML(logfile, num, logfilename)
            num = num + 1

