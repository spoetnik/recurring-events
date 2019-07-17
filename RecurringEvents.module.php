<?php

namespace ProcessWire;

use RRule\RRule;

/**
 *
 * Saas
 *
 * @author Enter later
 *
 * ProcessWire 3.x
 * Copyright (C) 2011 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://www.processwire.com
 * http://www.ryancramer.com
 *
 */

class RecurringEvents extends WireData implements Module, ConfigurableModule
{

  /**
   * Module information
   */
  public static function getModuleInfo()
  {
    return array(
      'title' => 'Recurring Events',
      'summary' => 'Create Recurring events. Recurrences are calculated, and only created when needed.',
      'version' => '0.0.1',
      'author' => 'Enter later',
      'href' => 'https://github.com/spoetnik/RecurringEvents',
      'icon' => 'calendar',
      'autoload' => true,
      'requires' => 'ProcessWire>=3.0.0',
    );
  }

  const RecurringEventsfields = [
    'RR_frequency',
    'RR_dtstart',
    'RR_interval',
    'RR_wkst',
    'RR_count',
    'RR_until',
    'RR_bymonth',
    'RR_byweekno',
    'RR_byyearday',
    'RR_bymonthday',
    'RR_byday',
    'RR_byhour',
    'RR_byminute',
    'RR_bysecond',
    'RR_bysetpos'
  ];

  /**
   * Construct
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Ready
   */
  public function ready()
  { }

  /**
   * Config inputfields
   *
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $modules = $this->wire('modules');
    $tmpTemplates = wire('templates');

    foreach ($tmpTemplates as $template) { // exclude system templates
      if ($template->flags & Template::flagSystem) continue;
      $templates[] = $template;
    }

    /* @var InputfieldSelect $f */
    $f = $modules->get("InputfieldSelect");
    $f_name = 'EventTemplate';
    $f->name = $f_name;
    $f->label = $this->_('Template for your event');
    $f->description = $this->_('Choose the template wich represents your event');
    foreach ($templates as $template) $f->addOption($template->name);
    $f->value = $this->$f_name;
    $inputfields->add($f);

    /* @var InputfieldSelect $f */
    $f = $modules->get("InputfieldSelect");
    $f_name = 'OcurrenceTemplate';
    $f->name = $f_name;
    $f->label = $this->_('Template for your event-ocurrence');
    $f->description = $this->_('Choose the template wich represents your event-ocurrences');
    foreach ($templates as $template) $f->addOption($template->name);
    $f->value = $this->$f_name;
    $inputfields->add($f);

