//---------------------------------------------------------------------------------------------------------------------------------
// File: pdo_parser.cpp
//
// Contents: Implements a parser to parse the PDO DSN.
// 
// Copyright Microsoft Corporation
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
//
// You may obtain a copy of the License at:
// http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//---------------------------------------------------------------------------------------------------------------------------------

#include "pdo_sqlsrv.h"

// Constructor
conn_string_parser:: conn_string_parser( sqlsrv_context& ctx, const char* dsn, int len, __inout HashTable* conn_options_ht )
{
    this->conn_str = dsn;
    this->len = len;
    this->conn_options_ht = conn_options_ht;
    this->pos = -1;
    this->ctx = &ctx;
}

// Move to the next character
inline bool conn_string_parser::next( void )
{
    // if already at the end than return false
    if( this->is_eos() ) {

        return false;
    }
        
    SQLSRV_ASSERT( this->pos < len, "Unexpected cursor position in conn_string_parser::next" );

    this->pos++;    

    if ( this->is_eos() ) {
    
        return false;
    }
    
    return true;
}

// Check for end of string.
inline bool conn_string_parser::is_eos( void )
{
    if( this->pos == len )
    {
        return true; // EOS
    }

    SQLSRV_ASSERT(this->pos < len, "Unexpected cursor position in conn_string_parser::is_eos" );
    
    return false;
}

// Check for white space. 
inline bool conn_string_parser::is_white_space( char c ) 
{
    if( c == ' ' || c == '\r' || c == '\n' || c == '\t' ) {
        return true;
    }
    return false;
}

// Discard any trailing white spaces.
int conn_string_parser::discard_trailing_white_spaces( const char* str, int len )
{
    const char* end = str + ( len - 1 );
    
    while(( this->is_white_space( *end ) ) && (len > 0) ) {
    
        len--;
        end--;
    }

    return len;
}

// Discard white spaces.
bool conn_string_parser::discard_white_spaces()
{
    if( this->is_eos() ) {
    
        return false;
    }

    while( this->is_white_space( this->conn_str[ pos ] )) {
    
        if( !next() )
            return false;
    } 
       
    return true;
}

// Add a key-value pair to the hashtable of connection options.
void conn_string_parser::add_key_value_pair( const char* value, int len TSRMLS_DC )
{
    zval_auto_ptr value_z;
    ALLOC_INIT_ZVAL( value_z );

    if( len == 0 ) {
    
        ZVAL_STRINGL( value_z, "", 0, 1 /*dup*/ );
    }
    else {

        ZVAL_STRINGL( value_z, const_cast<char*>( value ), len, 1 /*dup*/ );
    }                

    core::sqlsrv_zend_hash_index_update( *ctx, this->conn_options_ht, this->current_key, (void**)&value_z, 
                                         sizeof(zval*) TSRMLS_CC );

    zval_add_ref( &value_z );   
}

// Validate a given DSN keyword.
void conn_string_parser::validate_key(const char *key, int key_len TSRMLS_DC )
{
    int new_len = discard_trailing_white_spaces( key, key_len );

    for( int i=0; PDO_CONN_OPTS[ i ].conn_option_key != SQLSRV_CONN_OPTION_INVALID; ++i )
    {
        // discard the null terminator.
        if( new_len == ( PDO_CONN_OPTS[ i ].sqlsrv_len - 1 ) && !_strnicmp( key, PDO_CONN_OPTS[ i ].sqlsrv_name, new_len )) {

            this->current_key = PDO_CONN_OPTS[ i ].conn_option_key;
            this->current_key_name = PDO_CONN_OPTS[ i ].sqlsrv_name;
            return;
        }
    }

    // encountered an invalid key, throw error.
    sqlsrv_malloc_auto_ptr<char> key_name;
    key_name = static_cast<char*>( sqlsrv_malloc( new_len + 1 ));
    memcpy( key_name, key, new_len );
    key_name[ new_len ] = '\0';  

    THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_INVALID_DSN_KEY, key_name ); 
}

