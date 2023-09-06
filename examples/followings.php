<?php

declare(strict_types=1);

use Instagram\Api;
use Instagram\Auth\Checkpoint\ImapClient;
use Instagram\Exception\InstagramException;

use Instagram\Model\User;
use Psr\Cache\CacheException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

require realpath(dirname(__FILE__)) . '/../vendor/autoload.php';
$credentials = include_once realpath(dirname(__FILE__)) . '/credentials.php';

$cachePool = new FilesystemAdapter('Instagram', 0, __DIR__ . '/../cache');

$countUsers = 0;
$page = 0;

try {
    $api = new Api($cachePool);
    //$api->login($credentials->getLogin(), $credentials->getPassword());
    $imapClient = new ImapClient($credentials->getImapServer(), $credentials->getImapLogin(), $credentials->getImapPassword());
    $api->login($credentials->getLogin(), $credentials->getPassword(), $imapClient);
    // 1518284433 is robertdowneyjr's account id
    $userId = 31610061;

    $timeStart = time();

    $followingFeed = $api->getFollowings($userId);

    $arrayUsers = $followingFeed->getUsers();
    printUsers($arrayUsers);

    do {
        ++$page;
        echo "Page {$page}\n";
        $followingFeed = $api->getMoreFollowings($userId, $followingFeed->getEndCursor());

        $arrayUsersNext = $followingFeed->getUsers();
        $arrayUsers = array_merge((array)$arrayUsers, (array)$arrayUsersNext);
        printUsers($arrayUsersNext);

        // avoid 429 Rate limit from Instagram
        sleep(rand(10,15));
    } while ($followingFeed->hasNextPage());

    $countArrayUsers = count($arrayUsers);
    $timeSpend = time() - $timeStart;
    echo "page:{$page}, total:{$countUsers}, array size:{$countArrayUsers}, time spent:{$timeSpend}s\n";
    
    $jsonString = json_encode((array)$arrayUsers, JSON_PRETTY_PRINT);
    // Write in the file
    $fp = fopen("jsons/".$userId."-followings.json", 'w');
    fwrite($fp, $jsonString);
    fclose($fp);

} catch (InstagramException $e) {
    print_r($e->getMessage());
} catch (CacheException $e) {
    print_r($e->getMessage());
}

function printUsers(array $users)
{
    global $countUsers;
    /** @var User $user */
    foreach ($users as $user) {
        ++$countUsers;
        echo "user {$countUsers}:\n";
        print_r($user);
        //echo $user->getUserName() . PHP_EOL;
    }
}
