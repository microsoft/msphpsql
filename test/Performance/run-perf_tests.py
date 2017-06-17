import shutil
from shutil import copyfile
import os
import sys
import argparse
import subprocess
import fileinput
import subprocess
from subprocess import call
import xml.etree.ElementTree as ET
import pyodbc
import platform
import re
import datetime
from time import strftime
 
sqlsrv_regular_path = "benchmark"+ os.sep + "sqlsrv" + os.sep + "regular"
sqlsrv_large_path = "benchmark"+ os.sep + "sqlsrv" + os.sep + "large"
pdo_regular_path = "benchmark"+ os.sep + "pdo_sqlsrv" + os.sep + "regular"
pdo_large_path = "benchmark"+ os.sep + "pdo_sqlsrv" + os.sep + "large"
connect_file = "lib" + os.sep + "connect.php"
connect_file_bak = connect_file + ".bak"

def validate_platform( platform_name ):
    platforms = [
          "Windows10"
        , "WidnowsServer2016"
        , "WindowsServer2012"
        , "Ubuntu16"
        , "RedHat7"
        , "Sierra"]
    if platform_name not in platforms:
        print ( "Platform must be one of the following:" )
        print( platforms )
        exit( 1 )

class DB( object ):
    def __init__ ( self
        , server_name = None
        , database_name = None
        , username = None
        , password = None):
            self.server_name = server_name
            self.database_name = database_name
            self.username = username
            self.password = password
 
class XMLResult( object ):
    def __init__ ( self
        , benchmark_name = None
        , success = None
        , duration = None
        , memory = None
        , error_message = None ):
            self.benchmark_name = benchmark_name
            self.success = success
            self.duration = duration
            self.memory = memory
            self.error_message = error_message

def get_test_name( name ):
    test_name_dict = {
          'SqlsrvConnectionBench': 'connection'
        , 'SqlsrvCreateDbTableProcBench': 'create'
        , 'SqlsrvInsertBench': 'crud-create'    
        , 'SqlsrvFetchBench': 'crud-retrieve'  
        , 'SqlsrvUpdateBench': 'crud-update'    
        , 'SqlsrvDeleteBench': 'crud-delete'
        , 'SqlsrvFetchLargeBench': 'large'
        , 'SqlsrvSelectVersionBench': 'version'
        , 'PDOConnectionBench': 'connection'
        , 'PDOCreateDbTableProcBench': 'create'
        , 'PDOInsertBench': 'crud-create'    
        , 'PDOFetchBench': 'crud-retrieve'  
        , 'PDOUpdateBench': 'crud-update'    
        , 'PDODeleteBench': 'crud-delete'
        , 'PDOFetchLargeBench': 'large'
        , 'PDOSelectVersionBench': 'version'
    }
    return test_name_dict[ name ]
 
def get_run_command( path_to_tests, iterations, dump_file ):
    command = "vendor/bin/phpbench run {0} --iterations {1} --dump-file={2}"
    return command.format( path_to_tests, iterations, dump_file )

def get_id( conn, id_field, table, name_field, name):
    query = "SELECT {0} FROM {1} WHERE {2}='{3}'"
    cursor = conn.cursor()
    cursor.execute( query.format( id_field, table, name_field, name ))
    id = cursor.fetchone()
    cursor.close()
    if id is not None:
        return id[0]
    return id

def get_test_database():
    test_db = DB()
    for line in open( connect_file ):
        if "server" in line:
            test_db.server_name = line.split("=")[1].strip()[1:-2]
        elif "database" in line:
            test_db.database_name = line.split("=")[1].strip()[1:-2]        
        elif "uid" in line:
            test_db.username = line.split("=")[1].strip()[1:-2]
        elif "pwd" in line:
            test_db.password = line.split("=")[1].strip()[1:-2]
    return test_db

def connect( db ):
    return pyodbc.connect(
          driver="{ODBC Driver 13 for SQL Server}"
        , host=db.server_name
        , database=db.database_name
        , user=db.username
        , password=db.password
        , autocommit = True)

