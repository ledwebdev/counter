counter
=======

This repository contains some simple scripts used for tracking building
and facility at the [Leddy Library](https://leddy.uwindsor.ca) at the 
[University of Windsor](https://www.uwindsor.ca). The _count.php_
script produces a form as shown:

<img src="https://github.com/ledwebdev/counter/blob/main/leddy.png?raw=true" width="70%" height="70%">

There are a couple of variables that need to be set for _MySQL_ credentials,
the tables used by the script are in _tables.sql_. There are tables
which are used to track [LibCal](https://www.springshare.com/libcal/) activity,
these designate what kinds and types of resources are being tracked.

The _status.php_ script is used to provide status information. The output
is in [JSON](https://www.json.org/json-en.html) and looks like:

```
{"total":27,"limit":350,"bookings":[{"space_id":0000,"category":"PCs","counter":9,"capacity":10},{"space_id":0000,"category":"Rooms","counter":4,"capacity":5},{"space_id":0000,"category":"Desks","counter":10,"capacity":20}]}
```
This can be used with a javascript widget for displaying ongoing capacty status.

art rhyno [ourdigitalworld/cdigs](https://github.com/artunit)
