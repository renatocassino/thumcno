Thumcno - A Tacno Thumbnail Generator in runtime
=================

## Installation

```
git clone git@github.com:tacnoman/thumcno.git
```

Based on timthumb:
http://www.binarymoon.co.uk/projects/timthumb/

You can use for any projects with different domains.

How it works?
------------------

The Google recommend that your dynamic images uses another domain (or a subdomain) and with this lib you will can do it with multiple projects in different domains.

First step:
You need to create in folder `apps` a file called `<yourdomain>.ini`. In this file you will be set the params that you want (example: cache directory, etc).
This params will replace the params in default.ini (you can change it).

This is the `default.ini`.

```
; This document has default params
port = 80

debug_on = true    ; Enable debug logging to web server error log. Remember to update this param in your app
debug_level = 1 ; Debug level 1 is less noisy and 3 is the most noisy

memory_limit = 30M              ; Set PHP memory limit
block_external_leechers = false ; If the image or webshot is being loaded on an external site, display a red "No Hotlinking" gif.
display_error_messages = true   ; Display error messages. Set to false to turn off errors (good for production websites)

allow_external = false       ; Allow image fetching from external websites. Will check against ALLOWED_SITES if ALLOW_ALL_EXTERNAL_SITES is false
allow_external_sites = false ; Less secure.

file_cache_enabled = true              ; Should we store resized/modified images on disk to speed things up?
file_cache_time_between_cleans = 86400 ; How often the cache is cleaned

file_cache_max_file_age = 86400 ; How old does a file have to be to be deleted from the cache
file_cache_suffix = .thumcno    ; What to put at the end of all files in the cache directory so we can identify them
file_cache_prefix = null        ; What to put at the beg of all files in the cache directory so we can identify them
file_cache_directory = cache    ; Directory where images are cached. Left blank it will use the system temporary directory (which is better for security)
max_file_size = 10485760        ; 10 Megs is 10485760. This is the max internal or external file size that we'll process.
curl_timeout = 20               ; Timeout duration for Curl. This only applies if you have Curl installed and aren't using PHP's default URL fetching mechanism.
wait_between_fetch_errors = 3600; Time to wait between errors fetching remote file

; Browser caching
browser_cache_max_age = 864000 ; Time to cache in the browser
browser_cache_disable = false  ; Use for testing if you want to disable all browser caching

; Image size and defaults
max_width = 1500         ; Maximum image width
max_height = 1500        ; Maximum image height
not_found_image = null   ; Image to serve if any 404 occurs
error_image = ''         ; Image to serve if an error occurs instead of showing error message
png_is_transparent = true; Define if a png image should have a transparent background color. Use False value if you want to display a custom coloured canvas_colour
default_q = 90           ; Default image quality. Allows overrid in timthumb-config.php
default_zc = 1           ; Default zoom/crop setting. Allows overrid in timthumb-config.php
default_f = null         ; Default image filters. Allows overrid in timthumb-config.php
default_s = 0            ; Default sharpen value. Allows overrid in timthumb-config.php
default_cc = ffffff      ; Default canvas colour. Allows overrid in timthumb-config.php
default_width = 100      ; Default thumbnail width. Allows overrid in timthumb-config.php
default_height = 100     ; Default thumbnail height. Allows overrid in timthumb-config.php
```

You need to replace only the necessary.

See working in local machine
----------------------------

To see in your local machine, run this commands:

```
cd /path/to/thumbcno
php -S localhost:8080
```

Now, go to your browser and open the url:

http://localhost:8080/?src=/example_image/dubai.jpg&w=200&h=200&q=80
You will see this:
![dubai thumb](./example_image/thumbnail.jpg)

Possible params
-------------------

This list is the params that you can pass in url ($_GET):


| parameter | stands for    | values                  | What it does                                                                                                                                                    |
|-----------|---------------|-------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| src       | source        | url to image            | Tells Thumbcno which image to resize                                                                                                                            |
| w         | width         | the width to resize to  | Remove the width to scale proportionally (will then need the height)                                                                                            |
| h         | height        | the height to resize to | Remove the height to scale proportionally (will then need the width)                                                                                            |
| q         | quality       | 0-100                   | Compression quality. The higher the number the nicer the image will look. I wouldn’t recommend going any higher than about 95 else the image will get too large |
| a         | alignment     | c,t,l,r,b,tl,tr,bl,br   | Crop alignment. c = center, t = top, b = bottom, r = right, l = left. The positions can be joined to create diagonal positions                                  |
| zc        | zoom/crop     | 0,1,2,3                 | Change the cropping and scaling settings                                                                                                                        |
| f         | filters       | too many to mention     | You can use the link above                                                                                                                                      |
| s         | sharpen       |                         | Apply a sharpen filter to the image, makes scaled down images look a little crisper                                                                             |
| cc        | canvas colour | hexadecimal color(#fff) | Change background colour. Most used when changing the zoom and crop settings, which in turn can add borders to the image.                                       |
| ct        | canvas transp | true (1)                | Use transparency and ignore background colour                                                                                                                   |


Possible strategy
-------------------

Suppose that you have 3 projects (ex: project01, project02 and project03) with the urls http://project01.com, http://project02.com and http://project03.com.
All projects have a subdirectory called /uploads/ with the dynamic images.

You can make this steps:

First: Create files `i.project01.com.ini`, `i.project02.com.ini` and `i.project03.com.ini` in /apps directory.
Second: Use this params for each ini file.

```
port 80
file_cache_suffix = .project0<id>
file_cache_directory = cache
path_images = project_images_0<id>
```

Third: Create symbolic links to this folders:

```
ln -s /path/to/project<id>/uploads /path/to/thumbcno/project<id>
```

Fourth: Set your nginx/apache to root thumbcno project and try the url:

Ex: http://i.project01.com?src=images/dubai.jpg&width=300&h=300

!TODO
--------------------

Add param route like this

```
.....

route = /(P?<w>\d+)_(P?<h>\d+)/(P?<src>\(.)+) ; ex: i.domain.com/200/150/images/my_image.jpg == i.domain.com?w=200&h=200&src=images/my_image.jpg
....

```

Bugs
---------------

Send-me an email: <renatocassino@gmail.com>