def get_server_version( server):
    conn = connect( server )
    cursor = conn.cursor()
    cursor.execute( "SELECT @@VERSION")
    version = cursor.fetchone()[0]
    cursor.close()
    return version
       
def insert_server_entry( conn, server_name, server_version ):
    query = "INSERT INTO Servers ( HostName, Version ) VALUES ( '{0}', '{1}' )"
    cursor = conn.cursor()
    cursor.execute( query.format( server_name, server_version ))
    cursor.close()

def insert_client_entry ( conn, name ):
    query = "INSERT INTO Clients ( HostName ) VALUES( '{0}' )"
    cursor = conn.cursor()
    cursor.execute( query.format( name ))
    cursor.close()

def insert_team_entry ( conn, name ):
    query = "INSERT INTO Teams ( TeamName ) VALUES( '{0}' )"
    cursor = conn.cursor()
    cursor.execute( query.format( name ))
    cursor.close()

def insert_test_entry( conn, name ):
    #TO-DO Remove unnecessary columns from the table and fix the query string. Amd64 and 0 are used to bypass not null
    query = "INSERT INTO PerformanceTests ( TestName, Arch, HashVer ) VALUES( '{0}', 'Amd64', 0 )"
    cursor = conn.cursor()
    cursor.execute( query.format( name ))
    cursor.close()

def get_server_id( conn, test_db ):
    server_id = get_id( conn, "ServerId", "Servers", "HostName", test_db.server_name )
    if server_id is None:
        insert_server_entry( conn, test_db.server_name, get_server_version( test_db ))
        server_id = get_id( conn, "ServerId", "Servers", "HostName", test_db.server_name )
    return server_id

def get_client_id( conn ):
    client_name = platform.node()
    client_id = get_id( conn, "ClientId", "Clients", "HostName", client_name )
    if client_id is None:
        insert_client_entry( conn, client_name )
        client_id = get_id( conn, "ClientId", "Clients", "HostName", client_name )
    return client_id

def get_team_id( conn ):
    team_name = "PHP"
    team_id = get_id( conn, "TeamId", "Teams", "TeamName", team_name)
    if team_id is None:
        insert_team_entry( conn, team_name )
        team_id = get_id( conn, "TeamId", "Teams", "TeamName", team_name)
    return team_id

def get_test_id( conn, test_name ):
    test_id = get_id( conn, "TestId", "PerformanceTests", "TestName", test_name )
    if test_id is None:
        insert_test_entry( conn, test_name )
        test_id = get_id( conn, "TestId", "PerformanceTests", "TestName", test_name )
    return test_id
   
def insert_result_entry_and_get_id( conn, test_id, client_id, driver_id, server_id, team_id, success ):
    query = "INSERT INTO PerformanceResults( TestId, ClientId, DriverId, ServerId, TeamId, Success ) OUTPUT INSERTED.ResultId VALUES( {0}, {1}, {2}, {3}, {4}, {5} )"
    cursor = conn.cursor()
    cursor.execute( query.format( test_id, client_id, driver_id, server_id, team_id, success ))
    result_id = cursor.fetchone()
    cursor.close()
    if result_id is not None:
        return result_id[0]
    return id

def insert_key_value( conn, table_name, result_id, key, value ):
    query = "INSERT INTO {0} ( ResultId, name, value ) VALUES( ?, ?, ? )"
    cursor = conn.cursor()
    cursor.execute( query.format( table_name ), ( result_id, key, value ) )
    cursor.close()

def get_php_arch():
    p = subprocess.Popen( "php -r 'echo PHP_INT_SIZE;'", stdout=subprocess.PIPE, shell = True )
    out, err = p.communicate()
    if out.decode('ascii') == "8":
        return "x64"
    elif out.decode('ascii') == "4":
        return "x86"

