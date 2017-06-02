set options=%2
set options=%options:"=%
C:\php-sdk\bin\phpsdk_setvars.bat && "c:\Program Files (x86)\Microsoft Visual Studio 14.0\VC\vcvarsall.bat" %1 && .\buildconf --force && .\configure %options% && nmake && nmake install