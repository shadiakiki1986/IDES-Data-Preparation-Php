2015-06-12
* attempting at integrating correction doc ref ID, but documentation isn''t so clear

2015-06-06
* data source functionality moved to separate function lib/getFatcaData.php
* refactored var/www/api/getFatcaClients.php to var/www/api/transmitter.php
* cleaned up code a bit

2015-06-04
* dropped ''xsi:schemaLocation'' which seems to have been causing ''virus threat''
* generating new ID''s instead of static one in MessageRefId
* setting timezone to UTC
* DocRefID: currently just putting ''Ref ID123''
* getting balances from Bankflow instead of dummy 0''s
* skipping some accounts just for testing purposes

2015-06-03
* testing in 2nd window
* got feedback saying that decrypted contents contain a virus threat
* working on the issue
* replaced xml signing with package xmlseclibs instead of custom function
* added class to parse received zip into xml
* replacing invalid characters

2015-05
* released first draft that successfully uploads