def get_php_thread():
    if os.name == 'nt':
        command = "php -i | findstr 'Thread'"
    else:
        command = "php -i | grep 'Thread'"
    p = subprocess.Popen( command, stdout=subprocess.PIPE, shell = True )
    out, err = p.communicate()
    if out.decode('ascii').split()[3].strip() == 'disabled':
        return "nts"
    else:
        return "ts"    

def enable_mars():
    print( "Enabling MARS...")
    with fileinput.FileInput( connect_file, inplace=True, backup='.bak') as file:
        for line in file:
            print( line.replace( "$mars=false;", "$mars=true;" ), end='')      

def disable_mars():
    print( "Disabling MARS...")
    os.remove( connect_file )
    copyfile( connect_file_bak, connect_file )

def enable_pooling():
    print( "Enabling Pooling...")
    if os.name == 'nt':
        with fileinput.FileInput( connect_file, inplace=True, backup='.bak') as file:
            for line in file:
                print( line.replace( "$pooling=false;", "$pooling=true;" ), end='')
    else:
        # Get the location of odbcinst.ini
        odbcinst = os.popen( "odbcinst -j" ).read().splitlines()[1].split()[1]
        odbcinst_bak = odbcinst + ".bak"
 
        # Create a copy of odbcinst.ini
        copyfile( odbcinst, odbcinst_bak )
 
        # Lines to enable Connection pooling
        lines_to_append="CPTimeout=5\n[ODBC]\nPooling=Yes\n"
 
        with open( odbcinst, "a" ) as f:
            f.write( lines_to_append )

def disable_pooling():
    print("Disabling Pooling...")
    if os.name == 'nt':
        os.remove( connect_file )
        copyfile( connect_file_bak, connect_file )
    else:
        # Get the location of odbcinst.ini
        odbcinst = os.popen( "odbcinst -j" ).read().splitlines()[1].split()[1]
        odbcinst_bak = odbcinst + ".bak"
        os.remove( odbcinst )
        copyfile( odbcinst_bak, odbcinst )
        os.remove( odbcinst_bak )

def run_tests( iterations, iterations_large ):
    print("Running the tests...")
    call( get_run_command( sqlsrv_regular_path, iterations, "sqlsrv-regular.xml" ), shell=True, stdout=open( os.devnull, 'wb' ))
    call( get_run_command( sqlsrv_large_path, iterations_large, "sqlsrv-large.xml" ), shell=True, stdout=open( os.devnull, 'wb' ))
 
    call( get_run_command( pdo_regular_path, iterations, "pdo_sqlsrv-regular.xml" ), shell=True, stdout=open( os.devnull, 'wb' ))
    call( get_run_command( pdo_large_path, iterations_large, "pdo_sqlsrv-large.xml" ), shell=True, stdout=open( os.devnull, 'wb' ))

def parse_results( dump_file ):
    xml_results = []
    tree = ET.parse( dump_file )
    root = tree.getroot()
    for benchmark in root[0].findall( 'benchmark' ):
        xml_result = XMLResult()
        xml_result.benchmark_name = benchmark.get( 'class' )[1:]
        errors = benchmark[0][0].find( 'errors' )
        if( errors is not None ):
            xml_result.success = 0
            xml_result.error_message = errors[0].text
        else:
            xml_result.success = 1
            xml_result.duration = benchmark[0][0].find( 'stats' ).get( 'sum' )
            memory_peak = 0
            for iteration in benchmark[0][0].findall( 'iteration' ):
                iter_memory_peak = int( iteration.get( 'mem-peak' ))
                if iter_memory_peak > memory_peak:
                    memory_peak = iter_memory_peak
            xml_result.memory = memory_peak
        xml_results.append( xml_result )
    return xml_results

