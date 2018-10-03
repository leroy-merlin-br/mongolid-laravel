<?php
namespace MongolidLaravel;

require __DIR__.'/../vendor/autoload.php';

use SebastianBergmann\Comparator\Factory;

Factory::getInstance()->register(new UTCDateTimeComparator());
