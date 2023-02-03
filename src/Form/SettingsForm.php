<?php

namespace Drupal\security_monitor\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for security_monitor module.
 */
class SettingsForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'security_monitor_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $fields = $this->check_data();

        $form['execute_job'] = $this->check_execution();

        if($fields) {
            $form['endpoint_url'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Security Dashboard Endpoint URL'),
                '#description' => $this->t('Endpoint URL for Dashboard where JSON data will be sent.'),
                '#default_value' => $fields["url"],
            ];

            $form['authorization_token'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Security Dashboard Authorization Token'),
                '#description' => $this->t('Authorization Token for the Security Dashboard.'),
                '#default_value' => $fields["token"],
            ];
        } else {
            $form['endpoint_url'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Security Dashboard Endpoint URL'),
                '#description' => $this->t('Endpoint URL for Dashboard where JSON data will be sent.'),
            ];

            $form['authorization_token'] = [
                '#type' => 'textfield',
                '#title' => $this->t('Security Dashboard Authorization Token'),
                '#description' => $this->t('Authorization Token for the Security Dashboard.'),
            ];
        };

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save Configuration'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * Checks if there is any data to pre-populate form fields with.
     *
     * @param array fields
     * - Contains both fields to prepopulate
     *
     * @param bool fields
     * - If no data found, will return False.
     */
    public function check_data() {
        try{
            $database = \Drupal::database();
            $query = $database->query("SELECT * FROM {security_monitor}");
            $result = $query->fetchAssoc();
        } catch(Exception $ex){
            \Drupal::logger('security_monitor')->error($ex->getMessage());
        }

        if($result) {
            $fields["url"] = $result['url'];
            $fields["token"] = $result['token'];
        } else {
            $fields = FALSE;
        };

        return $fields;
    }

    /**
     * Returns the last execution time of the job and adds the form
     * field for running the job manually.
     *
     * @param array attributes
     * Attributes for a submit button on form.
     */
    public function check_execution() {
        try {
            $database = \Drupal::database();
            $query = $database->query("SELECT * FROM {security_monitor_cron}");
            $result = $query->fetchAssoc();
        } catch(Exception $ex){
            \Drupal::logger('security_monitor')->error($ex->getMessage());
        };

        if ($result) {
            $last_execution_time = $result['last_executed'];
            $date = date('d/m/Y - H:i:s', $last_execution_time);
            $prefix = '<b>Last Execution Time:</b> ' . $date . ' <br>';
        } else {
            $prefix = '<b>Last Execution Time:</b> N/A <br>';
        };

        $attributes = [
            '#type' => 'submit',
            '#value' => $this->t('Run Job Manually'),
            '#submit' => array([$this, 'run_job_submit']),
            '#prefix' => $this->t($prefix),
        ];

        return $attributes;
    }

    /**
     * {@inheritdoc}
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        if (empty($form_state->getValue('endpoint_url'))) {
            $form_state->setErrorByName('endpoint_url', $this->t('Please input a URL.'));
        };
        if (empty($form_state->getValue('authorization_token'))) {
            $form_state->setErrorByName('authorization_token', $this->t('Please input a authorization token.'));
        };
    }

    /**
     * Runs the job and creates a message to be displayed to the user.
     */
    public function run_job_submit(array &$form, FormStateInterface $form_State) {
        \Drupal::service('json_handler')->send_json();
        \Drupal::messenger()->addMessage($this->t('The job has been ran.'));
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        try {
            $database = \Drupal::database();

            $field = $form_state->getValues();

            $fields["url"] = $field['endpoint_url'];
            $fields["token"] = $field['authorization_token'];

            $database->delete('security_monitor')
                ->execute();

            $database->insert('security_monitor')
                ->fields($fields)
                ->execute();

            \Drupal::messenger()->addMessage($this->t('The fields have been saved.'));
        } catch(Exception $ex){
            \Drupal::logger('security_monitor')->error($ex->getMessage());
        };
    }

}