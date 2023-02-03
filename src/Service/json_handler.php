<?php

namespace Drupal\security_monitor\Service;

class json_handler {

    public function send_json() {
        update_time();
        return;
    }

    public function update_time() {
        try {
            $database = \Drupal::database();
            $database->delete('security_monitor_cron')
                ->execute();

            $time = \Drupal::time()->getRequestTime();
            $fields["last_executed"] = $time;

            $database->insert('security_monitor_cron')
                ->fields($fields)
                ->execute();
        } catch(Exception $ex){
            \Drupal::logger('security_monitor')->error($ex->getMessage());
        };
    }
}