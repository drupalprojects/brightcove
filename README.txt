Brightcove integration API and CCK field. Features:

* Browse videos coming from Brightcove Studio directly in Drupal with a possibility to search in videos by name or tags.
* Upload a video for Brightcove customers with at least a Professional account (You need a Write API key).
* Integrates with Views module - creates fields with a video player, all available metadata such as Plays count or Creation dates.
* Provides various formatters including a Lightbox2 player and Imagecache integration for remote images. 


Requirements
------------

CCK: http://drupal.org/project/cck
ModalFrame API: http://drupal.org/project/modalframe - It is currently recommended to use ModalFrame 6.x-1.6 with the Brightcove module.. It is possible to use with ModalFrame 6.x-1.7 (which requires jQuery UI 1.7.x and jQuery Update 6.x-2.x) however this version breaks Lightbox2 (as of 25.05.2010). 
jQuery UI: http://drupal.org/project/jquery_ui
External libraries: Brightcove PHP MAPI Wrapper, http://opensource.brightcove.com/project/PHP-MAPI-Wrapper/

Installation instructions
-------------------------

Get Read and/or Write API keys from Brightcove support. Refer to section Brightcove Keys if you don't know how to do that.

Download module from http://drupal.org/project/brightcove, untar to sites/all/modules or sites/sitename.com/modules

Download Brightcove PHP MAPI Wrapper from http://opensource.brightcove.com/project/PHP-MAPI-Wrapper/, module is tested with PHP MAPI Wrapper 2.0.4 and later.

Unzip framework to sites/all/libraries/*. After unzipping, there needs to be a file at sites/all/libraries/*/bc-mapi.php.

Enable Brightcove module and Brightcove CCK Field module at your site.

Navigate to admin/settings/brightcove and fill in your Read/Write key and default Player ID from Brightcove Studio (Refer to section Brightcove player if you don't know how to do that)

Create a new CCK field called of type Brightcove video.

Play.

Brightcove PlayerKEY
-----------------------------------------

On admin settings page (admin/settings/brightcove) you must enter the Brightcove PlayerKEY. Here's the process how to find it:
1 - Sign in to the Brightcove Studio,
2 - Go to the Publishing module and select the player,
3 - Click Get Code to copy the player publishing code to your clipboard,
4 - Find, copy and paste the playerKey value to your own embed code.

Other Brightcove Keys
---------------

To be written, target version: Release Candidate.

Brightcove player
-----------------

To be written, target version: Release Candidate.

Media Mover Integration
-----------------------

To be written, target version: Release Candidate.