def parse_and_store_results( dump_file, test_db, result_db, platform, driver, start_time, mars, pooling ):
    conn = connect( result_db )

    server_id = get_server_id( conn, test_db )
    client_id = get_client_id( conn )
    team_id = get_team_id( conn )
    # TO - DO Add a function to insert a driver entry
    driver_id=1
   
    arch = get_php_arch()
    thread = get_php_thread()
    cursor = conn.cursor()
    results = parse_results( dump_file )

    for result in results:
        test_name = get_test_name( result.benchmark_name )
        test_id = get_test_id( conn, test_name )
        result_id = insert_result_entry_and_get_id( conn, test_id, client_id, driver_id, server_id, team_id, result.success )

        if result.success:
            insert_key_value( conn, "KeyValueTableBigInt", result_id, "duration", result.duration )
            insert_key_value( conn, "KeyValueTableBigInt", result_id, "memory",   result.memory )
        else:
            insert_key_value( conn, "KeyValueTableString", result_id, "error", result.error_message )

        insert_key_value( conn, "KeyValueTableDate"  , result_id, "startTime", start_time )
        insert_key_value( conn, "KeyValueTableBigInt", result_id, "mars"     , mars )
        insert_key_value( conn, "KeyValueTableBigInt", result_id, "pooling"  , pooling )
        insert_key_value( conn, "KeyValueTableString", result_id, "driver"   , driver )
        insert_key_value( conn, "KeyValueTableString", result_id, "arch"     , arch )
        insert_key_value( conn, "KeyValueTableString", result_id, "os"       , platform )
        insert_key_value( conn, "KeyValueTableString", result_id, "thread"   , thread )

def parse_and_store_results_all( test_db, result_db, platform, start_time, mars, pooling ):
    print("Parsing and storing the results...")
    parse_and_store_results( "sqlsrv-regular.xml", test_db, result_db, platform, "sqlsrv", start_time, mars, pooling )
    parse_and_store_results( "sqlsrv-large.xml", test_db, result_db, platform, "sqlsrv", start_time, mars, pooling )
    parse_and_store_results( "pdo_sqlsrv-regular.xml", test_db, result_db, platform, "pdo_sqlsrv", start_time, mars, pooling )
    parse_and_store_results( "pdo_sqlsrv-large.xml", test_db, result_db, platform, "pdo_sqlsrv", start_time, mars, pooling )

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument( '-platform', '--PLATFORM', required=True )
    parser.add_argument( '-iterations', '--ITERATIONS', type=int , required=True )
    parser.add_argument( '-iterations-large', '--ITERATIONS_LARGE',type=int , required=True )
    parser.add_argument( '-result-server', '--RESULT_SERVER', required=True )
    parser.add_argument( '-result-db', '--RESULT_DB', required=True )
    parser.add_argument( '-result-uid', '--RESULT_UID', required=True )
    parser.add_argument( '-result-pwd', '--RESULT_PWD', required=True )
    args = parser.parse_args()

    validate_platform( args.PLATFORM )
    result_db = DB( args.RESULT_SERVER, args.RESULT_DB, args.RESULT_UID, args.RESULT_PWD )
    test_db = get_test_database()
    fmt = "%Y-%m-%d %H:%M:%S.0000000"

    print("Running the tests with default settings...")
    start_time = datetime.datetime.now().strftime( fmt )
    run_tests( args.ITERATIONS, args.ITERATIONS_LARGE )
    parse_and_store_results_all( test_db, result_db, args.PLATFORM, start_time, 0, 0 )
   
    print("Running the tests with MARS ON...")
    enable_mars()
    start_time = datetime.datetime.now().strftime( fmt )
    run_tests( args.ITERATIONS, args.ITERATIONS_LARGE )
    parse_and_store_results_all( test_db, result_db, args.PLATFORM, start_time, 1, 0 )
    disable_mars()

   
    print("Running the tests with Pooling ON...")
    enable_pooling()
    start_time = datetime.datetime.now().strftime( fmt )
    run_tests( args.ITERATIONS, args.ITERATIONS_LARGE )
    parse_and_store_results_all( test_db, result_db, args.PLATFORM, start_time, 0, 1 )
    disable_pooling()
   
   