关于ImageMagick
-------------

　　ImageMagick (TM) 是一个免费的创建、编辑、合成图片的软件。它可以读取、转换、写入多种格式的图片。图片切割、颜色替换、各种效果的应用，图片的旋转、组合，文本，直线，多边形，椭圆，曲线，附加到图片伸展旋转。
　　
关于PHP API for ImageMagick
-------------
    文件中封装了imagick在php环境下的三个切图方法。

安装ImageMagick以及PHP扩展
-------------
```
wget http://www.imagemagick.org/download/ImageMagick.tar.gz
tar -zxvf ImageMagick.tar.gz
cd ImageMagick
./configure --with-php-config=/usr/local/imagemagick
make && make install
 
wget http://pecl.php.net/get/imagick-3.1.1.tgz
tar -xvf imagick-3.1.1.tgz 
cd imagick
phpize
./configure --with-php-config=/usr/local/php54/bin/php-config --with-imagick=/usr/local/imagemagick
make ＆＆ make install
然后php.ini添加 
extension=imagick.so
重启php-fpm
```
