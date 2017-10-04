--TEST--
retrieval of XML as a string and a stream.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once("MsCommon.inc");

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $stmt = sqlsrv_query($conn, "SELECT xml_type FROM [159137]");
    if ($stmt == false) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_query failed.");
    }

    sqlsrv_fetch($stmt);
    $str = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($str === false) {
        var_dump(sqlsrv_errors());
    }
    echo "$str\n";

    sqlsrv_fetch($stmt);
    $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
    if ($stream === false) {
        var_dump(sqlsrv_errors());
        die("reading as a stream failed.");
    }
    while (!feof($stream)) {
        $xml = fread($stream, 79);
        echo "$xml\n";
    }

?>
--EXPECT--
<?xml-stylesheet href="ProductDescription.xsl" type="text/xsl"?><p1:ProductDescription xmlns:p1="http://schemas.microsoft.com/sqlserver/2004/07/adventure-works/ProductModelDescription" xmlns:wm="http://schemas.microsoft.com/sqlserver/2004/07/adventure-works/ProductModelWarrAndMain" xmlns:wf="http://www.adventure-works.com/schemas/OtherFeatures" xmlns:html="http://www.w3.org/1999/xhtml" ProductModelID="19" ProductModelName="Mountain 100"><p1:Summary><html:p>Our top-of-the-line competition mountain bike. 
 				Performance-enhancing options include the innovative HL Frame, 
				super-smooth front suspension, and traction for all terrain.
                        </html:p></p1:Summary><p1:Manufacturer><p1:Name>AdventureWorks</p1:Name><p1:Copyright>2002</p1:Copyright><p1:ProductURL>HTTP://www.Adventure-works.com</p1:ProductURL></p1:Manufacturer><p1:Features>These are the product highlights. 
                 <wm:Warranty><wm:WarrantyPeriod>3 years</wm:WarrantyPeriod><wm:Description>parts and labor</wm:Description></wm:Warranty><wm:Maintenance><wm:NoOfYears>10 years</wm:NoOfYears><wm:Description>maintenance contract available through your dealer or any AdventureWorks retail store.</wm:Description></wm:Maintenance><wf:wheel>High performance wheels.</wf:wheel><wf:saddle><html:i>Anatomic design</html:i> and made from durable leather for a full-day of riding in comfort.</wf:saddle><wf:pedal><html:b>Top-of-the-line</html:b> clipless pedals with adjustable tension.</wf:pedal><wf:BikeFrame>Each frame is hand-crafted in our Bothell facility to the optimum diameter 
				and wall-thickness required of a premium mountain frame. 
				The heat-treated welded aluminum frame has a larger diameter tube that absorbs the bumps.</wf:BikeFrame><wf:crankset> Triple crankset; alumunim crank arm; flawless shifting. </wf:crankset></p1:Features><!-- add one or more of these elements... one for each specific product in this product model --><p1:Picture><p1:Angle>front</p1:Angle><p1:Size>small</p1:Size><p1:ProductPhotoID>118</p1:ProductPhotoID></p1:Picture><!-- add any tags in <specifications> --><p1:Specifications> These are the product specifications.
                   <Material>Almuminum Alloy</Material><Color>Available in most colors</Color><ProductLine>Mountain bike</ProductLine><Style>Unisex</Style><RiderExperience>Advanced to Professional riders</RiderExperience></p1:Specifications></p1:ProductDescription>
<?xml-stylesheet href="ProductDescription.xsl" type="text/xsl"?><p1:ProductDesc
ription xmlns:p1="http://schemas.microsoft.com/sqlserver/2004/07/adventure-work
s/ProductModelDescription" xmlns:wm="http://schemas.microsoft.com/sqlserver/200
4/07/adventure-works/ProductModelWarrAndMain" xmlns:wf="http://www.adventure-wo
rks.com/schemas/OtherFeatures" xmlns:html="http://www.w3.org/1999/xhtml" Produc
tModelID="23" ProductModelName="Mountain-500"><p1:Summary><html:p>Suitable for 
any type of riding, on or off-road. 
			Fits any budget. Smooth-shifting with a
 comfortable ride.
                        </html:p></p1:Summary><p1:Manufactur
er><p1:Name>AdventureWorks</p1:Name><p1:Copyright>2002</p1:Copyright><p1:Produc
tURL>HTTP://www.Adventure-works.com</p1:ProductURL></p1:Manufacturer><p1:Featur
es>Product highlights include: 
                 <wm:Warranty><wm:WarrantyPerio
d>1 year</wm:WarrantyPeriod><wm:Description>parts and labor</wm:Description></w
m:Warranty><wm:Maintenance><wm:NoOfYears>3 years</wm:NoOfYears><wm:Description>
maintenance contact available through dealer</wm:Description></wm:Maintenance><
wf:wheel>Stable, durable wheels suitable for novice riders.</wf:wheel><wf:saddl
e>Made from synthetic leather and features gel padding for increased comfort.</
wf:saddle><wf:pedal><html:b>Expanded platform</html:b> so you can ride in any s
hoes; great for all-around riding.</wf:pedal><wf:crankset> Super rigid spindle.
 </wf:crankset><wf:BikeFrame>Our best value frame utilizing the same, ground-br
eaking technology as the ML aluminum frame.</wf:BikeFrame></p1:Features><!-- ad
d one or more of these elements... one for each specific product in this produc
t model --><p1:Picture><p1:Angle>front</p1:Angle><p1:Size>small</p1:Size><p1:Pr
oductPhotoID>1</p1:ProductPhotoID></p1:Picture><!-- add any tags in <specificat
ions> --><p1:Specifications> These are the product specifications.
            
       <Height>Varies</Height> Centimeters.
                   <Material>Alumin
um Alloy</Material><Color>Available in all colors.</Color><ProductLine>Mountain
 bike</ProductLine><Style>Unisex</Style><RiderExperience>Novice to Intermediate
 riders</RiderExperience></p1:Specifications></p1:ProductDescription>
