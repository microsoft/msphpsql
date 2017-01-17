#!/usr/bin/env python3
#########################################################################################
#
# Description:
#       Requirement of python 3.4 to execute this script and required result log file are in the same location
#       Run with command line without options required. Example: py output.py
#       This script parse output of PHP Native Test
#
#
#############################################################################################

import os
import stat
import re

# This module returns either the number of test or the number of failed test
# depending on the argument you requested.
# Input:    var - a single variable containing either "FAIL" or "TOTAL"
# Output:   Returns a number of test/s or failed test/s
def returnCount(var):
    with open(os.path.dirname(os.path.realpath(__file__)) + os.sep + logfile) as f:
        num = 0
        failnum = 0
        for line in f:
            if "FAIL" in line or "PASS" in line:
                if ".phpt" in line:
                    if "FAIL" in line:
                        failnum += 1
                    num += 1
    if var == 'total':
        return str(num)
    else:
        return str(failnum)

# This module prints the line that matches the expression.
# Input:    inputStr - String that matches
#           file - file name
#           path - path of the file.
# Output:   null
def readAndPrint(inputStr, file, path):
    filn = open(path + os.sep + file).readlines()
    for lines in filn:
        if inputStr in lines:
            print(lines)

# This module returns the test file name.
# Input:    line - current line of the log file
# Output:   Returns the filename.
def TestFilename(line):
    terminateChar = os.sep
    currentPos = 0
    while True:
        currentPos = currentPos - 1
        line[currentPos]
        if line[currentPos] == terminateChar:
            break
    file = line[currentPos+1:-1]
    return file

def genXML(logfile,number):
    # Generating the nativeresult.xml file.
    file = open('nativeresult' + str(number) + '.xml','w')
    file.write('<?xml version="1.0" encoding="UTF-8" ?>' + os.linesep)
    file.write('<testsuite tests="' + returnCount('total') + '" failures="' + returnCount('fail') + '" name="Native Tests" >' + os.linesep)
    file.close()

    # Extract individual test results from the log file and
    # enter it in the nativeresult.xml file.

    with open(os.path.dirname(os.path.realpath(__file__)) + os.sep + logfile) as f:
        num = 1
        failnum = 0
        for line in f:
            file = open('nativeresult' + str(number) + '.xml','a')
            if "FAIL" in line or "PASS" in line:
                if ".phpt" in line:

                    file.write('\t<testcase name="')
                    if "FAIL" in line:
                        failnum += 1
                        result = re.search('FAIL(.*).', line)
                        file.write(TestFilename(str(result.group(1))) + '-' + str(num) + '">' + os.linesep)
                        stop_pos = result.group(1).find('[')
                        file.write('\t\t<failure message=" Failed in ' + str(result.group(1))[0:stop_pos] + '"/>' + os.linesep)
                        file.write('\t</testcase>' + os.linesep)
                    else:
                        result = re.search('PASS(.*).', line)
                        file.write(TestFilename(str(result.group(1))) + '-' + str(num) + '"/>' + os.linesep)
                    num += 1
            file.close()

        file = open('nativeresult' + str(number) + '.xml','a')
        file.write('</testsuite>' + os.linesep)
        file.close()

def run():
    num = 1
    for f in os.listdir(os.path.dirname(os.path.realpath(__file__))):
        if f.endswith("log"):
            print('================================================')
            print(os.path.splitext(f)[0])
            readAndPrint('Number of tests :', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests skipped ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests warned ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests failed ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Expected fail ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests passed ', f, os.path.dirname(os.path.realpath(__file__)))
            print('================================================')
            logfile = f
            genXML(logfile,num)
            num = num + 1


# ------------------------------------------------------- Main Function ---------------------------------------------------

# Display results on screen from result log file.
if __name__ == '__main__':
    num = 1
    for f in os.listdir(os.path.dirname(os.path.realpath(__file__))):
        if f.endswith("log"):
            print('================================================')
            print("\n" + os.path.splitext(f)[0] + "\n")
            readAndPrint('Number of tests :', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests skipped ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests warned ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests failed ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Expected fail ', f, os.path.dirname(os.path.realpath(__file__)))
            readAndPrint('Tests passed ', f, os.path.dirname(os.path.realpath(__file__)))
            print('================================================')
            logfile = f
            genXML(logfile,num)
            num = num + 1