// Primary function which parses the connection string/DSN.
void conn_string_parser:: parse_conn_string( TSRMLS_D ) 
{
    States state = FirstKeyValuePair; // starting state
    int start_pos = -1;

    try {

        while( !this->is_eos() ) {
        
            switch( state ) {
            
                case FirstKeyValuePair:
                {
                    // discard leading spaces
                    if( !next() || !discard_white_spaces() ) {
                        
                        THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_INVALID_DSN_STRING ); //EOS
                    }
                  
                    state = Key;
                    break;
                }

                case Key:
                {
                    start_pos = this->pos;

                    // read the key name
                    while( this->conn_str[ pos ] != '=' ) {
                    
                        if( !next() ) {
                            
                            THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_DSN_STRING_ENDED_UNEXPECTEDLY ); //EOS 
                        }      
                    } 

                    this->validate_key( &( this->conn_str[ start_pos ] ), ( pos - start_pos ) TSRMLS_CC ); 
                
                    state = Value;

                    break;
                }

                case Value:
                {
                    SQLSRV_ASSERT(( this->conn_str[ pos ] == '=' ), "conn_string_parser:: parse_conn_string: "
                                   "Equal was expected" );

                    next(); // skip "="

                    // if EOS encountered after 0 or more spaces OR semi-colon encountered.
                    if( !discard_white_spaces() || this->conn_str[ pos ] == ';' ) {

                        add_key_value_pair( NULL, 0 TSRMLS_CC );

                        if( this->is_eos() ) {
                            
                            break; // EOS
                        }
                        else {

                            // this->conn_str[ pos ] == ';' 
                            state = NextKeyValuePair;
                        }
                    }
                    
                    // if LCB
                    else if( this->conn_str[ pos ] == '{' ) {
                        
                        start_pos = this->pos; // starting character is LCB
                        state = ValueContent1;
                    }
                    
                    // If NonSP-LCB-SC
                    else  {

                        start_pos = this->pos;
                        state = ValueContent2;
                    }

                    break;
                }

                case ValueContent1:
                {
                    while ( this->conn_str[ pos ] != '}' ) {
                    
                        if ( ! next() ) {

                            THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_RCB_MISSING_IN_DSN_VALUE, this->current_key_name ); 
                        }
                    }

                    // If we reached here than RCB encountered
                    state = RCBEncountered;

                    break;
                }

                case ValueContent2:
                {
                    while( this->conn_str[ pos ] != ';' ) {

                        if( ! next() ) {
                            
                            break; //EOS
                        }
                    }

                    if( !this->is_eos() && this->conn_str[ pos ] == ';' ) {
                    
                        // semi-colon encountered, so go to next key-value pair
                        state = NextKeyValuePair;
                    }
                    
                    add_key_value_pair( &( this->conn_str[ start_pos ] ), this->pos - start_pos TSRMLS_CC );
              
                    SQLSRV_ASSERT((( state == NextKeyValuePair ) || ( this->is_eos() )), 
                                  "conn_string_parser::parse_conn_string: Invalid state encountered " );

                    break;
                }

                case RCBEncountered:
                {
                    
                    // Read the next character after RCB.
                    if( !next() ) {

                        // EOS
                        add_key_value_pair( &( this->conn_str[ start_pos ] ), this->pos - start_pos TSRMLS_CC );
                        break;
                    }

                    SQLSRV_ASSERT( !this->is_eos(), "conn_string_parser::parse_conn_string: Unexpected EOS encountered" );

                    // if second RCB encountered than go back to ValueContent1
                    if( this->conn_str[ pos ] == '}' ) {
                        
                        if( !next() ) {

                            // EOS after a second RCB is error
                            THROW_PDO_ERROR( this->ctx, SQLSRV_ERROR_UNESCAPED_RIGHT_BRACE_IN_DSN, this->current_key_name );                              
                        }

                        state = ValueContent1;
                        break;
                    }

                    int end_pos = this->pos;

                    // discard any trailing white-spaces.
                    if( this->is_white_space( this->conn_str[ pos ] )) {
                    
                        if( ! this->discard_white_spaces() ) {
                            
                            //EOS
                            add_key_value_pair( &( this->conn_str[ start_pos ] ), end_pos - start_pos TSRMLS_CC );
                            break;
                        }
                    }

                    // if semi-colon than go to next key-value pair
                    if ( this->conn_str[ pos ] == ';' ) {
                        
                        add_key_value_pair( &( this->conn_str[ start_pos ] ), end_pos - start_pos TSRMLS_CC );
                        state = NextKeyValuePair;
                        break;
                    }

                    // Non - (RCB, SP*, SC, EOS) character. Any other character after an RCB is an error.
                    THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_INVALID_DSN_VALUE, this->current_key_name );      
                    break;    
                }
                case NextKeyValuePair:
                {
                    SQLSRV_ASSERT(( this->conn_str[ pos ] == ';' ), 
                                  "conn_string_parser::parse_conn_string: semi-colon was expected." );

                    // Call next() to skip the semi-colon.
                    if( !next() || !this->discard_white_spaces() ) {
                    
                        // EOS
                        break;
                    }
                    
                    if( this->conn_str[ pos ] == ';' ) {
                    
                        // a second semi-colon is error case.
                        THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_EXTRA_SEMI_COLON_IN_DSN_STRING, this->pos );      
                    }

                    else {

                        // any other character leads to the next key
                        state = Key;
                        break;
                    }
                } //case NextKeyValuePair
            } // switch
        } //while
    } 
    catch( pdo::PDOException& ) {

        throw;
    }
}

