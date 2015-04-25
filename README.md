Thumcno - A Tacno Thumbnail Generator in runtime
=================

## Installation

If you use linux, you must install this library.

```
sudo add-apt-repository "deb http://archive.ubuntu.com/ubuntu $(lsb_release -sc) main"
sudo add-apt-repository "deb http://archive.ubuntu.com/ubuntu $(lsb_release -sc) universe"
sudo apt-get update
sudo apt-get install imagemagick php5-imagick
```

Based on timthumb:
http://www.binarymoon.co.uk/projects/timthumb/

You can use for any projects with different domains.

| parameter | stands for | values                  | What it does                                                                                                                                                    |
|-----------|------------|-------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| src       | source     | url to image            | Tells Thumbcno which image to resize                                                                                                                            |
| w         | width      | the width to resize to  | Remove the width to scale proportionally (will then need the height)                                                                                            |
| h         | height     | the height to resize to | Remove the height to scale proportionally (will then need the width)                                                                                            |
| q         | quality    | 0-100                   | Compression quality. The higher the number the nicer the image will look. I wouldn’t recommend going any higher than about 95 else the image will get too large |
| a         | alignment  | c,t,l,r,b,tl,tr,bl,br   | Crop alignment. c = center, t = top, b = bottom, r = right, l = left. The positions can be joined to create diagonal positions                                  |
| zc        | zoom/crop  | 0,1,2,3                 | Change the cropping and scaling settings                                                                                                                        |
| f         | filters    | too many to mention     | You can use the link above                                                                                                                                      |