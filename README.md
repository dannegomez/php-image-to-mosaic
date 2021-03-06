# php-image-to-mosaic
Create a mosaic image of a picture with different shapes

## How to use
```php
include('mosaic.php');

$Mosaic = new Mosaic("input.jpg");
$Mosaic->create_mosaic("smoothcircle");
```

## Settings
```php
$Mosaic->sample_size = 20; //pixel sample step
$Mosaic->shape_size = 40; //size of mosaic tile shape
$Mosaic->shape_margin = 3; //margin between tiles
```

## Server requirements
* GD module enabled

## Example
input image

![input file](/images/input.jpg)

cricle mosaic

![circle](/images/mosaic_circle.png)

cricle smooth mosaic

![circle smooth](/images/mosaic_smoothcircle.png)

square mosaic

![square](/images/mosaic_square.png)

star mosaic

![star](/images/mosaic_star.png)
