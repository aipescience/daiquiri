Daiquiri
========

A framework for the publication of scientific databases
-------------------------------------------------------

                           ....    Today, the publication of research data plays 
     .o:;;,,,''''''....,;;,'.      an important role in astronomy and astrophysics.
      ,oll::;;;;;;;;;:ol'...       On the one hand, dedicated programs, such as
       .:do''...   .;'.. ,'        surveys like SDSS and RAVE, data intensive
         ;xd.     .:dc:;c.         instruments like LOFAR, or massive simulations 
          .dO,       .l;           like Millennium and MultiDark are initially
            ox;     ;l.            planned to release their data for the 
             cl,.. l,              community. On the other hand, for more 
             .;,:;:o               traditionally oriented research projects 
               .;l.                data publication becomes a key requirement
               .xx                 demanded by the funding agencies.
               .kd                 
                kd                 The common approach is to publish this data 
                xx                 via dedicated web sites. This includes rather 
                xx.                simple HTML forms as well as complex query 
                lo.                systems such as SDSS-CAS or the RAVE database 
                c,'                query interface. Most of these web sites are 
           .. ....'.               tailor made for the particular case and are 
           ','''''.....            therefore not easily transferrable to future 
                                   projects.

At Leibniz-Institute for Astrophysics Potsdam (AIP), we gained experience with both 
the maintenance and the development of such applications. The RAVE and MultiDark 
databases, the German SDSS mirror, and several smaller web sites are written or 
at least maintained by us. It became, however, apparent that already the current 
plethora of applications constitutes a major challenge for maintenance expenses 
and scalability.

In order to address these issues, we developed a new web framework, which is 
particularly designed to allow for different highly customizable web applications 
based on a common easily maintainable code base. It is called Daiquiri.

Features
--------

Here are some features that come with Daiquiri. This feature list is far from 
complete. One of the key design goals of Daiquiri is, to each advanced complex 
feature to have a simple version that works out of the box. Many of the advanced 
features require 3rd party tools and are thus more complicated to set up. Such 
features are marked in bold. Further we strive to make Daiquiri as Virtual 
Observatory (VO) compliant as possible. The currently implemented VO features 
are marked with VO.

- User management
- Permission management
- SQL query form
- SQL query permission handling
- Data query forms
- Query management
- Query management using MySQL Job Queue
- Integration with PaQu, a parallel query engine
- Database table viewer
- First impression in-browser plotting
- Synchronous and asynchronous data export into different formats (MySQL Dump, 
  CSV, VOTable ASCII + Binary (VO))
- Database table and column meta data management (supporting UCDs (VO))
- File links and downloads through database tables
- UWS job submission (VO)
- Easy WordPress integration

Technology behind Daiquiri
--------------------------

Daiquiri uses PHP together with the Zend framework. It is currently build for 
MySQL, but should be able to run with any database after some minor modifications. 
The advanced features though are tightly integrated with MySQL extensions that we 
built for use with Daiquiri. These are the MySQL query queue, PaQu, and the IVOA 
MySQL VOTable dumper. The asynchronous data export relies on Gearman. The user 
interface makes use of jQuery, bootstrap, CodeMirror and flot.

Credits
-------

Daiquiri is developed and maintained by the E-Science group at the 
[www.aip.de](Leibniz-Institute for Astrophysics Potsdam).

Lead developers are [Jochen Klar](http://jochenklar.de) and 
[Adrian Partl](https://www.adrian-partl.de/).