    $modules->addHookAfter('saveConfig', $this, 'afterConfigSave');
  }

  /**
   * Process the submitted config data
   *
   * @param HookEvent $event
   */
  protected function afterConfigSave(HookEvent $event)
  {
    $tmpTemplates = wire('templates');

    foreach ($tmpTemplates as $template) { // exclude system templates
      if ($template->flags & Template::flagSystem) continue;
      $templates[] = $template;
    }

    if ($event->arguments(0) != $this) return;
    $data = $event->arguments(1);
    // Add RecurringEventsfields to selected templates
    foreach ($templates as $template) {

      if ($template->name == $data['EventTemplate']) {

        foreach (self::RecurringEventsfields as $RecurringEventsfield) {
          if ($template->hasField($RecurringEventsfield)) {
            continue;
          } else {

            $template->fields->add($RecurringEventsfield);
            $template->fields->save();
          }
        }
      } else {
        foreach (self::RecurringEventsfields as $RecurringEventsfield) {
          if ($template->hasField($RecurringEventsfield)) {
            // remove RecurringEventsfields field						
            $template->fields->remove($RecurringEventsfield);
            $template->fields->save();
          } else {
            continue;
          }
        }
      }
    }
    $event->arguments(1, $data);
  }

  /**
   * Create fields and templates
   *
   */
  public function ___install()
  {
    // create frequency field on user template
    if (!$this->wire('fields')->RR_frequency) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f_name = 'RR_frequency';
      $f->name = $f_name;
      $f->required = 1;
      $f->label = $this->_('FREQUENCY');
      $f->description = $this->_('Frequency field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'frequency' field: " . $e->getMessage());
      }
      // Add options to frequency field
      $options = 'SECONDLY
                  MINUTELY
                  HOURLY
                  DAILY
                  WEEKLY
                  MONTHLY
                  YEARLY';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'frequency' options: " . $e->getMessage());
      }
    }

    // create dtstart field on user template
    if (!$this->wire('fields')->RR_dtstart) {
      $f = new Field();
      $f->type = 'FieldtypeDatetime';
      $f_name = 'RR_dtstart';
      $f->name = $f_name;
      $f->datepicker = 3;
      $f->label = $this->_('DTSTART');
      $f->description = $this->_('Dtstart field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'dtstart' field: " . $e->getMessage());
      }
    }

    // create interval field on user template
    if (!$this->wire('fields')->RR_interval) {
      $f = new Field();
      $f->type = 'FieldtypeInteger';
      $f_name = 'RR_interval';
      $f->name = $f_name;
      $f->label = $this->_('INTERVAL');
      $f->description = $this->_('interval field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'interval' field: " . $e->getMessage());
      }
    }

    // create wkst field on user template
    if (!$this->wire('fields')->RR_wkst) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f_name = 'RR_wkst';
      $f->name = $f_name;
      $f->label = $this->_('WKST');
      $f->description = $this->_('wkst field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'dtstart' field: " . $e->getMessage());
      }
      // Add options to wkst field
      $options = 'MO
                  TU
                  WE
                  TH
                  FR
                  SA
                  SU';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'wkst' options: " . $e->getMessage());
      }
    }

    // create count field on user template
    if (!$this->wire('fields')->RR_count) {
      $f = new Field();
      $f->type = 'FieldtypeInteger';
      $f_name = 'RR_count';
      $f->name = $f_name;
      $f->label = $this->_('COUNT');
      $f->description = $this->_('count field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'count' field: " . $e->getMessage());
      }
    }

    // create until field on user template
    if (!$this->wire('fields')->RR_until) {
      $f = new Field();
      $f->type = 'FieldtypeDatetime';
      $f_name = 'RR_until';
      $f->name = $f_name;
      $f->datepicker = 3;
      $f->label = $this->_('UNTIL');
      $f->description = $this->_('Until field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'until' field: " . $e->getMessage());
      }
    }

    // create bymonth field on user template
    if (!$this->wire('fields')->RR_bymonth) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f->inputfieldClass = 'InputfieldAsmSelect';
      $f_name = 'RR_bymonth';
      $f->name = $f_name;
      $f->label = $this->_('BYMONTH');
      $f->description = $this->_('bymonth field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'bymonth' field: " . $e->getMessage());
      }
      // Add options to bymonth field
      $options = 'JAN
                  FEB
                  MAR
                  APR
                  MAY
                  JUNE
                  JULY
                  AUG
                  SEPT
                  OCT
                  NOV
                  DEC';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'bymonth' options: " . $e->getMessage());
      }
    }

    // create byweekno field on user template
    if (!$this->wire('fields')->RR_byweekno) {
      $f = new Field();
      $f->type = 'FieldtypeInteger';
      $f_name = 'RR_byweekno';
      $f->name = $f_name;
      $f->label = $this->_('BYWEEKNO');
      $f->description = $this->_('byweekno field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'byweekno' field: " . $e->getMessage());
      }
    }

    // create RR_byyearday field on user template
    if (!$this->wire('fields')->RR_byyearday) {
      $f = new Field();
      $f->type = 'FieldtypeInteger';
      $f_name = 'RR_byyearday';
      $f->name = $f_name;
      $f->label = $this->_('BYYEARDAY');
      $f->description = $this->_('byyearday field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'byyearday' field: " . $e->getMessage());
      }
    }

    // create RR_bymonthday field on user template
    if (!$this->wire('fields')->RR_bymonthday) {
      $f = new Field();
      $f->type = 'FieldtypeInteger';
      $f_name = 'RR_bymonthday';
      $f->name = $f_name;
      $f->label = $this->_('BYMONTHDAY');
      $f->description = $this->_('bymonthday field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'bymonthday' field: " . $e->getMessage());
      }
    }

    // create byday field on user template
    if (!$this->wire('fields')->RR_byday) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f->inputfieldClass = 'InputfieldAsmSelect';
      $f_name = 'RR_byday';
      $f->name = $f_name;
      $f->label = $this->_('BYDAY');
      $f->description = $this->_('byday field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'byday' field: " . $e->getMessage());
      }
      // Add options to byday field
      $options = 'MO
                  TU
                  WE
                  TH
                  FR
                  SA
                  SU';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'byday' options: " . $e->getMessage());
      }
    }

    // create byhour field on user template
    if (!$this->wire('fields')->RR_byhour) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f->inputfieldClass = 'InputfieldAsmSelect';
      $f_name = 'RR_byhour';
      $f->name = $f_name;
      $f->label = $this->_('BYHOUR');
      $f->description = $this->_('byhour field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'byhour' field: " . $e->getMessage());
      }
      // Add options to byhour field
      $options = '0
                  1
                  2
                  3
                  4
                  5
                  6
                  7
                  8
                  9
                  10
                  11
                  12
                  13
                  14
                  15
                  16
                  17
                  18
                  19
                  20
                  21
                  22
                  23';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'byhour' options: " . $e->getMessage());
      }
    }

    // create byminute field on user template
    if (!$this->wire('fields')->RR_byminute) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f->inputfieldClass = 'InputfieldAsmSelect';
      $f_name = 'RR_byminute';
      $f->name = $f_name;
      $f->label = $this->_('BYMINUTE');
      $f->description = $this->_('byminute field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'byminute' field: " . $e->getMessage());
      }
      // Add options to byminute field
      $options = '0
                  1
                  2
                  3
                  4
                  5
                  6
                  7
                  8
                  9
                  10
                  11
                  12
                  13
                  14
                  15
                  16
                  17
                  18
                  19
                  20
                  21
                  22
                  23
                  24
                  25
                  26
                  27
                  28
                  29
                  30
                  31
                  32
                  33
                  34
                  35
                  36
                  37
                  38
                  39
                  40
                  41
                  42
                  43
                  44
                  45
                  46
                  47
                  48
                  49
                  50
                  51
                  52
                  53
                  54
                  55
                  56
                  57
                  58
                  59';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'byminute' options: " . $e->getMessage());
      }
    }

    // create bysecond field on user template
    if (!$this->wire('fields')->RR_second) {
      $f = new Field();
      $f->type = 'FieldtypeOptions';
      $f->inputfieldClass = 'InputfieldAsmSelect';
      $f_name = 'RR_bysecond';
      $f->name = $f_name;
      $f->label = $this->_('BYSECOND');
      $f->description = $this->_('bysecond field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'bysecond' field: " . $e->getMessage());
      }
      // Add options to bysecond field
      $options = '0
                  1
                  2
                  3
                  4
                  5
                  6
                  7
                  8
                  9
                  10
                  11
                  12
                  13
                  14
                  15
                  16
                  17
                  18
                  19
                  20
                  21
                  22
                  23
                  24
                  25
                  26
                  27
                  28
                  29
                  30
                  31
                  32
                  33
                  34
                  35
                  36
                  37
                  38
                  39
                  40
                  41
                  42
                  43
                  44
                  45
                  46
                  47
                  48
                  49
                  50
                  51
                  52
                  53
                  54
                  55
                  56
                  57
                  58
                  59
                  60';

      $manager = $this->wire(new SelectableOptionManager());
      $manager->setOptionsString($f, $options, false);
      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error saving 'bysecond' options: " . $e->getMessage());
      }
    }

    // create bysetpos field on user template
    if (!$this->wire('fields')->RR_bysetpos) {
      $f = new Field();
      $f->type = 'FieldtypeInteger';
      $f_name = 'RR_bysetpos';
      $f->name = $f_name;
      $f->label = $this->_('BYSETPOS');
      $f->description = $this->_('bysetpos field for the event, part of the Rrule');

      try {
        $f->save();
      } catch (\Exception $e) {
        $this->error("Error creating 'bysetpos' field: " . $e->getMessage());
      }
    }
  }
  /**
   * Remove fields and templates
   * 
   */
  public function ___uninstall()
  {
    $fields = wire('fields');
    $templates = wire('templates');

    foreach (self::RecurringEventsfields as $field) {
      if ($fields->get($field)) {

        // remove the field from all templates
        foreach ($templates as $template) {
          if (!$template->hasField($field)) continue;
          $template->fields->remove($field);
          $template->fields->save();
        }
        // remove field
        try {
          $fields->delete($fields->get($field));
        } catch (\Exception $e) {
          $this->error("Error deleting" . $field->name . "field: " . $e->getMessage());
        }
      }
    }
  }
}
