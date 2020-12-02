//---------------------------------------------------------------------------------------------------------------------------------
// File: pdo_parser.cpp
//
// Contents: Implements a parser to parse the PDO DSN.
// 
// Copyright Microsoft Corporation
//
// Microsoft Drivers 5.9 for PHP for SQL Server
// Copyright(c) Microsoft Corporation
// All rights reserved.
// MIT License
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files(the ""Software""), 
//  to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, 
//  and / or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions :
// The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
// THE SOFTWARE IS PROVIDED *AS IS*, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, 
//  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
//  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS 
//  IN THE SOFTWARE.
//---------------------------------------------------------------------------------------------------------------------------------

extern "C" {
  #include "php_pdo_sqlsrv.h"
}

#include "php_pdo_sqlsrv_int.h"

// Constructor
conn_string_parser:: conn_string_parser( _In_ sqlsrv_context& ctx, _In_ const char* dsn, _In_ int len, _In_ HashTable* conn_options_ht )
{
    this->orig_str = dsn;
    this->len = len;
    this->element_ht = conn_options_ht;
    this->pos = -1;
    this->ctx = &ctx;
    this->current_key = 0;
    this->current_key_name = NULL;    
}

sql_string_parser:: sql_string_parser( _In_ sqlsrv_context& ctx, _In_ const char* sql_str, _In_ int len, _In_ HashTable* placeholders_ht )
{
    this->orig_str = sql_str;
    this->len = len;
    this->element_ht = placeholders_ht;
    this->pos = -1;
    this->ctx = &ctx;
    this->current_key = 0;
}

// Move to the next character
inline bool string_parser::next( void )
{
    // if already at the end then return false
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
inline bool string_parser::is_eos( void )
{
    if( this->pos == len )
    {
        return true; // EOS
    }

    SQLSRV_ASSERT(this->pos < len, "Unexpected cursor position in conn_string_parser::is_eos" );
    
    return false;
}

// Check for white space. 
inline bool string_parser::is_white_space( _In_ char c ) 
{
    if( c == ' ' || c == '\r' || c == '\n' || c == '\t' ) {
        return true;
    }
    return false;
}

// Discard any trailing white spaces.
int conn_string_parser::discard_trailing_white_spaces( _In_reads_(len) const char* str, _Inout_ int len )
{
    const char* end = str + ( len - 1 );
    
    while(( this->is_white_space( *end ) ) && (len > 0) ) {
    
        len--;
        end--;
    }

    return len;
}

// Discard white spaces.
bool string_parser::discard_white_spaces()
{
    if( this->is_eos() ) {
    
        return false;
    }

    while( this->is_white_space( this->orig_str[pos] )) {
    
        if( !next() )
            return false;
    } 
       
    return true;
}

// Add a key-value pair to the hashtable
void string_parser::add_key_value_pair( _In_reads_(len) const char* value, _In_ int len )
{
    zval value_z;
    ZVAL_UNDEF( &value_z );

    if( len == 0 ) {
    
        ZVAL_STRINGL( &value_z, "", 0);
    }
    else {

        ZVAL_STRINGL( &value_z, const_cast<char*>( value ), len );
    }                

    core::sqlsrv_zend_hash_index_update( *ctx, this->element_ht, this->current_key, &value_z ); 
}

// Add a key-value pair to the hashtable with int value
void sql_string_parser::add_key_int_value_pair( _In_ unsigned int value ) {
    zval value_z;
    ZVAL_LONG( &value_z, value );
    
    core::sqlsrv_zend_hash_index_update( *ctx, this->element_ht, this->current_key, &value_z );
}

// Validate a given DSN keyword.
void conn_string_parser::validate_key( _In_reads_(key_len) const char *key, _Inout_ int key_len )
{
    int new_len = discard_trailing_white_spaces( key, key_len );

    for( int i=0; PDO_CONN_OPTS[i].conn_option_key != SQLSRV_CONN_OPTION_INVALID; ++i )
    {
        // discard the null terminator.
        if( new_len == ( PDO_CONN_OPTS[i].sqlsrv_len - 1 ) && !strncasecmp( key, PDO_CONN_OPTS[i].sqlsrv_name, new_len )) {

            this->current_key = PDO_CONN_OPTS[i].conn_option_key;
            this->current_key_name = PDO_CONN_OPTS[i].sqlsrv_name;
            return;
        }
    }

    // encountered an invalid key, throw error.
    sqlsrv_malloc_auto_ptr<char> key_name;
    key_name = static_cast<char*>( sqlsrv_malloc( new_len + 1 ));
    memcpy_s( key_name, new_len + 1 ,key, new_len );

    key_name[new_len] = '\0';  

    THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_INVALID_DSN_KEY, static_cast<char*>( key_name ) ); 
}

