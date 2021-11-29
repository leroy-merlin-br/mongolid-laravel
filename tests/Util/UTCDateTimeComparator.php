<?php
namespace Mongolid\Laravel\Tests\Util;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

/**
 * UTCDateTime "assertEquals" comparator.
 */
class UTCDateTimeComparator extends Comparator
{
    /**
     * {@inheritdoc}
     */
    public function accepts($expected, $actual)
    {
        return $expected instanceof UTCDateTime
            && $actual instanceof UTCDateTime;
    }

    /**
     * @param UTCDateTime $expected
     * @param UTCDateTime $actual
     *
     * @throws ComparisonFailure
     *
     * {@inheritdoc}
     */
    public function assertEquals($expected, $actual, $delta = 100, $canonicalize = false, $ignoreCase = false)
    {
        $expectedDateTime = $expected->toDateTime();
        $actualDateTime = $actual->toDateTime();

        if (abs($expectedDateTime->getTimestamp() - $actualDateTime->getTimestamp()) > $delta) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $expectedDateTime->format(DateTime::ISO8601),
                $actualDateTime->format(DateTime::ISO8601)
            );
        }
    }
}
