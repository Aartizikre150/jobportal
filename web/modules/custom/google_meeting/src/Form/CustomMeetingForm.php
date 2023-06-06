<?php

namespace Drupal\google_meeting\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class CustomMeetingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_meeting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meeting Title'),
      '#required' => true,
    ];

    $form['participants'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Participants (Email addresses separated by comma)'),
      '#required' => true,
    ];

    $form['start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Start Date'),
      '#required' => true,
    ];

    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Duration (in minutes)'),
      '#required' => true,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Meeting'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Load the Google API client configuration.
    $config = [
      'web' => [
        'client_id' => '953055555880-fcr18sdiidamk3cmq930i00o6bn003hl.apps.googleusercontent.com',
        'project_id' => 'friendly-folio-329412',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_secret' => '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',
        'redirect_uris' => ['https://jobportal.com/google_api_client/callback'],
      ],
    ];

    // Set up the Google API client.
    $client = new Google_Client();
    $client->setClientId($config['web']['client_id']);
    $client->setClientSecret($config['web']['client_secret']);
    $client->setRedirectUri($config['web']['redirect_uris'][0]);
    $client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);

    // Check if the access token is already available, otherwise redirect for authentication.
    if (!empty($_SESSION['google_access_token'])) {
      $client->setAccessToken($_SESSION['google_access_token']);
    } else {
      // Redirect for authentication.
      $authUrl = $client->createAuthUrl();
      $response = new TrustedRedirectResponse($authUrl);
      $response->send();
      return;
    }

    // Refresh the access token if it has expired.
    if ($client->isAccessTokenExpired()) {
      $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      $_SESSION['google_access_token'] = $client->getAccessToken();
    }

    // Create a Google Calendar service.
    $service = new Google_Service_Calendar($client);

    // Create a new event for the meeting.
    $event = new Google_Service_Calendar_Event();
    $event->setSummary($form_state->getValue('title'));
    $event->setDescription('This is a Google Meeting created using Drupal.');

    // Set the meeting start and end time.
    $startDate = new \DateTime($form_state->getValue('start_date'));
    $endDate = clone $startDate;
    $endDate->modify('+ ' . $form_state->getValue('duration') . ' minutes');

    $event->setStart(
      [
        'dateTime' => $startDate->format(\DateTimeInterface::RFC3339),
        'timeZone' => 'UTC', // Modify the timezone if needed.
      ]
    );

    $event->setEnd(
      [
        'dateTime' => $endDate->format(\DateTimeInterface::RFC3339),
        'timeZone' => 'UTC', // Modify the timezone if needed.
      ]
    );

    // Set the participants' email addresses.
    $participants = explode(',', $form_state->getValue('participants'));
    $attendees = [];
    foreach ($participants as $participant) {
      $attendees[] = ['email' => trim($participant)];
    }
    $event->setAttendees($attendees);

    // Insert the event into the Google Calendar.
    $calendarId = 'primary'; // Use the primary calendar for simplicity.
    $event = $service->events->insert($calendarId, $event);

    // Output the Google Meeting URL.
    $meetingUrl = $event->getHangoutLink();

    // Display the meeting URL to the user.
    drupal_set_message($this->t('Google Meeting created successfully. Meeting URL: <a href="@url" target="_blank">@url</a>', ['@url' => $meetingUrl]));

    // Log the meeting URL in the Drupal system log.
    $message = $this->t('Google Meeting created. Meeting URL: @url', ['@url' => $meetingUrl]);
    watchdog('custom_meeting', $message);
  }
}
