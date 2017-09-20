--The script can be run to read the results. You can filter out the results ran on a certain date by changing the date at the end.
DECLARE @convByteToMegabyte int = 1048576
select t1.ResultId, Test, Client, Server, Driver, Duration, Memory, Success, Team, StartTime from
(
select pr.ResultId, pr.Success, pt.TestName as Test, cl.HostName as Client, srv.HostName as Server,
tm.TeamName as Team, st.value as Driver, bi.value as Duration, CAST(bi2.value AS decimal(10,2))/@convByteToMegabyte as Memory, dt.value as StartTime from 
KeyValueTableBigInt bi, 
KeyValueTableBigInt bi2, 
KeyValueTableString st,
KeyValueTableDate dt,
PerformanceResults pr,
Clients cl,
PerformanceTests pt,
Teams tm,
Servers srv
where bi.name = 'duration' and bi.ResultId = pr.ResultId 
and bi2.name = 'memory' and bi2.ResultId = pr.ResultId
and dt.name = 'startTime' and dt.ResultId = pr.ResultId
and st.name = 'driver' and st.ResultId = pr.ResultId
and cl.ClientId = pr.ClientId
and pt.TestId = pr.TestId
and tm.TeamId = pr.TeamId
and srv.ServerId = pr.ServerId
) t1 where StartTime like '%2017-06-23%'