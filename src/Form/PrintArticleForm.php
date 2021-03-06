<?php

namespace Drupal\thunder_print\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Print article edit forms.
 *
 * @ingroup thunder_print
 */
class PrintArticleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Node author information for administrators.
    $form['author'] = [
      '#type' => 'details',
      '#title' => $this->t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => [
        'class' => ['node-form-author'],
      ],
      '#attached' => [
        'library' => ['node/drupal.node'],
      ],
      '#weight' => 90,
      '#optional' => TRUE,
    ];

    if (isset($form['user_id'])) {
      $form['user_id']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

    $form['footer'] = [
      '#type' => 'container',
      '#weight' => 99,
    ];
    $form['status']['#group'] = 'footer';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function addRevisionableFormFields(array &$form) {
    parent::addRevisionableFormFields($form);

    if (isset($form['revision_log_message'])) {
      $form['revision_log_message'] += [
        '#group' => 'revision_information',
        '#states' => [
          'visible' => [
            ':input[name="revision"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId($this->currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Print article.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Print article.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.print_article.canonical', ['print_article' => $entity->id()]);
  }

}
