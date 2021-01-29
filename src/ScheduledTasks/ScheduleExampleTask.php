<?php

use Scheduled\Scheduled\Scheduled;

$schedule = new \Scheduled\Scheduled\Scheduled();

/**
 * @note Run command
 */
$task = $schedule->run('ping www.google.es');
$task->description('Ping to google');

/**
 * @note Run cakephp shell
 */
//$task = $schedule->shell('Scheduled view all');
//$task->description('Run cake shell');


$task->before(function() {
    // Code before run
});

$task->after(function() {
    // Code after run
});

$task->skip(function() {
    if ((bool) (time() % 2)) {
        return true;
    }

    return false;
});

//$task->daily();
$task->cron('* * * * *');

//$task->appendOutputTo('/var/log/google.log');


$schedule->onError(function() {
    // Code when error
});

// IMPORTANT: You must return the schedule object
return $schedule;