void conn_string_parser::add_key_value_pair( _In_reads_(len) const char* value, _In_ int len )
{
    // if the keyword is 'Authentication', check whether the user specified option is supported
    bool valid = true;
    if ( stricmp( this->current_key_name, ODBCConnOptions::Authentication ) == 0 ) {
        if (len <= 0)
            valid = false;
        else {
            // extract option from the value by len
            sqlsrv_malloc_auto_ptr<char> option;
            option = static_cast<char*>( sqlsrv_malloc( len + 1 ) );
            memcpy_s( option, len + 1, value, len );
            option[len] = '\0';

            valid = AzureADOptions::isAuthValid(option, len);
        }
    }
    if( !valid ) {
        THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_INVALID_AUTHENTICATION_OPTION, this->current_key_name );
    }

    string_parser::add_key_value_pair( value, len );
}


inline bool sql_string_parser::is_placeholder_char( char c )
{
    // placeholder only accepts numbers, upper and lower case alphabets and underscore
    if (( c >= '0' && c <= '9' ) || ( c >= 'A' && c <= 'Z' ) || ( c >= 'a' && c <= 'z' ) || c == '_' ) {
        return true;
    }
    return false;
}

// Primary function which parses the connection string/DSN.
void conn_string_parser:: parse_conn_string( void ) 
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
                    while( this->orig_str[pos] != '=' ) {
                    
                        if( !next() ) {
                            
                            THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_DSN_STRING_ENDED_UNEXPECTEDLY ); //EOS 
                        }      
                    } 

                    this->validate_key( &( this->orig_str[start_pos] ), ( pos - start_pos ) ); 
                
                    state = Value;

                    break;
                }

                case Value:
                {
                    SQLSRV_ASSERT(( this->orig_str[pos] == '=' ), "conn_string_parser:: parse_conn_string: "
                                   "Equal was expected" );

                    next(); // skip "="

                    // if EOS encountered after 0 or more spaces OR semi-colon encountered.
                    if( !discard_white_spaces() || this->orig_str[pos] == ';' ) {

                        add_key_value_pair( NULL, 0 );

                        if( this->is_eos() ) {
                            
                            break; // EOS
                        }
                        else {

                            // this->orig_str[pos] == ';' 
                            state = NextKeyValuePair;
                        }
                    }
                    
                    // if LCB
                    else if( this->orig_str[pos] == '{' ) {
                        
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
                    while ( this->orig_str[pos] != '}' ) {
                    
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
                    while( this->orig_str[pos] != ';' ) {

                        if( ! next() ) {
                            
                            break; //EOS
                        }
                    }

                    if( !this->is_eos() && this->orig_str[pos] == ';' ) {
                    
                        // semi-colon encountered, so go to next key-value pair
                        state = NextKeyValuePair;
                    }
                    
                    add_key_value_pair( &( this->orig_str[start_pos] ), this->pos - start_pos );
              
                    SQLSRV_ASSERT((( state == NextKeyValuePair ) || ( this->is_eos() )), 
                                  "conn_string_parser::parse_conn_string: Invalid state encountered " );

                    break;
                }

                case RCBEncountered:
                {
                    
                    // Read the next character after RCB.
                    if( !next() ) {

                        // EOS
                        add_key_value_pair( &( this->orig_str[start_pos] ), this->pos - start_pos );
                        break;
                    }

                    SQLSRV_ASSERT( !this->is_eos(), "conn_string_parser::parse_conn_string: Unexpected EOS encountered" );

                    // if second RCB encountered than go back to ValueContent1
                    if( this->orig_str[pos] == '}' ) {
                        
                        if( !next() ) {

                            // EOS after a second RCB is error
                            THROW_PDO_ERROR( this->ctx, SQLSRV_ERROR_UNESCAPED_RIGHT_BRACE_IN_DSN, this->current_key_name );                              
                        }

                        state = ValueContent1;
                        break;
                    }

                    int end_pos = this->pos;

                    // discard any trailing white-spaces.
                    if( this->is_white_space( this->orig_str[pos] )) {
                    
                        if( ! this->discard_white_spaces() ) {
                            
                            //EOS
                            add_key_value_pair( &( this->orig_str[start_pos] ), end_pos - start_pos );
                            break;
                        }
                    }

                    // if semi-colon than go to next key-value pair
                    if ( this->orig_str[pos] == ';' ) {
                        
                        add_key_value_pair( &( this->orig_str[start_pos] ), end_pos - start_pos );
                        state = NextKeyValuePair;
                        break;
                    }

                    // Non - (RCB, SP*, SC, EOS) character. Any other character after an RCB is an error.
                    THROW_PDO_ERROR( this->ctx, PDO_SQLSRV_ERROR_INVALID_DSN_VALUE, this->current_key_name );      
                    break;    
                }
                case NextKeyValuePair:
                {
                    SQLSRV_ASSERT(( this->orig_str[pos] == ';' ), 
                                  "conn_string_parser::parse_conn_string: semi-colon was expected." );

                    // Call next() to skip the semi-colon.
                    if( !next() || !this->discard_white_spaces() ) {
                    
                        // EOS
                        break;
                    }
                    
                    if( this->orig_str[pos] == ';' ) {
                    
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

// Primary function which parses out the named placeholders from a sql string.
void sql_string_parser::parse_sql_string( void ) {
    try {
        int start_pos = -1;
        while ( !this->is_eos() ) {
            // if pos is -1, then reading from a string is an initialized read
            if ( pos == -1 ) {
                next();
            }
            // skip until a '"', '\'', ':' or '?'
            char sym;
            while ( this->orig_str[pos] != '"' && this->orig_str[pos] != '\'' && this->orig_str[pos] != ':' && this->orig_str[pos] != '?' && !this->is_eos() ) {
                next();
            }
            sym = this->orig_str[pos];
            // if '"' or '\'', skip until the next '"' or '\'' respectively
            if ( sym == '"' || sym == '\'' ) {
                next();
                while ( this->orig_str[pos] != sym && !this->is_eos() ) {
                    next();
                }
            }
            // if ':', store string placeholder in the placeholders hashtable
            else if ( sym == ':' ) {
                start_pos = this->pos;
                next();
                // keep going until the next space or line break
                // while (!is_white_space(this->orig_str[pos]) && !this->is_eos()) {
                while ( is_placeholder_char( this->orig_str[pos] )) {
                    next();
                }
                add_key_value_pair( &( this->orig_str[start_pos] ), this->pos - start_pos );
                discard_white_spaces();
                // if an '=' is right after a placeholder, it means the placeholder is for output parameters
                //  and emulate prepare does not support output parameters
                if (this->orig_str[pos] == '=') {
                    THROW_PDO_ERROR(this->ctx, PDO_SQLSRV_ERROR_EMULATE_INOUT_UNSUPPORTED);
                }
                this->current_key++;
            }
            // if '?', store long placeholder into the placeholders hashtable
            else if ( sym == '?' ) {
                next();
                // add dummy value to placeholders ht to keep count of the number of placeholders
                add_key_int_value_pair( this->current_key );
                discard_white_spaces();
                if (this->orig_str[pos] == '=') {
                    THROW_PDO_ERROR(this->ctx, PDO_SQLSRV_ERROR_EMULATE_INOUT_UNSUPPORTED);
                }
                this->current_key++;
            }
        }
    }
    catch ( pdo::PDOException& ) {
        throw;
    }
}