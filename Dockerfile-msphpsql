#Download base image ubuntu 16.04

FROM ubuntu:16.04

# Update Ubuntu Software repository
RUN export DEBIAN_FRONTEND=noninteractive && apt-get update && \
    apt-get -y install \
    apt-transport-https     \
    apt-utils \
    autoconf \
    curl \
    libcurl3 \
    g++ \
    gcc    \
    git \
    lcov \
    libxml2-dev \
    locales \
    make \
    php7.0 \
    php7.0-dev \
    python-pip \
    re2c \
    unixodbc-dev \
    unzip && apt-get clean
    
ARG PHPSQLDIR=/REPO/msphpsql-dev
ENV TEST_PHP_SQL_SERVER sql
ENV TEST_PHP_SQL_UID sa
ENV TEST_PHP_SQL_PWD Password123

# add locale iso-8859-1
RUN sed -i 's/# en_US ISO-8859-1/en_US ISO-8859-1/g' /etc/locale.gen
RUN locale-gen en_US

# set locale to utf-8
RUN locale-gen en_US.UTF-8
ENV LANG='en_US.UTF-8' LANGUAGE='en_US:en' LC_ALL='en_US.UTF-8'

#install ODBC driver	
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -	
RUN curl https://packages.microsoft.com/config/ubuntu/16.04/prod.list > /etc/apt/sources.list.d/mssql-release.list	

RUN export DEBIAN_FRONTEND=noninteractive && apt-get update && ACCEPT_EULA=Y apt-get install -y msodbcsql17 mssql-tools
ENV PATH="/opt/mssql-tools/bin:${PATH}"	

#install coveralls (upgrade both pip and requests first)
RUN python -m pip install --upgrade pip
RUN python -m pip install --upgrade requests
RUN python -m pip install cpp-coveralls

#Either Install git / download zip (One can see other strategies : https://ryanfb.github.io/etc/2015/07/29/git_strategies_for_docker.html )
#One option is to get source from zip file of repository.
#another option is to copy source to build directory on image
RUN mkdir -p $PHPSQLDIR
COPY . $PHPSQLDIR
WORKDIR $PHPSQLDIR/source/ 

RUN chmod +x ./packagize.sh
RUN /bin/bash -c "./packagize.sh"

RUN echo "extension = pdo_sqlsrv.so" >> /etc/php/7.0/cli/conf.d/20-pdo_sqlsrv.ini
RUN echo "extension = sqlsrv.so" >> `php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||"`

WORKDIR $PHPSQLDIR/source/sqlsrv
RUN phpize && ./configure LDFLAGS="-lgcov" CXXFLAGS="-O0 --coverage" && make && make install

WORKDIR $PHPSQLDIR/source/pdo_sqlsrv
RUN phpize && ./configure LDFLAGS="-lgcov" CXXFLAGS="-O0 --coverage" && make && make install

# set name of sql server host to use
WORKDIR $PHPSQLDIR/test/functional/pdo_sqlsrv
RUN sed -i -e 's/TARGET_SERVER/sql/g' MsSetup.inc
RUN sed -i -e 's/TARGET_DATABASE/msphpsql_pdosqlsrv/g' MsSetup.inc
RUN sed -i -e 's/TARGET_USERNAME/'"$TEST_PHP_SQL_UID"'/g' MsSetup.inc
RUN sed -i -e 's/TARGET_PASSWORD/'"$TEST_PHP_SQL_PWD"'/g' MsSetup.inc

WORKDIR $PHPSQLDIR/test/functional/sqlsrv
RUN sed -i -e 's/TARGET_SERVER/sql/g' MsSetup.inc
RUN sed -i -e 's/TARGET_DATABASE/msphpsql_sqlsrv/g' MsSetup.inc
RUN sed -i -e 's/TARGET_USERNAME/'"$TEST_PHP_SQL_UID"'/g' MsSetup.inc
RUN sed -i -e 's/TARGET_PASSWORD/'"$TEST_PHP_SQL_PWD"'/g' MsSetup.inc

WORKDIR $PHPSQLDIR
RUN chmod +x ./entrypoint.sh
CMD /bin/bash ./entrypoint.sh

ENV REPORT_EXIT_STATUS 1
ENV TEST_PHP_EXECUTABLE /usr/bin/php
