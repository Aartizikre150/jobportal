<?php

namespace Drupal\google_meeting\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\google_meeting\Form\CustomMeetingForm;
use Symfony\Component\HttpFoundation\Response;

class CustomMeetingController extends ControllerBase {

  public function createMeetingForm() {
    $form = $this->formBuilder()->getForm(CustomMeetingForm::class);
    return $form;
  }

}
