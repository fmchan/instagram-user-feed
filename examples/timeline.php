<?php

declare(strict_types=1);

use Instagram\Api;
use Instagram\Exception\InstagramException;

use Instagram\Model\TimelineFeed;
use Psr\Cache\CacheException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

require realpath(dirname(__FILE__)) . '/../vendor/autoload.php';
$credentials = include_once realpath(dirname(__FILE__)) . '/credentials.php';

$cachePool = new FilesystemAdapter('Instagram', 0, __DIR__ . '/../cache');

try {
    $api = new Api($cachePool);
    $api->login($credentials->getLogin(), $credentials->getPassword());

    // get timeline
    $timelineFeed = $api->getTimeline();
    $countTimeline = 1;

    echo "Count:{$countTimeline}\n";
    printTimeline($timelineFeed);

    do {
        $timelineFeed = $api->getTimeline($timelineFeed->getMaxId());
        echo "Count:{$countTimeline}\n";
        printTimeline($timelineFeed);

        // avoid 429 Rate limit from Instagram
        sleep(1);
        $countTimeline++;
    } while ($timelineFeed->hasMoreTimeline());

} catch (InstagramException $e) {
    print_r($e->getMessage());
} catch (CacheException $e) {
    print_r($e->getMessage());
}

function printTimeline(TimelineFeed $timelineFeed)
{
    /** @var \Instagram\Model\Timeline $timeline */
    foreach ($timelineFeed->getTimeline() as $timeline) {
        if ($timeline->getContent() != null) {
            echo 'ID        : ' . $timeline->getContent()->getId() . "\n";
            echo 'Code      : ' . $timeline->getContent()->getShortCode() . "\n";
            echo 'Link      : ' . $timeline->getContent()->getLink() . "\n";
            echo 'Caption   : ' . $timeline->getContent()->getCaption() . "\n";
            echo 'Likes     : ' . $timeline->getContent()->getLikes() . "\n";
            echo 'Date      : ' . $timeline->getContent()->getDate()->format('Y-m-d h:i:s') . "\n";
        } else {
            echo 'Content      : null'."\n";
        }
        if ($timeline->getUser() != null)
            echo 'User      : ' . $timeline->getUser()->getFullName() . "\n";
        echo 'Type      : ' . $timeline->getType() . "\n\n";
    }
}